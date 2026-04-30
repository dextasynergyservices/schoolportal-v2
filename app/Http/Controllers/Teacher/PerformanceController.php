<?php

declare(strict_types=1);

namespace App\Http\Controllers\Teacher;

use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class PerformanceController extends Controller
{
    public function subjects(Request $request): View
    {
        $defaultClassId = auth()->user()->assignedClasses()->first()?->id;
        $tab = $request->query('tab', 'subjects');

        return view('teacher.performance.subjects', compact('defaultClassId', 'tab'));
    }

    public function students(): RedirectResponse
    {
        return redirect()->route('teacher.performance.subjects', ['tab' => 'students']);
    }
}
