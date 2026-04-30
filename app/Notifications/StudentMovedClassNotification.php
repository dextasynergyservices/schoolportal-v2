<?php

declare(strict_types=1);

namespace App\Notifications;

class StudentMovedClassNotification extends DatabaseNotification
{
    public function __construct(
        private readonly string $fromClassName,
        private readonly string $toClassName,
        private readonly string $studentName,
        private readonly string $recipientRole = 'student', // 'student' or 'parent'
    ) {}

    protected function icon(): string
    {
        return 'arrows-right-left';
    }

    protected function message(): string
    {
        if ($this->recipientRole === 'parent') {
            return __(':student has been moved from :from to :to.', [
                'student' => $this->studentName,
                'from' => $this->fromClassName,
                'to' => $this->toClassName,
            ]);
        }

        return __('You have been moved from :from to :to.', [
            'from' => $this->fromClassName,
            'to' => $this->toClassName,
        ]);
    }

    protected function actionUrl(): string
    {
        return $this->recipientRole === 'parent'
            ? url('/portal/parent/dashboard')
            : url('/portal/student/dashboard');
    }

    protected function typeLabel(): string
    {
        return 'class_move';
    }

    /** @return array<string, mixed> */
    public function toArray(object $notifiable): array
    {
        return [
            'icon' => $this->icon(),
            'message' => $this->message(),
            'action_url' => $this->actionUrl(),
            'type' => $this->typeLabel(),
        ];
    }
}
