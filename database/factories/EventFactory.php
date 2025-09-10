<?php

namespace Database\Factories;

use App\Models\Event;
use App\Models\Project;
use App\Models\WebhookEndpoint;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Event>
 */
class EventFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     *
     * @var string
     */
    protected $model = Event::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'event_id' => $this->faker->uuid(),
            'project_id' => Project::factory(),
            'webhook_endpoint_id' => WebhookEndpoint::factory(),
            'event_type' => $this->faker->randomElement([
                'user.created',
                'user.updated',
                'user.deleted',
                'order.created',
                'order.updated',
                'order.cancelled',
                'payment.succeeded',
                'payment.failed',
                'subscription.created',
                'subscription.cancelled',
            ]),
            'payload' => [
                'id' => $this->faker->uuid(),
                'timestamp' => $this->faker->unixTime(),
                'data' => [
                    'user_id' => $this->faker->numberBetween(1, 1000),
                    'email' => $this->faker->email(),
                    'amount' => $this->faker->randomFloat(2, 10, 1000),
                ],
            ],
            'headers' => [
                'Content-Type' => 'application/json',
                'User-Agent' => $this->faker->userAgent(),
                'X-Webhook-ID' => $this->faker->uuid(),
            ],
            'source_ip' => $this->faker->ipv4(),
            'user_agent' => $this->faker->userAgent(),
            'status' => $this->faker->randomElement(['pending', 'processing', 'delivered', 'failed']),
            'delivery_attempts' => $this->faker->numberBetween(0, 5),
            'last_attempt_at' => $this->faker->optional(0.7)->dateTimeBetween('-1 hour', 'now'),
            'delivered_at' => $this->faker->optional(0.5)->dateTimeBetween('-1 hour', 'now'),
        ];
    }

    /**
     * Indicate that the event was delivered successfully.
     */
    public function delivered(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'delivered',
            'attempts' => $this->faker->numberBetween(1, 3),
            'response_status' => $this->faker->randomElement([200, 201, 202]),
            'response_time' => $this->faker->numberBetween(50, 500),
            'next_retry_at' => null,
        ]);
    }

    /**
     * Indicate that the event failed to deliver.
     */
    public function failed(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'failed',
            'attempts' => $this->faker->numberBetween(3, 10),
            'response_status' => $this->faker->randomElement([400, 404, 500, 502, 503]),
            'response_time' => $this->faker->numberBetween(1000, 5000),
            'next_retry_at' => null,
        ]);
    }

    /**
     * Indicate that the event is pending delivery.
     */
    public function pending(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'pending',
            'attempts' => 0,
            'response_status' => null,
            'response_body' => null,
            'response_time' => null,
            'next_retry_at' => null,
        ]);
    }

    /**
     * Indicate that the event is retrying.
     */
    public function retrying(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'retrying',
            'attempts' => $this->faker->numberBetween(1, 3),
            'response_status' => $this->faker->randomElement([500, 502, 503, 504]),
            'response_time' => $this->faker->numberBetween(1000, 3000),
            'next_retry_at' => $this->faker->dateTimeBetween('now', '+1 hour'),
        ]);
    }

    /**
     * Indicate that the event belongs to a specific project.
     */
    public function forProject(Project $project): static
    {
        return $this->state(fn (array $attributes) => [
            'project_id' => $project->id,
        ]);
    }

    /**
     * Indicate that the event belongs to a specific webhook endpoint.
     */
    public function forWebhookEndpoint(WebhookEndpoint $endpoint): static
    {
        return $this->state(fn (array $attributes) => [
            'webhook_endpoint_id' => $endpoint->id,
            'project_id' => $endpoint->project_id,
        ]);
    }

    /**
     * Indicate that the event has a specific type.
     */
    public function ofType(string $type): static
    {
        return $this->state(fn (array $attributes) => [
            'event_type' => $type,
        ]);
    }

    /**
     * Indicate that the event was created recently.
     */
    public function recent(): static
    {
        return $this->state(fn (array $attributes) => [
            'created_at' => $this->faker->dateTimeBetween('-24 hours', 'now'),
        ]);
    }
}