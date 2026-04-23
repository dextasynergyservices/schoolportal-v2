<?php

declare(strict_types=1);

namespace App\Livewire\Admin;

use App\Models\User;
use Livewire\Component;

class DashboardCustomizer extends Component
{
    public bool $showPanel = false;

    /** @var array<int, array{id: string, visible: bool}> */
    public array $widgets = [];

    /**
     * Widget metadata: labels, icons, descriptions for the UI.
     *
     * @return array<string, array{label: string, icon: string, description: string, color: string}>
     */
    public static function widgetMeta(): array
    {
        return [
            'alerts' => [
                'label' => 'Alerts & Warnings',
                'icon' => 'exclamation-triangle',
                'description' => 'System alerts like unassigned teachers',
                'color' => 'amber',
            ],
            'primary_stats' => [
                'label' => 'School Overview',
                'icon' => 'chart-bar',
                'description' => 'Student, teacher, parent & class counts',
                'color' => 'blue',
            ],
            'term_stats' => [
                'label' => 'Term Statistics',
                'icon' => 'presentation-chart-line',
                'description' => 'Results, assignments, notices & AI credits',
                'color' => 'indigo',
            ],
            'quick_actions' => [
                'label' => 'Quick Actions',
                'icon' => 'bolt',
                'description' => 'Shortcut buttons for common tasks',
                'color' => 'amber',
            ],
            'approvals_activity' => [
                'label' => 'Approvals & Activity',
                'icon' => 'clock',
                'description' => 'Pending submissions & recent audit log',
                'color' => 'emerald',
            ],
            'analytics_link' => [
                'label' => 'Analytics Link',
                'icon' => 'chart-bar-square',
                'description' => 'Quick link to detailed analytics page',
                'color' => 'cyan',
            ],
        ];
    }

    public function mount(): void
    {
        $this->widgets = auth()->user()->getDashboardWidgets();
    }

    public function openPanel(): void
    {
        $this->widgets = auth()->user()->getDashboardWidgets();
        $this->showPanel = true;
    }

    public function moveUp(int $index): void
    {
        if ($index <= 0) {
            return;
        }

        [$this->widgets[$index - 1], $this->widgets[$index]] = [$this->widgets[$index], $this->widgets[$index - 1]];
        $this->widgets = array_values($this->widgets);
    }

    public function moveDown(int $index): void
    {
        if ($index >= count($this->widgets) - 1) {
            return;
        }

        [$this->widgets[$index], $this->widgets[$index + 1]] = [$this->widgets[$index + 1], $this->widgets[$index]];
        $this->widgets = array_values($this->widgets);
    }

    public function toggleVisibility(int $index): void
    {
        if (! isset($this->widgets[$index])) {
            return;
        }

        $this->widgets[$index]['visible'] = ! $this->widgets[$index]['visible'];
    }

    /**
     * Reorder a widget from one position to another (called from Alpine drag-and-drop).
     */
    public function reorder(int $fromIndex, int $toIndex): void
    {
        if ($fromIndex === $toIndex) {
            return;
        }

        if ($fromIndex < 0 || $fromIndex >= count($this->widgets)) {
            return;
        }

        if ($toIndex < 0 || $toIndex >= count($this->widgets)) {
            return;
        }

        $item = array_splice($this->widgets, $fromIndex, 1);
        array_splice($this->widgets, $toIndex, 0, $item);
        $this->widgets = array_values($this->widgets);
    }

    public function save(): void
    {
        /** @var User $user */
        $user = auth()->user();
        $user->dashboard_preferences = ['widgets' => $this->widgets];
        $user->save();

        $this->showPanel = false;
        $this->dispatch('dashboard-updated');
    }

    public function resetDefaults(): void
    {
        $this->widgets = User::defaultDashboardWidgets();
    }

    public function render()
    {
        return view('livewire.admin.dashboard-customizer', [
            'meta' => self::widgetMeta(),
        ]);
    }
}
