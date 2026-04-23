<?php

declare(strict_types=1);

namespace App\Notifications;

class NewQuizNotification extends DatabaseNotification
{
    public function __construct(
        private readonly string $quizTitle,
        private readonly string $className,
        private readonly int $quizId,
        private readonly string $recipientRole = 'student',
    ) {}

    protected function icon(): string
    {
        return 'question-mark-circle';
    }

    protected function message(): string
    {
        if ($this->recipientRole === 'parent') {
            return __('New quiz available for :class: :title', [
                'class' => $this->className,
                'title' => $this->quizTitle,
            ]);
        }

        return __('New quiz available: :title', ['title' => $this->quizTitle]);
    }

    protected function actionUrl(): string
    {
        if ($this->recipientRole === 'parent') {
            return url('/portal/parent/quizzes');
        }

        return url('/portal/student/quizzes');
    }

    protected function typeLabel(): string
    {
        return 'quiz';
    }

    /** @return array<string, mixed> */
    public function toArray(object $notifiable): array
    {
        return [
            'icon' => $this->icon(),
            'message' => $this->message(),
            'action_url' => $this->actionUrl(),
            'type_label' => $this->typeLabel(),
            'quiz_id' => $this->quizId,
            'quiz_title' => $this->quizTitle,
            'class_name' => $this->className,
        ];
    }
}
