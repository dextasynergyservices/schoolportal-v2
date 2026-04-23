<?php

declare(strict_types=1);

namespace App\Notifications;

use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;

class CreditPurchaseNotification extends DatabaseNotification implements ShouldQueue
{
    public function __construct(
        private readonly string $schoolName,
        private readonly int $credits,
        private readonly string $amount,
        private readonly int $schoolId,
    ) {}

    /** @return array<string> */
    public function via(object $notifiable): array
    {
        return $notifiable->email ? ['database', 'mail'] : ['database'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject(__('AI Credit Purchase — :school', ['school' => $this->schoolName]))
            ->greeting(__('New Credit Purchase'))
            ->line(__(':school purchased **:credits AI credits** for **:amount**.', [
                'school' => $this->schoolName,
                'credits' => $this->credits,
                'amount' => $this->amount,
            ]))
            ->action(__('View Credit Details'), $this->actionUrl())
            ->line(__('This is an automated notification from :app.', ['app' => config('app.name')]));
    }

    protected function icon(): string
    {
        return 'sparkles';
    }

    protected function message(): string
    {
        return __(':school purchased :credits AI credits (:amount)', [
            'school' => $this->schoolName,
            'credits' => $this->credits,
            'amount' => $this->amount,
        ]);
    }

    protected function actionUrl(): string
    {
        return url('/portal/super-admin/credits');
    }

    protected function typeLabel(): string
    {
        return 'credit_purchase';
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
            'credits' => $this->credits,
            'amount' => $this->amount,
        ];
    }
}
