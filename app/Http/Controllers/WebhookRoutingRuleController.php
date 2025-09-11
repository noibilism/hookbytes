<?php

namespace App\Http\Controllers;

use App\Models\WebhookEndpoint;
use App\Models\WebhookRoutingRule;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Validator;

class WebhookRoutingRuleController extends Controller
{
    /**
     * Display routing rules for a webhook endpoint
     */
    public function index(WebhookEndpoint $endpoint): View
    {
        $routingRules = $endpoint->routingRules()->orderBy('priority')->get();
        
        return view('routing-rules.index', compact('endpoint', 'routingRules'));
    }

    /**
     * Show the form for creating a new routing rule
     */
    public function create(WebhookEndpoint $endpoint): View
    {
        return view('routing-rules.create', compact('endpoint'));
    }

    /**
     * Store a newly created routing rule
     */
    public function store(Request $request, WebhookEndpoint $endpoint): RedirectResponse
    {
        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255',
            'description' => 'nullable|string|max:500',
            'action' => 'required|in:route,drop',
            'priority' => 'required|integer|min:1|max:100',
            'conditions' => 'nullable|array',
            'destinations' => 'nullable|array',
            'destinations.*.url' => 'required_if:action,route|url',
            'destinations.*.priority' => 'nullable|integer|min:1|max:100',
            'is_active' => 'boolean',
        ]);

        if ($validator->fails()) {
            return back()->withErrors($validator)->withInput();
        }

        $endpoint->routingRules()->create([
            'name' => $request->name,
            'description' => $request->description,
            'action' => $request->action,
            'priority' => $request->priority,
            'conditions' => $request->conditions ?? [],
            'destinations' => $request->action === 'route' ? ($request->destinations ?? []) : [],
            'is_active' => $request->boolean('is_active', true),
        ]);

        return redirect()->route('routing-rules.index', $endpoint)
            ->with('success', 'Routing rule created successfully.');
    }

    /**
     * Show the form for editing a routing rule
     */
    public function edit(WebhookEndpoint $endpoint, WebhookRoutingRule $routingRule): View
    {
        return view('routing-rules.edit', compact('endpoint', 'routingRule'));
    }

    /**
     * Update the specified routing rule
     */
    public function update(Request $request, WebhookEndpoint $endpoint, WebhookRoutingRule $routingRule): RedirectResponse
    {
        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255',
            'description' => 'nullable|string|max:500',
            'action' => 'required|in:route,drop',
            'priority' => 'required|integer|min:1|max:100',
            'conditions' => 'nullable|array',
            'destinations' => 'nullable|array',
            'destinations.*.url' => 'required_if:action,route|url',
            'destinations.*.priority' => 'nullable|integer|min:1|max:100',
            'is_active' => 'boolean',
        ]);

        if ($validator->fails()) {
            return back()->withErrors($validator)->withInput();
        }

        $routingRule->update([
            'name' => $request->name,
            'description' => $request->description,
            'action' => $request->action,
            'priority' => $request->priority,
            'conditions' => $request->conditions ?? [],
            'destinations' => $request->action === 'route' ? ($request->destinations ?? []) : [],
            'is_active' => $request->boolean('is_active'),
        ]);

        return redirect()->route('routing-rules.index', $endpoint)
            ->with('success', 'Routing rule updated successfully.');
    }

    /**
     * Remove the specified routing rule
     */
    public function destroy(WebhookEndpoint $endpoint, WebhookRoutingRule $routingRule): RedirectResponse
    {
        $routingRule->delete();

        return redirect()->route('routing-rules.index', $endpoint)
            ->with('success', 'Routing rule deleted successfully.');
    }

    /**
     * Toggle routing rule active status
     */
    public function toggle(WebhookEndpoint $endpoint, WebhookRoutingRule $routingRule): JsonResponse
    {
        $routingRule->update([
            'is_active' => !$routingRule->is_active,
        ]);

        return response()->json([
            'success' => true,
            'is_active' => $routingRule->is_active,
        ]);
    }

    /**
     * Duplicate a routing rule
     */
    public function duplicate(WebhookEndpoint $endpoint, WebhookRoutingRule $routingRule): RedirectResponse
    {
        $newRule = $routingRule->replicate();
        $newRule->name = $routingRule->name . ' (Copy)';
        $newRule->priority = $routingRule->priority + 1;
        $newRule->is_active = false;
        $newRule->save();

        return redirect()->route('routing-rules.index', $endpoint)
            ->with('success', 'Routing rule duplicated successfully.');
    }
}
