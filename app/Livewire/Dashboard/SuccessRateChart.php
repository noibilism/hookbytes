<?php

namespace App\Livewire\Dashboard;

use App\Models\Delivery;
use Carbon\Carbon;
use Livewire\Component;
use Illuminate\Support\Facades\DB;

class SuccessRateChart extends Component
{
    public $chartData = [];
    public $period = '7d';

    public function mount()
    {
        $this->loadChartData();
    }

    public function updatedPeriod()
    {
        $this->loadChartData();
    }

    public function loadChartData()
    {
        $days = match($this->period) {
            '24h' => 1,
            '7d' => 7,
            '30d' => 30,
            default => 7,
        };

        $startDate = Carbon::now()->subDays($days);
        
        $data = Delivery::whereHas('event', function($query) {
            $query->where('user_id', auth()->id());
        })
        ->where('created_at', '>=', $startDate)
        ->select(
            DB::raw('DATE(created_at) as date'),
            DB::raw('COUNT(*) as total'),
            DB::raw('SUM(CASE WHEN status = "delivered" THEN 1 ELSE 0 END) as successful')
        )
        ->groupBy('date')
        ->orderBy('date')
        ->get();

        $this->chartData = $data->map(function($item) {
            return [
                'date' => Carbon::parse($item->date)->format('M j'),
                'success_rate' => $item->total > 0 ? round(($item->successful / $item->total) * 100, 1) : 0,
                'total' => $item->total,
                'successful' => $item->successful,
            ];
        })->toArray();
    }

    public function render()
    {
        return view('livewire.dashboard.success-rate-chart');
    }
}
