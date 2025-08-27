<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Subscription;
use App\Models\Event;
use App\Models\Delivery;

class DashboardController extends Controller
{
    /**
     * Display the main dashboard overview.
     */
    public function index()
    {
        return view('dashboard.index');
    }

    /**
     * Display the subscriptions management page.
     */
    public function subscriptions()
    {
        return view('dashboard.subscriptions.index');
    }

    /**
     * Show the form for creating a new subscription.
     */
    public function createSubscription()
    {
        return view('dashboard.subscriptions.create');
    }

    /**
     * Display the specified subscription.
     */
    public function showSubscription(Subscription $subscription)
    {
        // Ensure the subscription belongs to the authenticated user
        if ($subscription->user_id !== auth()->id()) {
            abort(404);
        }

        return view('dashboard.subscriptions.show', compact('subscription'));
    }

    /**
     * Show the form for editing the specified subscription.
     */
    public function editSubscription(Subscription $subscription)
    {
        // Ensure the subscription belongs to the authenticated user
        if ($subscription->user_id !== auth()->id()) {
            abort(404);
        }

        return view('dashboard.subscriptions.edit', compact('subscription'));
    }

    /**
     * Display the events management page.
     */
    public function events()
    {
        return view('dashboard.events.index');
    }

    /**
     * Display the specified event.
     */
    public function showEvent(Event $event)
    {
        // Ensure the event belongs to the authenticated user
        if ($event->user_id !== auth()->id()) {
            abort(404);
        }

        return view('dashboard.events.show', compact('event'));
    }

    /**
     * Display the deliveries management page.
     */
    public function deliveries()
    {
        return view('dashboard.deliveries.index');
    }

    /**
     * Display the specified delivery.
     */
    public function showDelivery(Delivery $delivery)
    {
        // Ensure the delivery belongs to the authenticated user through the event
        if ($delivery->event->user_id !== auth()->id()) {
            abort(404);
        }

        return view('dashboard.deliveries.show', compact('delivery'));
    }

    /**
     * Display the API keys management page.
     */
    public function apiKeys()
    {
        return view('dashboard.api-keys');
    }
}
