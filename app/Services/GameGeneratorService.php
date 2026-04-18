<?php

declare(strict_types=1);

namespace App\Services;

use Illuminate\Http\Client\Response;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class GameGeneratorService
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
     * Generate game content from text.
     *
     * @return array Game data structure matching the game type schema
     */
    public function generateGameContent(
        string $content,
        string $gameType,
        string $classLevel,
        string $difficulty = 'medium'
    ): array {
        $prompts = [
            'memory_match' => 'Generate 12 term-definition pairs for a Memory Match game. Return JSON: {"pairs": [{"term": "...", "definition": "..."}]}. Each term should be a key concept and definition should be a clear, concise explanation.',
            'word_scramble' => 'Generate 15 key terms with hints for a Word Scramble game. Return JSON: {"words": [{"word": "...", "hint": "...", "category": "..."}]}. Words should be single words or short phrases. Hints should help identify the word without giving it away.',
            'quiz_race' => 'Generate 20 rapid-fire multiple choice questions (4 options each) for a Quiz Race game. Return JSON: {"questions": [{"question": "...", "answer": "...", "options": ["...", "...", "...", "..."]}]}. Questions should be quick to read and answer.',
            'flashcard' => 'Generate 20 flashcard pairs for a study game. Return JSON: {"cards": [{"front": "...", "back": "...", "category": "..."}]}. Front should be a question or term, back should be the answer or definition.',
        ];

        $typePrompt = $prompts[$gameType] ?? $prompts['flashcard'];

        $systemPrompt = "You are an educational game content generator for {$classLevel} students in a Nigerian school. Difficulty: {$difficulty}. {$typePrompt} Use age-appropriate, clear language. Make content engaging and educational.";

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
        ], 'game generation');

        if ($response === null) {
            return [];
        }

        $text = $response->json('candidates.0.content.parts.0.text', '{}');
        $parsed = json_decode($text, true);

        if (! is_array($parsed)) {
            Log::warning('Gemini game generation returned non-array', ['text' => $text]);

            return [];
        }

        // Validate expected structure per game type
        $expectedKeys = [
            'memory_match' => 'pairs',
            'word_scramble' => 'words',
            'quiz_race' => 'questions',
            'flashcard' => 'cards',
        ];

        $key = $expectedKeys[$gameType] ?? null;
        if ($key && ! isset($parsed[$key]) && array_is_list($parsed)) {
            // Gemini returned the inner array directly instead of wrapped
            $parsed = [$key => $parsed];
        }

        return $parsed;
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

    /**
     * Generate game content from a free-form prompt.
     */
    public function generateFromPrompt(
        string $prompt,
        string $gameType,
        string $classLevel,
        string $difficulty = 'medium'
    ): array {
        return $this->generateGameContent(
            content: "Topic/Prompt: {$prompt}",
            gameType: $gameType,
            classLevel: $classLevel,
            difficulty: $difficulty,
        );
    }
}
