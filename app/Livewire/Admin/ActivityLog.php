<?php

namespace App\Livewire\Admin;

use Livewire\Attributes\Layout;
use Livewire\Attributes\Url;
use Livewire\Component;
use Livewire\WithPagination;
use Spatie\Activitylog\Models\Activity;

#[Layout('components.layouts.admin')]
class ActivityLog extends Component
{
    use WithPagination;

    #[Url]
    public string $log = 'tous';

    public function setLog(string $log): void
    {
        $this->log = $log;
        $this->resetPage();
    }

    public function render()
    {
        $query = Activity::with(['causer', 'subject'])->latest();

        if (in_array($this->log, ['payment', 'user'], true)) {
            $query->where('log_name', $this->log);
        }

        return view('livewire.admin.activity-log', [
            'entries' => $query->paginate(40),
        ]);
    }
}
