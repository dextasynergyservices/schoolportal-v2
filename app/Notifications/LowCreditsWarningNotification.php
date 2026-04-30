<?php

declare(strict_types=1);

namespace App\Notifications;

class LowCreditsWarningNotification extends DatabaseNotification
{
    public function __construct(
        private readonly int $remainingCredits,
    ) {}

    protected function icon(): string
    {
        return 'exclamation-triangle';
    }

    protected function message(): string
    {
        if ($this->remainingCredits === 0) {
            return __('Your school has run out of AI credits. Purchase more to continue generating quizzes and games.');
        }

        return __('Low AI credits warning: only :count credit(s) remaining. Purchase more to avoid interruption.', [
            'count' => $this->remainingCredits,
        ]);
    }

    protected function actionUrl(): string
    {
        return url('/portal/admin/credits');
    }

    protected function typeLabel(): string
    {
        return 'low_credits';
    }

    /** @return array<string, mixed> */
    public function toArray(object $notifiable): array
    {
        return [
            'icon' => $this->icon(),
            'message' => $this->message(),
            'action_url' => $this->actionUrl(),
            'type_label' => $this->typeLabel(),
            'remaining_credits' => $this->remainingCredits,
        ];
    }
}
