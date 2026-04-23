<?php

declare(strict_types=1);

namespace App\Notifications;

class NewAssignmentNotification extends DatabaseNotification
{
    public function __construct(
        private readonly string $className,
        private readonly string $title,
        private readonly int $weekNumber,
        private readonly string $recipientRole = 'student',
    ) {}

    protected function icon(): string
    {
        return 'clipboard-document-list';
    }

    protected function message(): string
    {
        $label = $this->title ?: __('Week :week assignment', ['week' => $this->weekNumber]);

        if ($this->recipientRole === 'parent') {
            return __('New assignment for :class: :title', [
                'class' => $this->className,
                'title' => $label,
            ]);
        }

        return __('New assignment: :title', ['title' => $label]);
    }

    protected function actionUrl(): string
    {
        if ($this->recipientRole === 'parent') {
            return url('/portal/parent/assignments');
        }

        return url('/portal/student/assignments');
    }

    protected function typeLabel(): string
    {
        return 'assignment';
    }

    /** @return array<string, mixed> */
    public function toArray(object $notifiable): array
    {
        return [
            'icon' => $this->icon(),
            'message' => $this->message(),
            'action_url' => $this->actionUrl(),
            'type_label' => $this->typeLabel(),
            'class_name' => $this->className,
            'title' => $this->title,
            'week_number' => $this->weekNumber,
        ];
    }
}
