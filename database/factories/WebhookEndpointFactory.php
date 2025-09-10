<?php

namespace Database\Factories;

use App\Models\Project;
use App\Models\WebhookEndpoint;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\WebhookEndpoint>
 */
class WebhookEndpointFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     *
     * @var string
     */
    protected $model = WebhookEndpoint::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $name = $this->faker->words(2, true);
        $slug = Str::slug($name) . '-' . Str::random(6);
        
        return [
            'project_id' => Project::factory(),
            'name' => ucwords($name),
            'slug' => $slug,
            'url_path' => '/' . $slug,
            'destination_urls' => [
                $this->faker->url() . '/webhook',
                $this->faker->url() . '/webhook-backup',
            ],
            'auth_method' => $this->faker->randomElement(['none', 'basic', 'bearer', 'hmac']),
            'auth_secret' => $this->faker->sha256(),
            'is_active' => true,
            'retry_config' => [
                'max_attempts' => $this->faker->numberBetween(1, 10),
                'retry_delay' => $this->faker->numberBetween(60, 300),
                'backoff_multiplier' => $this->faker->randomFloat(1, 1.0, 3.0),
            ],
            'headers_config' => [
                'X-Custom-Header' => $this->faker->word(),
                'X-Source' => 'webhook-gateway',
            ],
            'created_at' => now(),
            'updated_at' => now(),
        ];
    }

    /**
     * Indicate that the webhook endpoint is inactive.
     */
    public function inactive(): static
    {
        return $this->state(fn (array $attributes) => [
            'is_active' => false,
        ]);
    }

    /**
     * Indicate that the webhook endpoint uses no authentication.
     */
    public function noAuth(): static
    {
        return $this->state(fn (array $attributes) => [
            'auth_method' => 'none',
            'auth_secret' => null,
        ]);
    }

    /**
     * Indicate that the webhook endpoint uses HMAC authentication.
     */
    public function hmacAuth(): static
    {
        return $this->state(fn (array $attributes) => [
            'auth_method' => 'hmac',
            'auth_secret' => 'hmac_' . Str::random(32),
        ]);
    }

    /**
     * Indicate that the webhook endpoint belongs to a specific project.
     */
    public function forProject(Project $project): static
    {
        return $this->state(fn (array $attributes) => [
            'project_id' => $project->id,
        ]);
    }

    /**
     * Indicate that the webhook endpoint has a single destination URL.
     */
    public function singleDestination(): static
    {
        return $this->state(fn (array $attributes) => [
            'destination_urls' => [$this->faker->url() . '/webhook'],
        ]);
    }
}