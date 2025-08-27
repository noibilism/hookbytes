<?php

namespace App\Livewire\Dashboard;

use App\Models\Event;
use App\Models\Delivery;
use App\Models\Subscription;
use Carbon\Carbon;
use Livewire\Component;
use Illuminate\Support\Facades\DB;

class StatsOverview extends Component
{
    public $stats = [];
    public $timeRange = '24h';

    public function mount()
    {
        $this->loadStats();
    }

    public function updatedTimeRange()
    {
        $this->loadStats();
    }

    public function loadStats()
    {
        $startDate = $this->getStartDate();
        
        $this->stats = [
            'total_subscriptions' => Subscription::where('user_id', auth()->id())->count(),
            'active_subscriptions' => Subscription::where('user_id', auth()->id())
                ->where('is_active', true)
                ->count(),
            'total_events' => Event::where('user_id', auth()->id())
                ->where('created_at', '>=', $startDate)
                ->count(),
            'total_deliveries' => Delivery::whereHas('event', function($query) {
                $query->where('user_id', auth()->id());
            })
            ->where('created_at', '>=', $startDate)
            ->count(),
            'successful_deliveries' => Delivery::whereHas('event', function($query) {
                $query->where('user_id', auth()->id());
            })
            ->where('created_at', '>=', $startDate)
            ->where('status', 'delivered')
            ->count(),
            'failed_deliveries' => Delivery::whereHas('event', function($query) {
                $query->where('user_id', auth()->id());
            })
            ->where('created_at', '>=', $startDate)
            ->where('status', 'failed')
            ->count(),
            'pending_deliveries' => Delivery::whereHas('event', function($query) {
                $query->where('user_id', auth()->id());
            })
            ->where('created_at', '>=', $startDate)
            ->where('status', 'pending')
            ->count(),
        ];

        // Calculate success rate
        $totalDeliveries = $this->stats['total_deliveries'];
        $this->stats['success_rate'] = $totalDeliveries > 0 
            ? round(($this->stats['successful_deliveries'] / $totalDeliveries) * 100, 1)
            : 0;

        // Calculate average response time
        $this->stats['avg_response_time'] = Delivery::whereHas('event', function($query) {
            $query->where('user_id', auth()->id());
        })
        ->where('created_at', '>=', $startDate)
        ->where('status', 'delivered')
        ->whereNotNull('response_time_ms')
        ->avg('response_time_ms') ?? 0;

        $this->stats['avg_response_time'] = round($this->stats['avg_response_time']);
    }

    private function getStartDate()
    {
        return match($this->timeRange) {
            '1h' => Carbon::now()->subHour(),
            '24h' => Carbon::now()->subDay(),
            '7d' => Carbon::now()->subWeek(),
            '30d' => Carbon::now()->subMonth(),
            default => Carbon::now()->subDay(),
        };
    }

    public function render()
    {
        return view('livewire.dashboard.stats-overview');
    }
}
