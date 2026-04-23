<?php

declare(strict_types=1);

namespace App\Livewire;

use Illuminate\Contracts\View\View;
use Livewire\Attributes\Lazy;
use Livewire\Component;

#[Lazy]
class NotificationBell extends Component
{
    public int $unreadCount = 0;

    /** @var array<int, array<string, mixed>> */
    public array $recentNotifications = [];

    public bool $showDropdown = false;

    public function mount(): void
    {
        $this->loadNotifications();
    }

    public function loadNotifications(): void
    {
        $user = auth()->user();
        if (! $user) {
            return;
        }

        $this->unreadCount = $user->unreadNotifications()->count();

        $this->recentNotifications = $user->notifications()
            ->latest()
            ->take(10)
            ->get()
            ->map(fn ($n) => [
                'id' => $n->id,
                'message' => $n->data['message'] ?? '',
                'icon' => $n->data['icon'] ?? 'bell',
                'action_url' => $n->data['action_url'] ?? '#',
                'type_label' => $n->data['type_label'] ?? '',
                'read' => $n->read_at !== null,
                'time' => $n->created_at->diffForHumans(),
            ])
            ->toArray();
    }

    public function toggleDropdown(): void
    {
        $this->showDropdown = ! $this->showDropdown;

        if ($this->showDropdown) {
            $this->loadNotifications();
        }
    }

    public function markAsRead(string $notificationId): string
    {
        $user = auth()->user();
        $notification = $user?->notifications()->find($notificationId);

        if ($notification) {
            $notification->markAsRead();
            $actionUrl = $notification->data['action_url'] ?? '#';
            $this->loadNotifications();

            return $actionUrl;
        }

        return '#';
    }

    public function markAllAsRead(): void
    {
        auth()->user()?->unreadNotifications->markAsRead();
        $this->loadNotifications();
    }

    public function placeholder(): string
    {
        return <<<'HTML'
        <span></span>
        HTML;
    }

    public function render(): View
    {
        return view('livewire.notification-bell');
    }
}
