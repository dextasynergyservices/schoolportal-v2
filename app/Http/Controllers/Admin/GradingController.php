<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\GradingScale;
use App\Models\GradingScaleItem;
use App\Models\ReportCardConfig;
use App\Models\ScoreComponent;
use App\Services\FileUploadService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class GradingController extends Controller
{
    /**
     * Main grading settings page — tabs for grading scales, score components, report card config.
     */
    public function index(): View
    {
        $school = app('current.school');

        $gradingScales = GradingScale::with('items')->orderBy('name')->get();
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

        return view('admin.grading.index', compact('gradingScales', 'scoreComponents', 'reportCardConfig', 'totalWeight'));
    }

    // ── Grading Scales ──

    public function createScale(): View
    {
        return view('admin.grading.scales.create');
    }

    public function storeScale(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:100'],
            'is_default' => ['boolean'],
            'items' => ['required', 'array', 'min:1'],
            'items.*.grade' => ['required', 'string', 'max:5'],
            'items.*.label' => ['required', 'string', 'max:50'],
            'items.*.min_score' => ['required', 'integer', 'min:0', 'max:100'],
            'items.*.max_score' => ['required', 'integer', 'min:0', 'max:100'],
        ]);

        $school = app('current.school');

        DB::transaction(function () use ($validated, $school) {
            // If setting as default, unset other defaults
            if (! empty($validated['is_default'])) {
                GradingScale::where('is_default', true)->update(['is_default' => false]);
            }

            $scale = GradingScale::create([
                'school_id' => $school->id,
                'name' => $validated['name'],
                'is_default' => $validated['is_default'] ?? false,
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
        });

        return redirect()->route('admin.grading.index')
            ->with('success', __('Grading scale ":name" created.', ['name' => $validated['name']]));
    }

    public function editScale(GradingScale $scale): View
    {
        $scale->load('items');

        return view('admin.grading.scales.edit', compact('scale'));
    }

    public function updateScale(Request $request, GradingScale $scale): RedirectResponse
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:100'],
            'is_default' => ['boolean'],
            'items' => ['required', 'array', 'min:1'],
            'items.*.grade' => ['required', 'string', 'max:5'],
            'items.*.label' => ['required', 'string', 'max:50'],
            'items.*.min_score' => ['required', 'integer', 'min:0', 'max:100'],
            'items.*.max_score' => ['required', 'integer', 'min:0', 'max:100'],
        ]);

        $school = app('current.school');

        DB::transaction(function () use ($validated, $scale, $school) {
            if (! empty($validated['is_default'])) {
                GradingScale::where('is_default', true)->where('id', '!=', $scale->id)->update(['is_default' => false]);
            }

            $scale->update([
                'name' => $validated['name'],
                'is_default' => $validated['is_default'] ?? false,
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
        });

        return redirect()->route('admin.grading.index')
            ->with('success', __('Grading scale updated.'));
    }

    public function destroyScale(GradingScale $scale): RedirectResponse
    {
        $scale->delete();

        return redirect()->route('admin.grading.index')
            ->with('success', __('Grading scale deleted.'));
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
}
