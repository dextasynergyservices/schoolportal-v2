<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\View\View;

class InsightsController extends Controller
{
    public function __invoke(): View
    {
        $school = app('current.school');
        $currentSession = $school->currentSession();
        $currentTerm = $school->currentTerm();

        return view('admin.insights', [
            'currentSession' => $currentSession,
            'currentTerm' => $currentTerm,
        ]);
    }
}
