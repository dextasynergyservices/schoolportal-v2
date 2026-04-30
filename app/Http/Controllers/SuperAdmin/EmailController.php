<?php

declare(strict_types=1);

namespace App\Http\Controllers\SuperAdmin;

use App\Http\Controllers\Controller;
use App\Http\Requests\StorePlatformEmailRequest;
use App\Jobs\SendPlatformEmailJob;
use App\Models\PlatformEmail;
use App\Models\School;
use App\Services\FileUploadService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Log;
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
            ->get(['id', 'name', 'email', 'custom_domain']);

        return view('super-admin.emails.create', compact('schools'));
    }

    public function store(StorePlatformEmailRequest $request, FileUploadService $uploadService): RedirectResponse
    {
        $data = $request->validated();

        $schools = School::whereIn('id', $data['school_ids'])
            ->whereNotNull('email')
            ->where('email', '!=', '')
            ->get();

        if ($schools->isEmpty()) {
            return back()->withInput()
                ->with('error', __('None of the selected schools have an email address.'));
        }

        // Upload attachments to Cloudinary before creating the record so that
        // disk space on the server is never consumed (cPanel shared hosting).
        $attachments = [];
        if ($request->hasFile('attachments')) {
            foreach ($request->file('attachments') as $file) {
                try {
                    $attachments[] = $uploadService->uploadEmailAttachment($file);
                } catch (\Throwable $e) {
                    Log::error('Email attachment upload to Cloudinary failed', [
                        'file' => $file->getClientOriginalName(),
                        'error' => $e->getMessage(),
                    ]);

                    return back()->withInput()
                        ->with('error', __('Failed to upload attachment ":file". Please try again.', [
                            'file' => $file->getClientOriginalName(),
                        ]));
                }
            }
        }

        $record = PlatformEmail::create([
            'subject' => $data['subject'],
            'body' => $data['body'],
            'attachments' => $attachments ?: null,
            'recipient_school_ids' => $data['school_ids'],
            'total_recipients' => $schools->count(),
            'sent_by' => auth()->id(),
            'sent_at' => now(),
            'queued_at' => now(),
        ]);

        // Dispatch one lightweight job per school — non-blocking.
        foreach ($schools as $school) {
            SendPlatformEmailJob::dispatch($record->id, $school->id);
        }

        return redirect()->route('super-admin.emails.index')
            ->with('success', __('Email queued for :count school(s). Delivery is in progress in the background.', [
                'count' => $schools->count(),
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
