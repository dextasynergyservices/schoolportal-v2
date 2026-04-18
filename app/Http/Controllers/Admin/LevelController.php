<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\SchoolLevel;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\View\View;

class LevelController extends Controller
{
    public function index(): View
    {
        $levels = SchoolLevel::withCount('classes')
            ->orderBy('sort_order')
            ->get();

        return view('admin.levels.index', compact('levels'));
    }

    public function create(): View
    {
        return view('admin.levels.create');
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:50'],
            'sort_order' => ['nullable', 'integer', 'min:0'],
        ]);

        $validated['slug'] = Str::slug($validated['name']);

        SchoolLevel::create($validated);

        return redirect()->route('admin.levels.index')
            ->with('success', __('School level ":name" created.', ['name' => $validated['name']]));
    }

    public function edit(SchoolLevel $level): View
    {
        return view('admin.levels.edit', compact('level'));
    }

    public function update(Request $request, SchoolLevel $level): RedirectResponse
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:50'],
            'sort_order' => ['nullable', 'integer', 'min:0'],
            'is_active' => ['boolean'],
        ]);

        $validated['slug'] = Str::slug($validated['name']);

        $level->update($validated);

        return redirect()->route('admin.levels.index')
            ->with('success', __('School level updated.'));
    }

    public function destroy(SchoolLevel $level): RedirectResponse
    {
        if ($level->classes()->exists()) {
            return redirect()->route('admin.levels.index')
                ->with('error', __('Cannot delete a level that has classes. Remove classes first.'));
        }

        $level->delete();

        return redirect()->route('admin.levels.index')
            ->with('success', __('School level deleted.'));
    }
}
