<?php

declare(strict_types=1);

namespace App\Livewire\Admin;

use App\Models\AcademicSession;
use App\Models\Assignment;
use App\Models\Result;
use App\Models\School;
use App\Models\SchoolClass;
use App\Models\Term;
use App\Models\User;
use App\Services\AiCreditService;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\View\View;
use Livewire\Component;

class AiNarrative extends Component
{
    public ?int $sessionId = null;

    public ?int $termId = null;

    public bool $generating = false;

    public string $error = '';

    // ── Cached narrative loaded from school settings ──────────────────
    public ?array $narrative = null;

    public ?string $generatedAt = null;

    public int $creditsAvailable = 0;

    public function mount(?int $sessionId, ?int $termId): void
    {
        $this->sessionId = $sessionId;
        $this->termId = $termId;
        $this->loadCached();
        $this->creditsAvailable = app(AiCreditService::class)->getSchoolBalance(app('current.school'));
    }

    public function loadCached(): void
    {
        $school = app('current.school');
        $key = "ai_narrative.{$this->sessionId}.{$this->termId}";
        $cached = $school->setting($key);

        if (is_array($cached) && isset($cached['narrative'])) {
            $this->narrative = $cached['narrative'];
            $this->generatedAt = $cached['generated_at'] ?? null;
        } else {
            $this->narrative = null;
            $this->generatedAt = null;
        }
    }

    public function generate(): void
    {
        $this->error = '';
        $school = app('current.school');
        $creditService = app(AiCreditService::class);

        if (! $creditService->hasCredits($school)) {
            $this->error = __('No AI credits remaining. Purchase more credits or wait for the free monthly reset.');

            return;
        }

        if (! $this->sessionId || ! $this->termId) {
            $this->error = __('No active session/term — set one up in Academic Sessions first.');

            return;
        }

        $this->generating = true;

        try {
            $data = $this->gatherData($school);
            $json = $this->callGemini($school, $data);

            $settings = $school->settings ?? [];
            data_set($settings, "ai_narrative.{$this->sessionId}.{$this->termId}", [
                'narrative' => $json,
                'generated_at' => now()->toIso8601String(),
            ]);
            $school->settings = $settings;
            $school->save();

            // Deduct 1 credit
            $creditService->deductCredit($school, auth()->user(), 'analytics');

            $this->narrative = $json;
            $this->generatedAt = now()->toIso8601String();
            $this->creditsAvailable = $creditService->getSchoolBalance($school);
        } catch (\Throwable $e) {
            $this->error = $e->getMessage();
            Log::error('AiNarrative generation failed', [
                'school_id' => $school->id,
                'error' => $e->getMessage(),
            ]);
        } finally {
            $this->generating = false;
        }
    }

