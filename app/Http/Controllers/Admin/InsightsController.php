<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;

class InsightsController extends Controller
{
    public function __invoke(): RedirectResponse
    {
        return redirect()->route('admin.analytics', ['tab' => 'insights']);
    }
}
