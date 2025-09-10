<?php

namespace Database\Factories;

use App\Models\Settings;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Settings>
 */
class SettingsFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     *
     * @var string
     */
    protected $model = Settings::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'slack_webhook_url' => $this->faker->optional(0.3)->url(),
            'slack_notifications_enabled' => $this->faker->boolean(),
            'notification_email' => $this->faker->optional(0.7)->email(),
            'email_notifications_enabled' => $this->faker->boolean(),
        ];
    }

    /**
     * Create settings with Slack notifications enabled.
     */
    public function withSlackEnabled(): static
    {
        return $this->state(fn (array $attributes) => [
            'slack_notifications_enabled' => true,
            'slack_webhook_url' => $this->faker->url(),
        ]);
    }

    /**
     * Create settings with email notifications enabled.
     */
    public function withEmailEnabled(): static
    {
        return $this->state(fn (array $attributes) => [
            'email_notifications_enabled' => true,
            'notification_email' => $this->faker->email(),
        ]);
    }

    /**
     * Create settings with all notifications disabled.
     */
    public function withNotificationsDisabled(): static
    {
        return $this->state(fn (array $attributes) => [
            'slack_notifications_enabled' => false,
            'email_notifications_enabled' => false,
            'slack_webhook_url' => null,
            'notification_email' => null,
        ]);
    }
}