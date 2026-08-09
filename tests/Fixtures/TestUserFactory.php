<?php

declare(strict_types=1);

namespace IzAhmad\TurboSeeder\Tests\Fixtures;

use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<TestUserModel>
 */
class TestUserFactory extends Factory
{
    protected $model = TestUserModel::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'name' => $this->faker->name(),
            'email' => $this->faker->unique()->safeEmail(),
            'age' => $this->faker->numberBetween(18, 65),
        ];
    }

    /**
     * State with a fixed, known age for assertions.
     */
    public function adult(): static
    {
        return $this->state(fn () => ['age' => 40]);
    }
}
