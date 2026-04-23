<?php

declare(strict_types=1);

namespace App\Notifications;

class NewResultNotification extends DatabaseNotification
{
    public function __construct(
        private readonly string $studentName,
        private readonly string $sessionName,
        private readonly string $termName,
        private readonly int $resultId,
        private readonly string $recipientRole = 'student',
    ) {}

    protected function icon(): string
    {
        return 'document-text';
    }

    protected function message(): string
    {
        if ($this->recipientRole === 'parent') {
            return __(':student\'s result for :term, :session has been uploaded.', [
                'student' => $this->studentName,
                'term' => $this->termName,
                'session' => $this->sessionName,
            ]);
        }

        return __('Your result for :term, :session has been uploaded.', [
            'term' => $this->termName,
            'session' => $this->sessionName,
        ]);
    }

    protected function actionUrl(): string
    {
        if ($this->recipientRole === 'parent') {
            return url('/portal/parent/results');
        }

        return url("/portal/student/results/{$this->resultId}");
    }

    protected function typeLabel(): string
    {
        return 'result';
    }

    /** @return array<string, mixed> */
    public function toArray(object $notifiable): array
    {
        return [
            'icon' => $this->icon(),
            'message' => $this->message(),
            'action_url' => $this->actionUrl(),
            'type_label' => $this->typeLabel(),
            'result_id' => $this->resultId,
            'student_name' => $this->studentName,
            'session_name' => $this->sessionName,
            'term_name' => $this->termName,
        ];
    }
}
