<?php

declare(strict_types=1);

namespace App\Http\Responses;

use Illuminate\Http\Request;
use Laravel\Fortify\Contracts\LoginResponse as LoginResponseContract;
use Symfony\Component\HttpFoundation\Response;

class LoginResponse implements LoginResponseContract
{
    public function toResponse($request): Response
    {
        /** @var Request $request */
        $user = $request->user();

        $dashboard = match ($user?->role) {
            'super_admin' => '/portal/super-admin/dashboard',
            'school_admin' => '/portal/admin/dashboard',
            'teacher' => '/portal/teacher/dashboard',
            'student' => '/portal/student/dashboard',
            'parent' => '/portal/parent/dashboard',
            default => '/portal/dashboard',
        };

        return redirect()->intended($dashboard);
    }
}
