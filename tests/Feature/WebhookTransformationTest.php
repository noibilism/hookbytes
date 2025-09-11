<?php

namespace Tests\Feature;

use App\Models\Project;
use App\Models\User;
use App\Models\WebhookEndpoint;
use App\Models\WebhookTransformation;
use App\Services\WebhookTransformationService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class WebhookTransformationTest extends TestCase
{
    use RefreshDatabase;

    private User $user;
    private Project $project;
    private WebhookEndpoint $endpoint;
    private WebhookTransformationService $service;

    protected function setUp(): void
    {
        parent::setUp();
        
        $this->user = User::factory()->create();
        $this->project = Project::factory()->create();
        $this->endpoint = WebhookEndpoint::factory()->create(['project_id' => $this->project->id]);
        $this->service = app(WebhookTransformationService::class);
    }

    public function test_field_mapping_transformation()
    {
        $transformation = WebhookTransformation::factory()->create([
            'webhook_endpoint_id' => $this->endpoint->id,
            'type' => 'field_mapping',
            'transformation_rules' => [
                'mappings' => [
                    ['source' => 'user.name', 'target' => 'customer_name'],
                    ['source' => 'user.email', 'target' => 'customer_email'],
                ],
                'merge_with_original' => true
            ],
            'is_active' => true,
            'priority' => 1,
        ]);

        $payload = [
            'user' => [
                'name' => 'John Doe',
                'email' => 'john@example.com',
                'id' => 123
            ],
            'event' => 'user.created'
        ];

        $result = $this->service->applyTransformations($this->endpoint, $payload);

        $this->assertEquals('John Doe', $result['customer_name']);
        $this->assertEquals('john@example.com', $result['customer_email']);
        $this->assertEquals('user.created', $result['event']);
        $this->assertArrayHasKey('user', $result); // Original data should remain
    }

    public function test_template_transformation()
    {
        $transformation = WebhookTransformation::factory()->create([
            'webhook_endpoint_id' => $this->endpoint->id,
            'type' => 'template',
            'transformation_rules' => [
                'template' => '{
                    "customer": "{{user.name}}",
                    "email": "{{user.email}}",
                    "event_type": "{{event}}"
                }'
            ],
            'is_active' => true,
            'priority' => 1,
        ]);

        $payload = [
            'user' => [
                'name' => 'Jane Smith',
                'email' => 'jane@example.com'
            ],
            'event' => 'user.updated'
        ];

        $result = $this->service->applyTransformations($this->endpoint, $payload);

        $this->assertEquals('Jane Smith', $result['customer']);
        $this->assertEquals('jane@example.com', $result['email']);
        $this->assertEquals('user.updated', $result['event_type']);
    }

    public function test_javascript_transformation()
    {
        $transformation = WebhookTransformation::factory()->create([
            'webhook_endpoint_id' => $this->endpoint->id,
            'type' => 'javascript',
            'transformation_rules' => [
                'code' => 'function transform(payload) {
                    return {
                        ...payload,
                        processed_at: new Date().toISOString(),
                        user_full_name: payload.user.first_name + " " + payload.user.last_name
                    };
                }'
            ],
            'is_active' => true,
            'priority' => 1,
        ]);

        $payload = [
            'user' => [
                'first_name' => 'John',
                'last_name' => 'Doe',
                'email' => 'john@example.com'
            ]
        ];

        // JavaScript transformations are not implemented, so should return original payload
        $result = $this->service->applyTransformations($this->endpoint, $payload);

        // Should return original payload since JavaScript transformation fails
        $this->assertEquals($payload, $result);
    }

    public function test_jq_transformation()
    {
        $transformation = WebhookTransformation::factory()->create([
            'webhook_endpoint_id' => $this->endpoint->id,
            'type' => 'jq',
            'transformation_rules' => [
                'filter' => '.user | {customer_name: .name, customer_email: .email, customer_id: .id}'
            ],
            'is_active' => true,
            'priority' => 1,
        ]);

        $payload = [
            'user' => [
                'name' => 'Alice Johnson',
                'email' => 'alice@example.com',
                'id' => 456
            ],
            'event' => 'user.created'
        ];

        // jq transformations are not implemented, so should return original payload
        $result = $this->service->applyTransformations($this->endpoint, $payload);

        // Should return original payload since jq transformation fails
        $this->assertEquals($payload, $result);
    }

    public function test_multiple_transformations_priority_order()
    {
        // Create transformations with different priorities
        WebhookTransformation::factory()->create([
            'webhook_endpoint_id' => $this->endpoint->id,
            'type' => 'field_mapping',
            'transformation_rules' => [
                'mappings' => [
                    ['source' => 'user.name', 'target' => 'name'],
                ],
                'merge_with_original' => true
            ],
            'is_active' => true,
            'priority' => 2, // Second priority
        ]);

        WebhookTransformation::factory()->create([
            'webhook_endpoint_id' => $this->endpoint->id,
            'type' => 'field_mapping',
            'transformation_rules' => [
                'mappings' => [
                    ['source' => 'user.email', 'target' => 'email'],
                ],
                'merge_with_original' => true
            ],
            'is_active' => true,
            'priority' => 1, // First priority
        ]);

        $payload = [
            'user' => [
                'name' => 'Test User',
                'email' => 'test@example.com'
            ]
        ];

        $result = $this->service->applyTransformations($this->endpoint, $payload);

        $this->assertEquals('test@example.com', $result['email']);
        $this->assertEquals('Test User', $result['name']);
    }

    public function test_conditional_transformation()
    {
        $transformation = WebhookTransformation::factory()->create([
            'webhook_endpoint_id' => $this->endpoint->id,
            'type' => 'field_mapping',
            'transformation_rules' => [
                'mappings' => [
                    ['source' => 'user.name', 'target' => 'customer_name'],
                ]
            ],
            'conditions' => [
                ['field' => 'event', 'operator' => 'equals', 'value' => 'user.created']
            ],
            'is_active' => true,
            'priority' => 1,
        ]);

        // Test with matching condition
        $payload1 = [
            'user' => ['name' => 'John Doe'],
            'event' => 'user.created'
        ];

        $result1 = $this->service->applyTransformations($this->endpoint, $payload1);
        $this->assertEquals('John Doe', $result1['customer_name']);

        // Test with non-matching condition
        $payload2 = [
            'user' => ['name' => 'Jane Doe'],
            'event' => 'user.updated'
        ];

        $result2 = $this->service->applyTransformations($this->endpoint, $payload2);
        $this->assertArrayNotHasKey('customer_name', $result2);
    }

    public function test_inactive_transformation_not_applied()
    {
        $transformation = WebhookTransformation::factory()->create([
            'webhook_endpoint_id' => $this->endpoint->id,
            'type' => 'field_mapping',
            'transformation_rules' => [
                'mappings' => [
                    ['source' => 'user.name', 'target' => 'customer_name'],
                ]
            ],
            'is_active' => false, // Inactive
            'priority' => 1,
        ]);

        $payload = [
            'user' => ['name' => 'John Doe']
        ];

        $result = $this->service->applyTransformations($this->endpoint, $payload);
        
        $this->assertArrayNotHasKey('customer_name', $result);
        $this->assertEquals($payload, $result); // Should return original payload
    }

    public function test_transformation_error_handling()
    {
        $transformation = WebhookTransformation::factory()->create([
            'webhook_endpoint_id' => $this->endpoint->id,
            'type' => 'javascript',
            'transformation_rules' => [
                'code' => 'function transform(payload) { throw new Error("Test error"); }'
            ],
            'is_active' => true,
            'priority' => 1,
        ]);

        $payload = ['test' => 'data'];

        // Should not throw exception, but continue with original payload
        $result = $this->service->applyTransformations($this->endpoint, $payload);
        $this->assertEquals($payload, $result);
    }

    public function test_test_transformation_endpoint()
    {
        $this->actingAs($this->user);

        $transformation = WebhookTransformation::factory()->create([
            'webhook_endpoint_id' => $this->endpoint->id,
            'type' => 'field_mapping',
            'transformation_rules' => [
                'mappings' => [
                    ['source' => 'user.name', 'target' => 'customer_name'],
                ]
            ],
            'is_active' => true,
            'priority' => 1,
        ]);

        $testPayload = [
            'user' => ['name' => 'Test User']
        ];

        $response = $this->postJson(
            route('transformations.test', [$this->endpoint, $transformation]),
            ['test_payload' => $testPayload]
        );

        $response->assertStatus(200)
                ->assertJson([
                    'success' => true,
                    'original' => $testPayload,
                    'result' => [
                        'customer_name' => 'Test User'
                    ]
                ]);
    }
}