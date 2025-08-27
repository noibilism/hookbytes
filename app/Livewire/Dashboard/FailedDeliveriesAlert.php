<?php

namespace App\Livewire\Dashboard;

use App\Models\Delivery;
use Carbon\Carbon;
use Livewire\Component;

class FailedDeliveriesAlert extends Component
{
    public $recentFailures = 0;
    public $criticalFailures = 0;
    public $showAlert = false;

    public function mount()
    {
        $this->checkFailures();
    }

    public function checkFailures()
    {
        $last24Hours = Carbon::now()->subDay();
        $lastHour = Carbon::now()->subHour();

        // Count recent failures (last 24 hours)
        $this->recentFailures = Delivery::whereHas('event', function($query) {
            $query->where('user_id', auth()->id());
        })
        ->where('status', 'failed')
        ->where('created_at', '>=', $last24Hours)
        ->count();

        // Count critical failures (last hour)
        $this->criticalFailures = Delivery::whereHas('event', function($query) {
            $query->where('user_id', auth()->id());
        })
        ->where('status', 'failed')
        ->where('created_at', '>=', $lastHour)
        ->count();

        // Show alert if there are critical failures or many recent failures
        $this->showAlert = $this->criticalFailures > 0 || $this->recentFailures > 10;
    }

    public function dismissAlert()
    {
        $this->showAlert = false;
    }

    public function render()
    {
        return view('livewire.dashboard.failed-deliveries-alert');
    }
}