    /**
     * Gather analytics data for both the current term and the previous term (for comparison).
     *
     * @return array<string, mixed>
     */
    private function gatherData(School $school): array
    {
        $session = AcademicSession::find($this->sessionId);
        $term = Term::find($this->termId);

        $totalStudents = User::where('role', 'student')->where('is_active', true)->count();

        // Current-term result coverage
        $resultsApproved = Result::where('session_id', $this->sessionId)
            ->where('term_id', $this->termId)
            ->where('status', 'approved')
            ->count();

        $coveragePct = $totalStudents > 0 ? round($resultsApproved / $totalStudents * 100) : 0;

        // Assignment coverage (classes with at least 1 approved assignment this term)
        $weeksPerTerm = $school->setting('academic.weeks_per_term', 12);
        $classes = SchoolClass::where('is_active', true)
            ->with('level:id,name')
            ->orderBy('sort_order')
            ->get(['id', 'name', 'level_id']);

        $assignmentsByClass = Assignment::where('session_id', $this->sessionId)
            ->where('term_id', $this->termId)
            ->where('status', 'approved')
            ->selectRaw('class_id, count(*) as uploaded')
            ->groupBy('class_id')
            ->pluck('uploaded', 'class_id');

        $classAssignmentData = $classes->map(fn ($c) => [
            'name' => $c->name,
            'level' => $c->level?->name ?? '',
            'weeks_uploaded' => (int) ($assignmentsByClass[$c->id] ?? 0),
            'weeks_total' => (int) $weeksPerTerm,
        ])->values()->toArray();

        // Previous term for comparison
        $prevTermData = null;
        if ($term) {
            $prevTerm = Term::where('session_id', $this->sessionId)
                ->where('term_number', $term->term_number - 1)
                ->first();

            // If term_number is 1, look at term 3 of previous session
            if (! $prevTerm && $session) {
                $prevSession = AcademicSession::where('school_id', $school->id)
                    ->where('start_date', '<', $session->start_date)
                    ->orderByDesc('start_date')
                    ->first();

                if ($prevSession) {
                    $prevTerm = $prevSession->terms()
                        ->orderByDesc('term_number')
                        ->first();
                }
            }

            if ($prevTerm) {
                $prevResults = Result::where('term_id', $prevTerm->id)
                    ->where('status', 'approved')
                    ->count();
                $prevCoverage = $totalStudents > 0 ? round($prevResults / $totalStudents * 100) : 0;

                $prevTermData = [
                    'term_name' => $prevTerm->name,
                    'session_name' => $prevTerm->session?->name ?? '',
                    'results_coverage_pct' => $prevCoverage,
                    'assignments_uploaded' => (int) Assignment::where('term_id', $prevTerm->id)
                        ->where('status', 'approved')
                        ->count(),
                ];
            }
        }

        // Staff activity — teachers who uploaded at least one result or assignment this term
        $teacherCount = User::where('role', 'teacher')->where('is_active', true)->count();
        $activeViaResults = Result::where('term_id', $this->termId)
            ->whereNotNull('uploaded_by')
            ->distinct()
            ->pluck('uploaded_by');
        $activeViaAssignments = Assignment::where('term_id', $this->termId)
            ->whereNotNull('uploaded_by')
            ->distinct()
            ->pluck('uploaded_by');
        $teachersActiveThisTerm = $activeViaResults->merge($activeViaAssignments)->unique()->count();

        return [
            'school_name' => $school->name,
            'session_name' => $session?->name ?? 'Unknown Session',
            'term_name' => $term?->name ?? 'Unknown Term',
            'total_students' => $totalStudents,
            'total_classes' => $classes->count(),
            'total_teachers' => $teacherCount,
            'teachers_active_this_term' => $teachersActiveThisTerm,
            'results_approved' => $resultsApproved,
            'results_coverage_pct' => $coveragePct,
            'total_assignments_uploaded' => array_sum(array_column($classAssignmentData, 'weeks_uploaded')),
            'classes' => $classAssignmentData,
            'previous_term' => $prevTermData,
        ];
    }

