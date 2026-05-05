<?php

declare(strict_types=1);

namespace App\Mail;

use App\Models\School;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Address;
use Illuminate\Mail\Mailables\Attachment;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class PlatformEmail extends Mailable
{
    use Queueable, SerializesModels;

    /**
     * @param  array<array{url: string, public_id: string, name: string, mime: string, size: int}>  $attachmentFiles
     */
    public function __construct(
        private readonly string $emailSubject,
        private readonly string $emailBody,
        private readonly array $attachmentFiles = [],
        private readonly ?School $school = null,
    ) {}

    public function envelope(): Envelope
    {
        $fromName = $this->school
            ? $this->school->name.' via DX-SchoolPortal'
            : config('app.name');

        return new Envelope(
            subject: $this->emailSubject,
            replyTo: $this->school?->email ? [new Address($this->school->email, $this->school->name)] : [],
        );
    }

    public function content(): Content
    {
        $school = $this->school;

        return new Content(
            view: 'emails.platform-email',
            with: [
                'body' => $this->emailBody,
                'schoolName' => $school?->name ?? config('app.name'),
                'schoolLogoUrl' => $school?->settings['branding']['logo_url'] ?? null,
                'primaryColor' => $school?->settings['branding']['primary_color'] ?? '#4F46E5',
            ],
        );
    }

    /**
     * @return array<int, Attachment> Cloudinary files downloaded and attached.
     */
    public function attachments(): array
    {
        $result = [];

        foreach ($this->attachmentFiles as $file) {
            if (! isset($file['url'], $file['name'], $file['mime'])) {
                continue;
            }

            try {
                $response = Http::timeout(30)->get($file['url']);

                if (! $response->successful()) {
                    Log::warning('Could not download email attachment from Cloudinary', [
                        'url' => $file['url'],
                        'status' => $response->status(),
                    ]);

                    continue;
                }

                $content = $response->body();
                $result[] = Attachment::fromData(fn () => $content, $file['name'])
                    ->withMime($file['mime']);
            } catch (\Throwable $e) {
                Log::warning('Exception downloading email attachment', [
                    'url' => $file['url'],
                    'error' => $e->getMessage(),
                ]);
            }
        }

        return $result;
    }
}
