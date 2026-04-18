<?php

declare(strict_types=1);

namespace App\Services;

use Illuminate\Http\Client\Response;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class QuizGeneratorService
{
    private string $apiKey;

    private string $baseUrl;

    private string $model;

    public ?string $lastError = null;

    public function __construct()
    {
        $this->apiKey = config('services.gemini.api_key');
        $this->baseUrl = config('services.gemini.base_url');
        $this->model = config('services.gemini.model');
    }

    /**
     * Generate quiz questions from text content.
     *
     * @return array<int, array{type: string, question: string, options: array, correct_answer: string, explanation: string}>
     */
    public function generateFromContent(
        string $content,
        string $classLevel,
        int $questionCount = 10,
        array $questionTypes = ['multiple_choice'],
        string $difficulty = 'medium'
    ): array {
        $typesList = implode(', ', $questionTypes);

        $systemPrompt = <<<PROMPT
        You are a quiz generator for {$classLevel} students in a Nigerian school.
        Generate exactly {$questionCount} questions from the provided content.
        Difficulty: {$difficulty}.
        Question types to include: {$typesList}.

        Return ONLY a valid JSON array. Each item must have:
        - "type": "multiple_choice" | "true_false" | "fill_blank"
        - "question": the question text
        - "options": array of 4 options (for multiple_choice), ["True", "False"] (for true_false), [] (for fill_blank)
        - "correct_answer": the exact correct option text
        - "explanation": a brief explanation of why this is correct (1-2 sentences)

        Rules:
        - Questions must be age-appropriate for {$classLevel}
        - Use simple, clear language
        - Make wrong options plausible but clearly incorrect
        - Cover different parts of the content, not just the beginning
        - Do NOT repeat questions
        PROMPT;

        return $this->callGemini($systemPrompt, $content);
    }

    /**
     * Generate quiz from a free-form prompt (no document).
     */
    public function generateFromPrompt(
        string $prompt,
        string $classLevel,
        int $questionCount = 10,
        array $questionTypes = ['multiple_choice'],
        string $difficulty = 'medium'
    ): array {
        return $this->generateFromContent(
            content: "Topic/Prompt: {$prompt}",
            classLevel: $classLevel,
            questionCount: $questionCount,
            questionTypes: $questionTypes,
            difficulty: $difficulty,
        );
    }

    /**
     * Extract text from a document URL using Gemini's multimodal capability.
     */
    public function extractTextFromDocument(string $documentUrl, string $mimeType = 'application/pdf'): string
    {
        $this->lastError = null;
        $url = "{$this->baseUrl}/models/{$this->model}:generateContent?key={$this->apiKey}";

        $response = $this->postWithRetry($url, [
            'contents' => [[
                'parts' => [
                    ['text' => 'Extract all the text content from this document. Return only the text, no formatting or commentary.'],
                    ['file_data' => ['mime_type' => $mimeType, 'file_uri' => $documentUrl]],
                ],
            ]],
        ], 'document extraction');

        if ($response === null) {
            return '';
        }

        return $response->json('candidates.0.content.parts.0.text', '');
    }

    /**
     * Call Gemini API and return parsed JSON array.
     */
    private function callGemini(string $systemPrompt, string $content): array
    {
        $this->lastError = null;
        $url = "{$this->baseUrl}/models/{$this->model}:generateContent?key={$this->apiKey}";

        $response = $this->postWithRetry($url, [
            'system_instruction' => ['parts' => [['text' => $systemPrompt]]],
            'contents' => [['parts' => [['text' => $content]]]],
            'generationConfig' => [
                'temperature' => 0.7,
                'maxOutputTokens' => 8192,
                'responseMimeType' => 'application/json',
            ],
        ], 'quiz generation');

        if ($response === null) {
            return [];
        }

        $text = $response->json('candidates.0.content.parts.0.text', '[]');
        $parsed = json_decode($text, true);

        if (! is_array($parsed)) {
            Log::warning('Gemini returned non-array response', ['text' => $text]);
            $this->lastError = __('AI returned an unexpected response. Please try again.');

            return [];
        }

        if (! array_is_list($parsed) && count($parsed) === 1) {
            $inner = reset($parsed);
            if (is_array($inner) && array_is_list($inner)) {
                return $inner;
            }
        }

        return array_is_list($parsed) ? $parsed : [];
    }

    /**
     * POST to Gemini with exponential backoff on transient failures (429/5xx).
     */
    private function postWithRetry(string $url, array $payload, string $context): ?Response
    {
        $attempts = 3;
        $delayMs = 1000;

        for ($i = 1; $i <= $attempts; $i++) {
            try {
                $response = Http::timeout(90)->post($url, $payload);
            } catch (\Throwable $e) {
                Log::error("Gemini {$context} exception", ['attempt' => $i, 'message' => $e->getMessage()]);
                if ($i === $attempts) {
                    $this->lastError = __('Could not reach the AI service. Please check your connection and try again.');

                    return null;
                }
                usleep($delayMs * 1000);
                $delayMs *= 3;

                continue;
            }

            if ($response->successful()) {
                return $response;
            }

            $status = $response->status();
            $retriable = in_array($status, [429, 500, 502, 503, 504], true);

            Log::error("Gemini {$context} failed", [
                'attempt' => $i,
                'status' => $status,
                'body' => $response->body(),
            ]);

            if (! $retriable || $i === $attempts) {
                $this->lastError = match (true) {
                    $status === 503 => __('The AI service is overloaded right now. Please try again in a moment.'),
                    $status === 429 => __('AI rate limit reached. Please wait a moment and try again.'),
                    $status >= 500 => __('The AI service is temporarily unavailable. Please try again.'),
                    $status === 400 => __('The AI rejected the request. Try a shorter prompt or different content.'),
                    $status === 401 || $status === 403 => __('AI service authentication failed. Please contact support.'),
                    default => __('AI request failed (status :status). Please try again.', ['status' => $status]),
                };

                return null;
            }

            usleep($delayMs * 1000);
            $delayMs *= 3;
        }

        return null;
    }
}
