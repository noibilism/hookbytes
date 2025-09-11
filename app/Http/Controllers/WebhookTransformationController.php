<?php

namespace App\Http\Controllers;

use App\Models\WebhookEndpoint;
use App\Models\WebhookTransformation;
use App\Services\WebhookTransformationService;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Validator;

class WebhookTransformationController extends Controller
{
    public function __construct(
        private WebhookTransformationService $transformationService
    ) {}

    /**
     * Display transformations for a webhook endpoint
     */
    public function index(WebhookEndpoint $endpoint): View
    {
        $transformations = $endpoint->transformations()->orderBy('priority')->get();
        
        return view('transformations.index', compact('endpoint', 'transformations'));
    }

    /**
     * Show the form for creating a new transformation
     */
    public function create(WebhookEndpoint $endpoint): View
    {
        return view('transformations.create', compact('endpoint'));
    }

    /**
     * Store a newly created transformation
     */
    public function store(Request $request, WebhookEndpoint $endpoint): RedirectResponse
    {
        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255',
            'type' => 'required|in:field_mapping,template,javascript,jq',
            'transformation_rules' => 'required|array',
            'conditions' => 'nullable|array',
            'priority' => 'required|integer|min:1|max:100',
            'is_active' => 'boolean',
        ]);

        if ($validator->fails()) {
            return back()->withErrors($validator)->withInput();
        }

        $endpoint->transformations()->create([
            'name' => $request->name,
            'type' => $request->type,
            'transformation_rules' => $request->transformation_rules,
            'conditions' => $request->conditions ?? [],
            'priority' => $request->priority,
            'is_active' => $request->boolean('is_active', true),
        ]);

        return redirect()->route('transformations.index', $endpoint)
            ->with('success', 'Transformation created successfully.');
    }

    /**
     * Show the form for editing a transformation
     */
    public function edit(WebhookEndpoint $endpoint, WebhookTransformation $transformation): View
    {
        return view('transformations.edit', compact('endpoint', 'transformation'));
    }

    /**
     * Update the specified transformation
     */
    public function update(Request $request, WebhookEndpoint $endpoint, WebhookTransformation $transformation): RedirectResponse
    {
        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255',
            'type' => 'required|in:field_mapping,template,javascript,jq',
            'transformation_rules' => 'required|array',
            'conditions' => 'nullable|array',
            'priority' => 'required|integer|min:1|max:100',
            'is_active' => 'boolean',
        ]);

        if ($validator->fails()) {
            return back()->withErrors($validator)->withInput();
        }

        $transformation->update([
            'name' => $request->name,
            'type' => $request->type,
            'transformation_rules' => $request->transformation_rules,
            'conditions' => $request->conditions ?? [],
            'priority' => $request->priority,
            'is_active' => $request->boolean('is_active'),
        ]);

        return redirect()->route('transformations.index', $endpoint)
            ->with('success', 'Transformation updated successfully.');
    }

    /**
     * Remove the specified transformation
     */
    public function destroy(WebhookEndpoint $endpoint, WebhookTransformation $transformation): RedirectResponse
    {
        $transformation->delete();

        return redirect()->route('transformations.index', $endpoint)
            ->with('success', 'Transformation deleted successfully.');
    }

    /**
     * Test a transformation with sample data
     */
    public function test(Request $request, WebhookEndpoint $endpoint, WebhookTransformation $transformation): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'test_payload' => 'required|array',
            'test_headers' => 'nullable|array',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors(),
            ], 422);
        }

        $result = $this->transformationService->testTransformation(
            $transformation,
            $request->test_payload,
            $request->test_headers ?? []
        );

        return response()->json($result);
    }

    /**
     * Toggle transformation active status
     */
    public function toggle(WebhookEndpoint $endpoint, WebhookTransformation $transformation): JsonResponse
    {
        $transformation->update([
            'is_active' => !$transformation->is_active,
        ]);

        return response()->json([
            'success' => true,
            'is_active' => $transformation->is_active,
        ]);
    }

    /**
     * Duplicate a transformation
     */
    public function duplicate(WebhookEndpoint $endpoint, WebhookTransformation $transformation): RedirectResponse
    {
        $newTransformation = $transformation->replicate();
        $newTransformation->name = $transformation->name . ' (Copy)';
        $newTransformation->priority = $transformation->priority + 1;
        $newTransformation->is_active = false;
        $newTransformation->save();

        return redirect()->route('transformations.index', $endpoint)
            ->with('success', 'Transformation duplicated successfully.');
    }
}
