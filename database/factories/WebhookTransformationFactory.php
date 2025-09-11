<?php

namespace Database\Factories;

use App\Models\WebhookEndpoint;
use App\Models\WebhookTransformation;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\WebhookTransformation>
 */
class WebhookTransformationFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     *
     * @var string
     */
    protected $model = WebhookTransformation::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'webhook_endpoint_id' => WebhookEndpoint::factory(),
            'name' => $this->faker->words(3, true),
            'type' => $this->faker->randomElement(['field_mapping', 'template', 'javascript', 'jq']),
            'transformation_rules' => $this->getDefaultTransformationRules(),
            'conditions' => [],
            'priority' => $this->faker->numberBetween(1, 100),
            'is_active' => true,
            'last_tested_at' => null,
        ];
    }

    /**
     * Get default transformation rules based on type
     */
    private function getDefaultTransformationRules(): array
    {
        return [
            'mappings' => [
                ['source' => 'user.name', 'target' => 'customer_name'],
                ['source' => 'user.email', 'target' => 'customer_email'],
            ]
        ];
    }

    /**
     * Create a field mapping transformation
     */
    public function fieldMapping(array $mappings = null): static
    {
        return $this->state(function (array $attributes) use ($mappings) {
            return [
                'type' => 'field_mapping',
                'transformation_rules' => [
                    'mappings' => $mappings ?? [
                        ['source' => 'user.name', 'target' => 'customer_name'],
                        ['source' => 'user.email', 'target' => 'customer_email'],
                    ]
                ],
            ];
        });
    }

    /**
     * Create a template transformation
     */
    public function template(string $template = null): static
    {
        return $this->state(function (array $attributes) use ($template) {
            return [
                'type' => 'template',
                'transformation_rules' => [
                    'template' => $template ?? '{
                        "customer": "{{user.name}}",
                        "email": "{{user.email}}"
                    }'
                ],
            ];
        });
    }

    /**
     * Create a JavaScript transformation
     */
    public function javascript(string $code = null): static
    {
        return $this->state(function (array $attributes) use ($code) {
            return [
                'type' => 'javascript',
                'transformation_rules' => [
                    'code' => $code ?? 'function transform(payload) { return payload; }'
                ],
            ];
        });
    }

    /**
     * Create a jq transformation
     */
    public function jq(string $filter = null): static
    {
        return $this->state(function (array $attributes) use ($filter) {
            return [
                'type' => 'jq',
                'transformation_rules' => [
                    'filter' => $filter ?? '.user | {customer_name: .name, customer_email: .email}'
                ],
            ];
        });
    }

    /**
     * Create an inactive transformation
     */
    public function inactive(): static
    {
        return $this->state(function (array $attributes) {
            return [
                'is_active' => false,
            ];
        });
    }

    /**
     * Create a transformation with conditions
     */
    public function withConditions(array $conditions): static
    {
        return $this->state(function (array $attributes) use ($conditions) {
            return [
                'conditions' => $conditions,
            ];
        });
    }

    /**
     * Create a transformation with specific priority
     */
    public function priority(int $priority): static
    {
        return $this->state(function (array $attributes) use ($priority) {
            return [
                'priority' => $priority,
            ];
        });
    }
}