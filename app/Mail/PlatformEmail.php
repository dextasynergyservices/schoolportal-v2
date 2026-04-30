<?php

declare(strict_types=1);

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
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
    ) {}

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: $this->emailSubject,
        );
    }

    public function content(): Content
    {
        return new Content(
            markdown: 'emails.platform-email',
            with: [
                'body' => $this->emailBody,
            ],
        );
    }

    /**
     * @return array<int, Attachment>
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
