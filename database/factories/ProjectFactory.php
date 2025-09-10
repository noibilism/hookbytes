<?php

namespace Database\Factories;

use App\Models\Project;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Project>
 */
class ProjectFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     *
     * @var string
     */
    protected $model = Project::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'name' => $this->faker->company() . ' Project',
            'slug' => $this->faker->unique()->slug(),
            'description' => $this->faker->sentence(),
            'api_key' => 'pk_' . Str::random(32),
            'webhook_secret' => 'whsec_' . Str::random(32),
            'is_active' => $this->faker->boolean(80),
            'settings' => [
                'max_events_per_day' => $this->faker->numberBetween(1000, 50000),
                'retention_days' => $this->faker->numberBetween(7, 365),
                'enable_analytics' => $this->faker->boolean(),
            ],
        ];
    }

    /**
     * Indicate that the project is inactive.
     */
    public function inactive(): static
    {
        return $this->state(fn (array $attributes) => [
            'is_active' => false,
        ]);
    }


}