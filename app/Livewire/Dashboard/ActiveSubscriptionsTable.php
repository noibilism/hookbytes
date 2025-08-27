<?php

namespace App\Livewire\Dashboard;

use App\Models\Subscription;
use Livewire\Component;
use Livewire\WithPagination;

class ActiveSubscriptionsTable extends Component
{
    use WithPagination;

    public $search = '';
    public $sortField = 'created_at';
    public $sortDirection = 'desc';
    public $perPage = 10;

    protected $queryString = [
        'search' => ['except' => ''],
        'sortField' => ['except' => 'created_at'],
        'sortDirection' => ['except' => 'desc'],
    ];

    public function updatingSearch()
    {
        $this->resetPage();
    }

    public function sortBy($field)
    {
        if ($this->sortField === $field) {
            $this->sortDirection = $this->sortDirection === 'asc' ? 'desc' : 'asc';
        } else {
            $this->sortDirection = 'asc';
        }
        $this->sortField = $field;
    }

    public function toggleSubscription($subscriptionId)
    {
        $subscription = Subscription::where('user_id', auth()->id())
            ->findOrFail($subscriptionId);
        
        $subscription->update([
            'is_active' => !$subscription->is_active
        ]);

        $this->dispatch('subscription-toggled', [
            'message' => 'Subscription ' . ($subscription->is_active ? 'activated' : 'deactivated') . ' successfully.'
        ]);
    }

    public function getSubscriptionsProperty()
    {
        return Subscription::where('user_id', auth()->id())
            ->when($this->search, function($query) {
                $query->where(function($q) {
                    $q->where('url', 'like', '%' . $this->search . '%')
                      ->orWhereJsonContains('events', $this->search)
                      ->orWhere('name', 'like', '%' . $this->search . '%');
                });
            })
            ->withCount(['deliveries as total_deliveries'])
            ->withCount(['deliveries as successful_deliveries' => function($query) {
                $query->where('status', 'delivered');
            }])
            ->withCount(['deliveries as failed_deliveries' => function($query) {
                $query->where('status', 'failed');
            }])
            ->orderBy($this->sortField, $this->sortDirection)
            ->paginate($this->perPage);
    }

    public function render()
    {
        return view('livewire.dashboard.active-subscriptions-table', [
            'subscriptions' => $this->subscriptions,
        ]);
    }
}
