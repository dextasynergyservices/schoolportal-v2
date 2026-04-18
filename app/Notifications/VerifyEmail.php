<?php

declare(strict_types=1);

namespace App\Notifications;

use Illuminate\Auth\Notifications\VerifyEmail as BaseVerifyEmail;
use Illuminate\Notifications\Messages\MailMessage;

class VerifyEmail extends BaseVerifyEmail
{
    protected function buildMailMessage($url): MailMessage
    {
        return (new MailMessage)
            ->subject(__('Verify Your SchoolPortal Email Address'))
            ->greeting(__('Hello!'))
            ->line(__('Please click the button below to verify your email address.'))
            ->action(__('Verify Email Address'), $url)
            ->line(__('If you did not create an account, no further action is required.'))
            ->salutation(__('Regards, SchoolPortal'));
    }
}