    /**
     * Call the Gemini API with a structured prompt. Returns decoded JSON array or null.
     *
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>|null
     *
     * @throws \RuntimeException on API failure — caught by generate() try/catch
     */
    private function callGemini(School $school, array $data): ?array
    {
        $apiKey = config('services.gemini.api_key');
        if (! $apiKey) {
            throw new \RuntimeException('GEMINI_API_KEY is not set in .env');
        }

        $baseUrl = config('services.gemini.base_url', 'https://generativelanguage.googleapis.com/v1beta');
        $model = config('services.gemini.model', 'gemini-2.5-flash');
        $url = "{$baseUrl}/models/{$model}:generateContent?key={$apiKey}";

        $dataJson = json_encode($data, JSON_PRETTY_PRINT);

        $systemPrompt = <<<'PROMPT'
        You are an academic performance analyst writing for the school administrator of a Nigerian primary or nursery school.
        Your audience is a non-technical school admin who needs clear, actionable insights — not just a restatement of numbers.

        Analyse the provided term data and return ONLY a valid JSON object with exactly these keys:

        - "headline": string — A single compelling sentence summarising the most important thing about this term (max 12 words). Be specific, not generic.
        - "executive_summary": string — 2-3 sentences giving an honest overall picture. Compare to the previous term if the data is provided. Use plain English, no jargon.
        - "strengths": array of strings — 2-3 specific positives backed by the data. Quote numbers. Mention class or level names where relevant.
        - "concerns": array of objects with keys "area" (string), "detail" (string), "severity" ("high"|"medium"|"low") — 2-3 actionable issues the admin should address. Be direct. If assignment coverage is low, say which class. If results are missing, say how many students.
        - "recommendations": array of strings — 2-3 concrete next steps the admin can take before or at the start of next term. Be practical.
        - "data_quality_note": string — One sentence noting result coverage (e.g. "Based on results for 183 of 245 students (75% coverage)"). If coverage is below 80%, flag this honestly.

        Rules:
        - Never invent data not present in the input.
        - If there is no previous-term data for comparison, do not pretend there is.
        - Use the Nigerian academic context: terms, sessions, class names (Nursery 1, Primary 3, etc.).
        - Do NOT include markdown, code fences, or any text outside the JSON object.
        PROMPT;

        $userMessage = "School analytics data for {$data['school_name']} — {$data['term_name']}, {$data['session_name']}:\n\n{$dataJson}";

        $payload = [
            'system_instruction' => ['parts' => [['text' => $systemPrompt]]],
            'contents' => [['parts' => [['text' => $userMessage]]]],
            'generationConfig' => [
                'temperature' => 0.4,
                'maxOutputTokens' => 4096,
                'responseMimeType' => 'application/json',
            ],
        ];

        // Retry up to 3 times on transient errors (503, 429, 5xx) — same as QuizGeneratorService
        $attempts = 3;
        $delayMs = 1500;
        $response = null;

        for ($i = 1; $i <= $attempts; $i++) {
            try {
                $response = Http::timeout(90)->post($url, $payload);
            } catch (\Throwable $e) {
                Log::error('AiNarrative: HTTP exception', [
                    'school_id' => $school->id,
                    'attempt' => $i,
                    'error' => $e->getMessage(),
                ]);
                if ($i === $attempts) {
                    throw new \RuntimeException('Could not reach the AI service. Please check your connection and try again.');
                }
                usleep($delayMs * 1000);
                $delayMs *= 2;

                continue;
            }

            if ($response->successful()) {
                break;
            }

            $status = $response->status();
            $retriable = in_array($status, [429, 500, 502, 503, 504], true);

            Log::error('AiNarrative: Gemini API error', [
                'school_id' => $school->id,
                'attempt' => $i,
                'status' => $status,
                'body' => $response->body(),
            ]);

            if (! $retriable || $i === $attempts) {
                $message = match (true) {
                    $status === 429 => 'AI rate limit reached. Please wait a moment and try again.',
                    $status === 503 => 'The AI service is temporarily overloaded. Please try again in a moment.',
                    $status >= 500 => 'The AI service is temporarily unavailable. Please try again.',
                    $status === 400 => 'The AI rejected the request (invalid prompt or config).',
                    $status === 401 || $status === 403 => 'AI service authentication failed — check GEMINI_API_KEY.',
                    default => "AI request failed with status {$status}.",
                };
                throw new \RuntimeException($message);
            }

            usleep($delayMs * 1000);
            $delayMs *= 2;
        }

        $text = $response->json('candidates.0.content.parts.0.text', '');

        if (! $text) {
            Log::error('AiNarrative: empty text in Gemini response', [
                'school_id' => $school->id,
                'body' => $response->body(),
            ]);
            throw new \RuntimeException('The AI returned an empty response. Please try again.');
        }

        $decoded = json_decode($text, true);

        if (! is_array($decoded) || ! isset($decoded['headline'], $decoded['executive_summary'])) {
            Log::warning('AiNarrative: unexpected JSON structure', [
                'school_id' => $school->id,
                'text' => $text,
            ]);
            throw new \RuntimeException('The AI returned an unexpected format. Please try again.');
        }

        return $decoded;
    }

    public function render(): View
    {
        return view('livewire.admin.ai-narrative');
    }
}
