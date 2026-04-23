<?php

declare(strict_types=1);

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class ParentWeeklyDigest extends Notification implements ShouldQueue
{
    use Queueable;

    /**
     * @param  array<int, array{
     *     name: string,
     *     class: string,
     *     quizzes: array<int, array{title: string, score: string, percentage: string, passed: bool}>,
     *     games: array<int, array{title: string, type: string, score: string, percentage: string}>,
     *     assignments_count: int,
     *     results_count: int,
     * }>  $childrenSummaries
     */
    public function __construct(
        private readonly string $parentName,
        private readonly string $schoolName,
        private readonly array $childrenSummaries,
        private readonly int $noticesCount,
        private readonly string $portalUrl,
    ) {}

    /** @return array<string> */
    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $message = (new MailMessage)
            ->subject(__('Weekly Update — :school', ['school' => $this->schoolName]))
            ->greeting(__('Hello :name,', ['name' => $this->parentName]))
            ->line(__("Here's what happened this week at :school:", ['school' => $this->schoolName]));

        foreach ($this->childrenSummaries as $child) {
            $message->line('---');
            $message->line('**'.$child['name'].'** ('.$child['class'].')');

            $items = [];

            foreach ($child['quizzes'] as $quiz) {
                $status = $quiz['passed'] ? '✅' : '❌';
                $items[] = $status.' Scored '.$quiz['percentage'].'% on '.$quiz['title'];
            }

            foreach ($child['games'] as $game) {
                $items[] = '🎮 Scored '.$game['percentage'].'% on '.$game['title'];
            }

            if ($child['assignments_count'] > 0) {
                $items[] = '📝 '.$child['assignments_count'].' new '.($child['assignments_count'] === 1 ? 'assignment' : 'assignments').' posted';
            }

            if ($child['results_count'] > 0) {
                $items[] = '📋 '.$child['results_count'].' new '.($child['results_count'] === 1 ? 'result' : 'results').' uploaded';
            }

            if (empty($items)) {
                $items[] = 'No new activity this week.';
            }

            foreach ($items as $item) {
                $message->line($item);
            }
        }

        if ($this->noticesCount > 0) {
            $message->line('---');
            $message->line('📢 '.$this->noticesCount.' new school '.($this->noticesCount === 1 ? 'notice' : 'notices').' published.');
        }

        return $message
            ->action(__('View Full Details'), $this->portalUrl)
            ->salutation(__('Regards, :school', ['school' => $this->schoolName]));
    }
}
