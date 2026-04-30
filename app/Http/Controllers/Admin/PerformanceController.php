<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class PerformanceController extends Controller
{
    public function subjects(Request $request): View
    {
        $tab = $request->query('tab', 'subjects');

        return view('admin.performance.subjects', compact('tab'));
    }

    public function students(): RedirectResponse
    {
        return redirect()->route('admin.performance.subjects', ['tab' => 'students']);
    }
}
