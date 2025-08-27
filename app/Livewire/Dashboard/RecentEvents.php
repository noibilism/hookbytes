<?php

namespace App\Livewire\Dashboard;

use App\Models\Event;
use Livewire\Component;

class RecentEvents extends Component
{
    public $events = [];
    public $limit = 10;

    public function mount()
    {
        $this->loadEvents();
    }

    public function loadEvents()
    {
        $this->events = Event::where('user_id', auth()->id())
            ->with(['deliveries' => function($query) {
                $query->select('event_id', 'status')
                    ->groupBy('event_id', 'status')
                    ->selectRaw('event_id, status, count(*) as count');
            }])
            ->latest()
            ->limit($this->limit)
            ->get()
            ->map(function($event) {
                $deliveryStats = $event->deliveries->groupBy('status');
                return [
                    'id' => $event->id,
                    'type' => $event->type,
                    'created_at' => $event->created_at,
                    'status' => $event->status,
                    'delivery_count' => $event->deliveries->sum('count'),
                    'successful_deliveries' => $deliveryStats->get('delivered', collect())->sum('count'),
                    'failed_deliveries' => $deliveryStats->get('failed', collect())->sum('count'),
                    'pending_deliveries' => $deliveryStats->get('pending', collect())->sum('count'),
                ];
            })
            ->toArray();
    }

    public function render()
    {
        return view('livewire.dashboard.recent-events');
    }
}
