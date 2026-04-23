<?php

declare(strict_types=1);

namespace App\Notifications;

class NewGameNotification extends DatabaseNotification
{
    public function __construct(
        private readonly string $gameTitle,
        private readonly string $className,
        private readonly string $gameType,
        private readonly int $gameId,
        private readonly string $recipientRole = 'student',
    ) {}

    protected function icon(): string
    {
        return 'puzzle-piece';
    }

    protected function message(): string
    {
        if ($this->recipientRole === 'parent') {
            return __('New :type game for :class: :title', [
                'type' => str_replace('_', ' ', $this->gameType),
                'class' => $this->className,
                'title' => $this->gameTitle,
            ]);
        }

        return __('New :type game available: :title', [
            'type' => str_replace('_', ' ', $this->gameType),
            'title' => $this->gameTitle,
        ]);
    }

    protected function actionUrl(): string
    {
        if ($this->recipientRole === 'parent') {
            return url('/portal/parent/games');
        }

        return url('/portal/student/games');
    }

    protected function typeLabel(): string
    {
        return 'game';
    }

    /** @return array<string, mixed> */
    public function toArray(object $notifiable): array
    {
        return [
            'icon' => $this->icon(),
            'message' => $this->message(),
            'action_url' => $this->actionUrl(),
            'type_label' => $this->typeLabel(),
            'game_id' => $this->gameId,
            'game_title' => $this->gameTitle,
            'game_type' => $this->gameType,
            'class_name' => $this->className,
        ];
    }
}
