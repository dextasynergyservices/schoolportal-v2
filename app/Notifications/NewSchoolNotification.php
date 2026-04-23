<?php

declare(strict_types=1);

namespace App\Notifications;

class NewSchoolNotification extends DatabaseNotification
{
    public function __construct(
        private readonly string $schoolName,
        private readonly int $schoolId,
    ) {}

    protected function icon(): string
    {
        return 'building-office-2';
    }

    protected function message(): string
    {
        return __('New school registered: :name', ['name' => $this->schoolName]);
    }

    protected function actionUrl(): string
    {
        return url("/portal/super-admin/schools/{$this->schoolId}");
    }

    protected function typeLabel(): string
    {
        return 'school';
    }

    /** @return array<string, mixed> */
    public function toArray(object $notifiable): array
    {
        return [
            'icon' => $this->icon(),
            'message' => $this->message(),
            'action_url' => $this->actionUrl(),
            'type_label' => $this->typeLabel(),
            'school_name' => $this->schoolName,
            'school_id' => $this->schoolId,
        ];
    }
}
