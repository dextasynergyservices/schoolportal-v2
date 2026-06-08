<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\GradingScale;
use App\Models\GradingScaleItem;
use App\Models\ReportCardConfig;
use App\Models\SchoolLevel;
use App\Models\ScoreComponent;
use App\Services\FileUploadService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class GradingController extends Controller
{
    /**
     * Main grading settings page — tabs for grading scales, score components, report card config.
     */
    public function index(): View
    {
        $school = app('current.school');

        $gradingScales = GradingScale::with(['items', 'levels'])->orderBy('name')->get();
        $levels = SchoolLevel::where('is_active', true)->orderBy('sort_order')->get();
        $levelAssignments = DB::table('grading_scale_level')
            ->join('grading_scales', 'grading_scale_level.grading_scale_id', '=', 'grading_scales.id')
            ->where('grading_scale_level.school_id', $school->id)
            ->select('grading_scale_level.level_id', 'grading_scale_level.grading_scale_id', 'grading_scales.name as scale_name')
            ->get()
            ->keyBy('level_id');
        $scoreComponents = ScoreComponent::orderBy('sort_order')->get();
        $reportCardConfig = ReportCardConfig::firstOrCreate(
            ['school_id' => $school->id],
            [
                'psychomotor_traits' => ['Handwriting', 'Verbal Fluency', 'Sports', 'Drawing & Painting', 'Musical Skills', 'Crafts'],
                'affective_traits' => ['Punctuality', 'Neatness', 'Honesty', 'Politeness', 'Obedience', 'Teamwork', 'Attentiveness', 'Self-Control'],
                'trait_rating_scale' => [
                    ['value' => 5, 'label' => 'Excellent'],
                    ['value' => 4, 'label' => 'Very Good'],
                    ['value' => 3, 'label' => 'Good'],
                    ['value' => 2, 'label' => 'Fair'],
                    ['value' => 1, 'label' => 'Poor'],
                ],
                'comment_presets' => [
                    'excellent' => ['Excellent performance. Keep it up!', 'Outstanding result. Well done!', 'A brilliant performance. Maintain this standard.'],
                    'good' => ['Good performance. You can do better.', 'A commendable effort. Keep improving.', 'Very good result. Aim higher next term.'],
                    'average' => ['Fair performance. Put in more effort.', 'Average result. More hard work is needed.', 'You need to improve. Work harder.'],
                    'poor' => ['Below average. Serious improvement is needed.', 'Poor performance. You must do better.', 'Very poor result. Seek extra help.'],
                ],
            ]
        );

        $totalWeight = $scoreComponents->sum('weight');

        return view('admin.grading.index', compact('gradingScales', 'levels', 'levelAssignments', 'scoreComponents', 'reportCardConfig', 'totalWeight'));
    }

    // ── Grading Scales ──

    public function createScale(): View
    {
        $school = app('current.school');
        $levels = SchoolLevel::where('is_active', true)->orderBy('sort_order')->get();
        $levelAssignments = $this->levelAssignmentMap($school->id);

        return view('admin.grading.scales.create', compact('levels', 'levelAssignments'));
    }

    public function storeScale(Request $request): RedirectResponse
    {
        $school = app('current.school');
        $validated = $this->validateScalePayload($request, $school->id);

        DB::transaction(function () use ($validated, $school) {
            $makeDefault = ! empty($validated['is_default'])
                || ! GradingScale::where('school_id', $school->id)->where('is_default', true)->exists();

            // If setting as default, unset other defaults
            if ($makeDefault) {
                GradingScale::where('school_id', $school->id)
                    ->where('is_default', true)
                    ->update(['is_default' => false]);
            }

            $scale = GradingScale::create([
                'school_id' => $school->id,
                'name' => $validated['name'],
                'is_default' => $makeDefault,
            ]);

            foreach ($validated['items'] as $index => $item) {
                GradingScaleItem::create([
                    'grading_scale_id' => $scale->id,
                    'school_id' => $school->id,
                    'grade' => $item['grade'],
                    'label' => $item['label'],
                    'min_score' => $item['min_score'],
                    'max_score' => $item['max_score'],
                    'sort_order' => $index,
                ]);
            }

            $this->syncScaleLevels($scale, $validated['level_ids'] ?? [], $school->id);
        });

        return redirect()->route('admin.grading.index')
            ->with('success', __('Grading scale ":name" created.', ['name' => $validated['name']]));
    }

    public function editScale(GradingScale $scale): View
    {
        $school = app('current.school');
        $scale->load(['items', 'levels']);
        $levels = SchoolLevel::where('is_active', true)->orderBy('sort_order')->get();
        $levelAssignments = $this->levelAssignmentMap($school->id);
        $selectedLevelIds = $scale->levels->pluck('id')->all();

        return view('admin.grading.scales.edit', compact('scale', 'levels', 'levelAssignments', 'selectedLevelIds'));
    }

    public function updateScale(Request $request, GradingScale $scale): RedirectResponse
    {
        $school = app('current.school');
        $validated = $this->validateScalePayload($request, $school->id);

        DB::transaction(function () use ($validated, $scale, $school) {
            $makeDefault = $scale->is_default || ! empty($validated['is_default'])
                || ! GradingScale::where('school_id', $school->id)
                    ->where('is_default', true)
                    ->where('id', '!=', $scale->id)
                    ->exists();

            if ($makeDefault) {
                GradingScale::where('school_id', $school->id)
                    ->where('is_default', true)
                    ->where('id', '!=', $scale->id)
                    ->update(['is_default' => false]);
            }

            $scale->update([
                'name' => $validated['name'],
                'is_default' => $makeDefault,
            ]);

            // Replace all items
            $scale->items()->delete();
            foreach ($validated['items'] as $index => $item) {
                GradingScaleItem::create([
                    'grading_scale_id' => $scale->id,
                    'school_id' => $school->id,
                    'grade' => $item['grade'],
                    'label' => $item['label'],
                    'min_score' => $item['min_score'],
                    'max_score' => $item['max_score'],
                    'sort_order' => $index,
                ]);
            }

            $this->syncScaleLevels($scale, $validated['level_ids'] ?? [], $school->id);
        });

        return redirect()->route('admin.grading.index')
            ->with('success', __('Grading scale updated.'));
    }

    public function destroyScale(GradingScale $scale): RedirectResponse
    {
        if ($scale->is_default) {
            return redirect()->route('admin.grading.index', ['tab' => 'scales'])
                ->with('error', __('The default grading scale cannot be deleted.'));
        }

        if ($scale->levels()->exists()) {
            return redirect()->route('admin.grading.index', ['tab' => 'scales'])
                ->with('error', __('Remove assigned levels from this grading scale before deleting it.'));
        }

        $scale->delete();

        return redirect()->route('admin.grading.index')
            ->with('success', __('Grading scale deleted.'));
    }

    public function makeDefaultScale(GradingScale $scale): RedirectResponse
    {
        DB::transaction(function () use ($scale) {
            GradingScale::where('school_id', $scale->school_id)
                ->where('is_default', true)
                ->where('id', '!=', $scale->id)
                ->update(['is_default' => false]);

            $scale->update([
                'is_default' => true,
                'is_active' => true,
            ]);
        });

        return redirect()->route('admin.grading.index', ['tab' => 'scales'])
            ->with('success', __('":name" is now the school default grading scale.', ['name' => $scale->name]));
    }

    public function assignScaleLevels(Request $request, GradingScale $scale): RedirectResponse
    {
        $school = app('current.school');

        $validated = $request->validate([
            'level_ids' => ['nullable', 'array'],
            'level_ids.*' => ['integer', 'distinct', Rule::exists('school_levels', 'id')->where('school_id', $school->id)],
        ]);

        DB::transaction(function () use ($scale, $validated, $school) {
            $this->syncScaleLevels($scale, $validated['level_ids'] ?? [], $school->id);
        });

        return redirect()->route('admin.grading.index', ['tab' => 'scales'])
            ->with('success', __('Level assignments saved for ":name".', ['name' => $scale->name]));
    }

    // ── Score Components ──

    public function storeComponents(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'components' => ['required', 'array', 'min:1'],
            'components.*.name' => ['required', 'string', 'max:50'],
            'components.*.short_name' => ['required', 'string', 'max:10'],
            'components.*.max_score' => ['required', 'integer', 'min:1', 'max:100'],
            'components.*.weight' => ['required', 'integer', 'min:1', 'max:100'],
            'components.*.include_in_midterm' => ['nullable', 'boolean'],
        ]);

        // Validate weights sum to 100
        $totalWeight = collect($validated['components'])->sum('weight');
        if ($totalWeight !== 100) {
            return back()->withErrors(['components' => __('Score component weights must sum to exactly 100%. Currently: :total%', ['total' => $totalWeight])])->withInput();
        }

        $school = app('current.school');

        DB::transaction(function () use ($validated, $school) {
            // Replace all components
            ScoreComponent::where('school_id', $school->id)->delete();

            foreach ($validated['components'] as $index => $component) {
                ScoreComponent::create([
                    'school_id' => $school->id,
                    'name' => $component['name'],
                    'short_name' => $component['short_name'],
                    'max_score' => $component['max_score'],
                    'weight' => $component['weight'],
                    'include_in_midterm' => ! empty($component['include_in_midterm']),
                    'sort_order' => $index,
                ]);
            }
        });

        return redirect()->route('admin.grading.index')
            ->with('success', __('Score components saved.'));
    }

    // ── Report Card Config ──

    public function updateReportCard(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'psychomotor_traits' => ['nullable', 'array'],
            'psychomotor_traits.*' => ['string', 'max:50'],
            'affective_traits' => ['nullable', 'array'],
            'affective_traits.*' => ['string', 'max:50'],
            'trait_rating_scale' => ['nullable', 'array', 'min:2'],
            'trait_rating_scale.*.value' => ['required', 'integer', 'min:1', 'max:10'],
            'trait_rating_scale.*.label' => ['required', 'string', 'max:30'],
            'comment_presets' => ['nullable', 'array'],
            'show_position' => ['boolean'],
            'show_class_average' => ['boolean'],
            'show_subject_teacher' => ['boolean'],
            'show_grade_summary' => ['boolean'],
            'require_class_teacher_comment' => ['boolean'],
            'require_principal_comment' => ['boolean'],
            'principal_title' => ['nullable', 'string', 'max:50'],
            'principal_signature' => ['nullable', 'image', 'mimes:png,jpg,jpeg,webp', 'max:2048'],
            'school_stamp' => ['nullable', 'image', 'mimes:png,jpg,jpeg,webp', 'max:2048'],
            'remove_signature' => ['boolean'],
            'remove_stamp' => ['boolean'],
            'enabled_report_types' => ['required', 'array', 'min:1'],
            'enabled_report_types.*' => ['string', 'in:midterm,full_term,session'],
            'session_calculation_method' => ['nullable', 'string', 'in:average_of_terms,weighted_average,best_two_of_three'],
            'midterm_weight' => ['nullable', 'numeric', 'min:0', 'max:100'],
            'fullterm_weight' => ['nullable', 'numeric', 'min:0', 'max:100'],
            'show_term_breakdown_in_session' => ['boolean'],
        ]);

        // If session report is enabled, session_calculation_method is required
        if (in_array('session', $validated['enabled_report_types'] ?? []) && empty($validated['session_calculation_method'])) {
            return back()->withErrors(['session_calculation_method' => __('Session calculation method is required when Session Report is enabled.')])->withInput();
        }

        // If weighted method is selected, validate weights sum to 100
        if (($validated['session_calculation_method'] ?? null) === 'weighted_average') {
            $midWeight = (float) ($validated['midterm_weight'] ?? 0);
            $fullWeight = (float) ($validated['fullterm_weight'] ?? 0);
            if (abs($midWeight + $fullWeight - 100) > 0.01) {
                return back()->withErrors(['midterm_weight' => __('Term weights must sum to 100%. Currently: :total%', ['total' => $midWeight + $fullWeight])])->withInput();
            }
        }

        // If midterm report is enabled, at least 2 components must have include_in_midterm
        if (in_array('midterm', $validated['enabled_report_types'] ?? [])) {
            $midtermCount = ScoreComponent::where('include_in_midterm', true)->count();
            if ($midtermCount < 2) {
                return back()->withErrors(['enabled_report_types' => __('At least 2 score components must have "Include in Mid-Term" enabled to use Mid-Term Reports. Currently: :count', ['count' => $midtermCount])])->withInput();
            }
        }

        $school = app('current.school');
        $fileUploader = app(FileUploadService::class);

        // Filter out empty trait values
        if (isset($validated['psychomotor_traits'])) {
            $validated['psychomotor_traits'] = array_values(array_filter($validated['psychomotor_traits']));
        }
        if (isset($validated['affective_traits'])) {
            $validated['affective_traits'] = array_values(array_filter($validated['affective_traits']));
        }

        // Remove file fields from validated data (handled separately)
        unset($validated['principal_signature'], $validated['school_stamp'], $validated['remove_signature'], $validated['remove_stamp']);

        $config = ReportCardConfig::firstOrCreate(['school_id' => $school->id]);

        // Handle principal signature upload
        if ($request->hasFile('principal_signature')) {
            // Delete old signature if exists
            if ($config->principal_signature_public_id) {
                $fileUploader->delete($config->principal_signature_public_id);
            }
            $result = $fileUploader->uploadSchoolLogo($request->file('principal_signature'), $school->id);
            $validated['principal_signature_url'] = $result['url'];
            $validated['principal_signature_public_id'] = $result['public_id'];
        } elseif ($request->boolean('remove_signature') && $config->principal_signature_public_id) {
            $fileUploader->delete($config->principal_signature_public_id);
            $validated['principal_signature_url'] = null;
            $validated['principal_signature_public_id'] = null;
        }

        // Handle school stamp upload
        if ($request->hasFile('school_stamp')) {
            if ($config->school_stamp_public_id) {
                $fileUploader->delete($config->school_stamp_public_id);
            }
            $result = $fileUploader->uploadSchoolLogo($request->file('school_stamp'), $school->id);
            $validated['school_stamp_url'] = $result['url'];
            $validated['school_stamp_public_id'] = $result['public_id'];
        } elseif ($request->boolean('remove_stamp') && $config->school_stamp_public_id) {
            $fileUploader->delete($config->school_stamp_public_id);
            $validated['school_stamp_url'] = null;
            $validated['school_stamp_public_id'] = null;
        }

        // Checkboxes/switches are absent from the request when unchecked,
        // so explicitly set boolean toggles to false if not submitted.
        $booleanToggles = [
            'show_position',
            'show_class_average',
            'show_grade_summary',
            'show_subject_teacher',
            'require_class_teacher_comment',
            'require_principal_comment',
            'show_term_breakdown_in_session',
        ];

        foreach ($booleanToggles as $toggle) {
            if (! array_key_exists($toggle, $validated)) {
                $validated[$toggle] = false;
            }
        }

        $config->update($validated);

        return redirect()->route('admin.grading.index')
            ->with('success', __('Report card configuration saved.'));
    }

    /**
     * @param  array<int|string>  $levelIds
     */
    private function syncScaleLevels(GradingScale $scale, array $levelIds, int $schoolId): void
    {
        $levelIds = SchoolLevel::where('school_id', $schoolId)
            ->whereIn('id', array_unique(array_map('intval', $levelIds)))
            ->pluck('id')
            ->all();

        DB::table('grading_scale_level')
            ->where('school_id', $schoolId)
            ->where('grading_scale_id', $scale->id)
            ->delete();

        if ($levelIds === []) {
            return;
        }

        DB::table('grading_scale_level')
            ->where('school_id', $schoolId)
            ->whereIn('level_id', $levelIds)
            ->delete();

        DB::table('grading_scale_level')->insert(
            collect($levelIds)
                ->map(fn (int $levelId): array => [
                    'school_id' => $schoolId,
                    'grading_scale_id' => $scale->id,
                    'level_id' => $levelId,
                    'created_at' => now(),
                ])
                ->all()
        );
    }

    private function levelAssignmentMap(int $schoolId): Collection
    {
        return DB::table('grading_scale_level')
            ->join('grading_scales', 'grading_scale_level.grading_scale_id', '=', 'grading_scales.id')
            ->where('grading_scale_level.school_id', $schoolId)
            ->select('grading_scale_level.level_id', 'grading_scale_level.grading_scale_id', 'grading_scales.name as scale_name')
            ->get()
            ->keyBy('level_id');
    }

    /**
     * @return array{name: string, is_default?: bool, items: array<int, array{grade: string, label: string, min_score: int, max_score: int}>, level_ids?: array<int|string>}
     */
    private function validateScalePayload(Request $request, int $schoolId): array
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:100'],
            'is_default' => ['boolean'],
            'items' => ['required', 'array', 'min:1'],
            'items.*.grade' => ['required', 'string', 'max:5'],
            'items.*.label' => ['required', 'string', 'max:50'],
            'items.*.min_score' => ['required', 'integer', 'min:0', 'max:100'],
            'items.*.max_score' => ['required', 'integer', 'min:0', 'max:100'],
            'level_ids' => ['nullable', 'array'],
            'level_ids.*' => ['integer', 'distinct', Rule::exists('school_levels', 'id')->where('school_id', $schoolId)],
        ]);

        $this->validateGradeBands($validated['items']);

        return $validated;
    }

    /**
     * @param  array<int, array{grade: string, label: string, min_score: int, max_score: int}>  $items
     *
     * @throws ValidationException
     */
    private function validateGradeBands(array $items): void
    {
        $errors = [];
        $seenGrades = [];
        $bands = [];

        foreach ($items as $index => $item) {
            $grade = trim((string) $item['grade']);
            $gradeKey = mb_strtolower($grade);
            $minScore = (int) $item['min_score'];
            $maxScore = (int) $item['max_score'];

            if ($minScore > $maxScore) {
                $errors["items.{$index}.min_score"] = __('The minimum score cannot be greater than the maximum score.');
            }

            if (isset($seenGrades[$gradeKey])) {
                $errors["items.{$index}.grade"] = __('The grade label ":grade" is already used in this scale.', ['grade' => $grade]);
            }

            $seenGrades[$gradeKey] = true;
            $bands[] = [
                'index' => $index,
                'grade' => $grade,
                'min_score' => $minScore,
                'max_score' => $maxScore,
            ];
        }

        usort($bands, fn (array $left, array $right): int => $left['min_score'] <=> $right['min_score']);

        $expectedMin = 0;
        $previousBand = null;

        foreach ($bands as $band) {
            if ($previousBand !== null && $band['min_score'] <= $previousBand['max_score']) {
                $errors["items.{$band['index']}.min_score"] = __('The range :range overlaps with :grade. Please adjust the scores.', [
                    'range' => "{$band['min_score']}-{$band['max_score']}",
                    'grade' => $previousBand['grade'],
                ]);
            }

            if ($band['min_score'] > $expectedMin) {
                $missingEnd = $band['min_score'] - 1;
                $errors['items'] = __('Score ranges must cover every score from 0 to 100. Missing range: :range.', [
                    'range' => $expectedMin === $missingEnd ? (string) $expectedMin : "{$expectedMin}-{$missingEnd}",
                ]);
                break;
            }

            $expectedMin = max($expectedMin, $band['max_score'] + 1);
            $previousBand = $band;
        }

        if (! isset($errors['items']) && $expectedMin <= 100) {
            $missingEnd = 100;
            $errors['items'] = __('Score ranges must cover every score from 0 to 100. Missing range: :range.', [
                'range' => $expectedMin === $missingEnd ? (string) $expectedMin : "{$expectedMin}-{$missingEnd}",
            ]);
        }

        if ($errors !== []) {
            throw ValidationException::withMessages($errors);
        }
    }
}
