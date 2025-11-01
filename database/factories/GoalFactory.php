<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Goal>
 */
class GoalFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'user_id' => \App\Models\User::factory(),
            'title' => $this->faker->sentence(4),
            'description' => $this->faker->paragraph(),
            'end_date' => $this->faker->dateTimeBetween('+1 week', '+6 months')->format('Y-m-d'),
            'status' => $this->faker->randomElement(['active', 'completed', 'cancelled']),
            'completed_at' => null,
        ];
    }

    /**
     * Indicate that the goal is active.
     */
    public function active(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'active',
            'completed_at' => null,
        ]);
    }

    /**
     * Indicate that the goal is completed.
     */
    public function completed(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'completed',
            'completed_at' => $this->faker->dateTimeBetween('-1 month', 'now'),
        ]);
    }

    /**
     * Indicate that the goal is overdue.
     */
    public function overdue(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'active',
            'end_date' => $this->faker->dateTimeBetween('-1 month', '-1 day')->format('Y-m-d'),
            'completed_at' => null,
        ]);
    }
}
