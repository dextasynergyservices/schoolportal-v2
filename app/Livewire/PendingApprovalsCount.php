<?php

declare(strict_types=1);

namespace App\Livewire;

use App\Models\TeacherAction;
use Illuminate\Contracts\View\View;
use Livewire\Attributes\Lazy;
use Livewire\Component;

#[Lazy]
class PendingApprovalsCount extends Component
{
    public int $count = 0;

    public function mount(): void
    {
        $this->loadCount();
    }

    public function loadCount(): void
    {
        $user = auth()->user();

        if ($user && ($user->isSchoolAdmin() || $user->isSuperAdmin())) {
            $this->count = TeacherAction::where('status', 'pending')->count();
        }
    }

    public function placeholder(): string
    {
        return <<<'HTML'
        <span></span>
        HTML;
    }

    public function render(): View
    {
        return view('livewire.pending-approvals-count');
    }
}
