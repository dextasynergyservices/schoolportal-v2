<?php

declare(strict_types=1);

namespace App\Services;

use Illuminate\Http\Client\Response;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class ExamGeneratorService
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
     * Generate exam questions from content for all 6 question types.
     *
     * @param  array<string>  $questionTypes  e.g. ['multiple_choice','theory','matching']
     * @return array<int, array>
     */
    public function generateFromContent(
        string $content,
        string $classLevel,
        string $subjectName,
        int $questionCount = 10,
        array $questionTypes = ['multiple_choice'],
        string $difficulty = 'medium',
        string $category = 'exam',
    ): array {
        $typesList = implode(', ', $questionTypes);
        $categoryContext = $category === 'assessment' ? 'This is a class assessment/test (shorter, formative).' : 'This is a formal exam (summative).';

        $systemPrompt = <<<PROMPT
        You are an exam question generator for {$classLevel} students studying {$subjectName} in a Nigerian school.
        Generate exactly {$questionCount} questions from the provided content.
        {$categoryContext}tionCount} questions from the provided content.
        {$categoryContext} Difficulty: {$difficulty}.
        Question types to include: {$typesList}.

        Return ONLY a valid JSON array. Each item must follow this schema based on its type:

        For "multiple_choice":
        {
            "type": "multiple_choice",
            "question": "the question text",
            "options": ["Option A", "Option B", "Option C", "Option D"],
            "correct_answer": "the exact correct option text",
            "explanation": "brief explanation (1-2 sentences)",
            "points": 1,
            "section_label": "Section A: Objectives"
        }

        For "true_false":
        {
            "type": "true_false",
            "question": "statement to evaluate",
            "options": ["True", "False"],
            "correct_answer": "True" or "False",
            "explanation": "brief explanation",
            "points": 1,
            "section_label": "Section A: Objectives"
        }

        For "fill_blank":
        {
            "type": "fill_blank",
            "question": "The capital of Nigeria is ___.",
            "options": [],
            "correct_answer": "Abuja",
            "explanation": "brief explanation",
            "points": 1,
            "section_label": "Section A: Objectives"
        }

        For "short_answer":
        {
            "type": "short_answer",
            "question": "Explain briefly...",
            "options": [],
            "correct_answer": null,
            "sample_answer": "A concise expected answer (2-3 sentences)",
            "marking_guide": "Award marks for: mentioning X (1 mark), explaining Y (1 mark), giving example (1 mark)",
            "explanation": null,
            "points": 3,
            "section_label": "Section B: Theory"
        }

        For "theory":
        {
            "type": "theory",
            "question": "Discuss in detail...",
            "options": [],
            "correct_answer": null,
            "sample_answer": "A comprehensive model answer covering all key points...",
            "marking_guide": "Introduction (2 marks): ...\nBody (6 marks): ...\nConclusion (2 marks): ...\nTotal: 10 marks",
            "min_words": 100,
            "max_words": 500,
            "explanation": null,
            "points": 10,
            "section_label": "Section C: Theory"
        }

        For "matching":
        {
            "type": "matching",
            "question": "Match the terms in Column A with their definitions in Column B",
            "options": [
                {"left": "Photosynthesis", "right": "Process of making food using sunlight"},
                {"left": "Respiration", "right": "Process of breaking down food for energy"},
                {"left": "Osmosis", "right": "Movement of water through a membrane"},
                {"left": "Diffusion", "right": "Movement of particles from high to low concentration"}
            ],
            "correct_answer": null,
            "explanation": "Each term is matched with its correct scientific definition",
            "points": 4,
            "section_label": "Section A: Objectives"
        }

        Rules:
        - Questions must be age-appropriate for {$classLevel}
        - Use simple, clear language
        - For objective questions (MCQ, T/F, fill blank): make wrong options plausible but clearly incorrect
        - For theory questions (short answer, theory): provide detailed marking guides with point allocation
        - For theory questions: set reasonable word limits based on the difficulty level
        - For matching: provide 4-6 pairs with shuffled right-column items
        - Group questions by section labels (Section A for objectives, Section B for short answers, Section C for theory)
        - Cover different parts of the content
        - Do NOT repeat questions
        - Points should reflect the complexity: MCQ/TF=1, fill_blank=1-2, short_answer=2-5, theory=5-20, matching=number of pairs
        PROMPT;

        return $this->callGemini($systemPrompt, $content);
    }

    /**
     * Generate exam questions from a free-form prompt.
     */
    public function generateFromPrompt(
        string $prompt,
        string $classLevel,
        string $subjectName,
        int $questionCount = 10,
        array $questionTypes = ['multiple_choice'],
        string $category = 'exam',
    ): array {
        return $this->generateFromContent(
            content: "Topic/Prompt: {$prompt}",
            classLevel: $classLevel,
            subjectName: $subjectName,
            questionCount: $questionCount,
            questionTypes: $questionTypes,
            difficulty: $difficulty,
            category: $category,
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
                    ['text' => 'Extract all the text content from this document. Return only the text, preserving paragraph structure. No formatting commentary.'],
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
     * Extract text from any URL (web page, Google Docs, etc.) using Gemini.
     *
     * Fetches the URL content and sends it to Gemini for text extraction.
     */
    public function extractTextFromUrl(string $url): string
    {
        $this->lastError = null;

        // Try to fetch the page content via HTTP
        try {
            $httpResponse = Http::timeout(30)
                ->withHeaders([
                    'User-Agent' => 'Mozilla/5.0 (compatible; SchoolPortalBot/1.0)',
                    'Accept' => 'text/html,application/xhtml+xml,application/xml;q=0.9,*/*;q=0.8',
                ])
                ->get($url);

            if (! $httpResponse->successful()) {
                $this->lastError = __('Could not fetch content from the URL. Please check the link and try again.');
                Log::warning('Failed to fetch URL for text extraction', ['url' => $url, 'status' => $httpResponse->status()]);

                return '';
            }

            $body = $httpResponse->body();

            // If the response is too large, truncate (Gemini has token limits)
            if (strlen($body) > 500000) {
                $body = substr($body, 0, 500000);
            }
        } catch (\Exception $e) {
            $this->lastError = __('Could not fetch content from the URL. Please check the link and try again.');
            Log::warning('Exception fetching URL for text extraction', ['url' => $url, 'error' => $e->getMessage()]);

            return '';
        }

        // Send the fetched content to Gemini for intelligent text extraction
        $apiUrl = "{$this->baseUrl}/models/{$this->model}:generateContent?key={$this->apiKey}";

        $response = $this->postWithRetry($apiUrl, [
            'contents' => [[
                'parts' => [
                    ['text' => "Extract all educational text content from the following web page or document. Remove navigation menus, headers, footers, ads, and other non-content elements. Return only the main educational text, preserving paragraph structure. No formatting commentary.\n\nURL: {$url}\n\nPage Content:\n{$body}"],
                ],
            ]],
            'generationConfig' => [
                'temperature' => 0.1,
                'maxOutputTokens' => 8192,
            ],
        ], 'URL text extraction');

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
                'maxOutputTokens' => 16384, // larger for theory marking guides
                'responseMimeType' => 'application/json',
            ],
        ], 'exam generation');

        if ($response === null) {
            return [];
        }

        $text = $response->json('candidates.0.content.parts.0.text', '[]');
        $parsed = json_decode($text, true);

        if (! is_array($parsed)) {
            Log::warning('Gemini exam generation returned non-array response', ['text' => $text]);
            $this->lastError = __('AI returned an unexpected response. Please try again.');

            return [];
        }

        // Handle wrapper objects (Gemini sometimes wraps arrays in an object)
        if (! array_is_list($parsed) && count($parsed) === 1) {
            $inner = reset($parsed);
            if (is_array($inner) && array_is_list($inner)) {
                return $inner;
            }
        }

        return array_is_list($parsed) ? $parsed : [];
    }

    /**
     * POST to Gemini with exponential backoff on transient failures.
     */
    private function postWithRetry(string $url, array $payload, string $context): ?Response
    {
        $attempts = 3;
        $delayMs = 1000;

        for ($i = 1; $i <= $attempts; $i++) {
            try {
                $response = Http::timeout(120)->post($url, $payload);
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
