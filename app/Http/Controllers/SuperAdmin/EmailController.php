<?php

declare(strict_types=1);

namespace App\Http\Controllers\SuperAdmin;

use App\Http\Controllers\Controller;
use App\Mail\PlatformEmail as PlatformEmailMailable;
use App\Models\PlatformEmail;
use App\Models\School;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Illuminate\View\View;

class EmailController extends Controller
{
    public function index(): View
    {
        $emails = PlatformEmail::with('sender')
            ->latest()
            ->paginate(15);

        return view('super-admin.emails.index', compact('emails'));
    }

    public function create(): View
    {
        $schools = School::tenants()
            ->where('is_active', true)
            ->orderBy('name')
            ->get(['id', 'name', 'email']);

        return view('super-admin.emails.create', compact('schools'));
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'subject' => ['required', 'string', 'max:255'],
            'body' => ['required', 'string'],
            'school_ids' => ['required', 'array', 'min:1'],
            'school_ids.*' => ['required', 'integer', 'exists:schools,id'],
        ]);

        $schools = School::whereIn('id', $data['school_ids'])
            ->whereNotNull('email')
            ->where('email', '!=', '')
            ->get();

        if ($schools->isEmpty()) {
            return back()->withInput()
                ->with('error', __('None of the selected schools have an email address.'));
        }

        $record = PlatformEmail::create([
            'subject' => $data['subject'],
            'body' => $data['body'],
            'recipient_school_ids' => $data['school_ids'],
            'total_recipients' => $schools->count(),
            'sent_by' => auth()->id(),
            'sent_at' => now(),
        ]);

        $sentCount = 0;
        $failedCount = 0;

        foreach ($schools as $school) {
            try {
                Mail::to($school->email, $school->name)
                    ->send(new PlatformEmailMailable($data['subject'], $data['body']));
                $sentCount++;
            } catch (\Throwable $e) {
                Log::error('Platform email failed', [
                    'school_id' => $school->id,
                    'error' => $e->getMessage(),
                ]);
                $failedCount++;
            }
        }

        $record->update([
            'sent_count' => $sentCount,
            'failed_count' => $failedCount,
        ]);

        return redirect()->route('super-admin.emails.index')
            ->with('success', __('Email sent to :count school(s). :failed failed.', [
                'count' => $sentCount,
                'failed' => $failedCount,
            ]));
    }

    public function show(PlatformEmail $email): View
    {
        $email->load('sender');

        $recipientSchools = School::whereIn('id', $email->recipient_school_ids ?? [])
            ->orderBy('name')
            ->get(['id', 'name', 'email']);

        return view('super-admin.emails.show', compact('email', 'recipientSchools'));
    }
}
