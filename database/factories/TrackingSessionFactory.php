<?php

namespace Database\Factories;

use App\Models\TrackingSession;
use Illuminate\Database\Eloquent\Factories\Factory;

class TrackingSessionFactory extends Factory
{
    protected $model = TrackingSession::class;

    public function definition(): array
    {
        $startedAt = $this->faker->dateTimeBetween('-1 month', 'now');

        return [
            'session_uuid' => (string) \Illuminate\Support\Str::uuid(),
            'started_at' => $startedAt,
            'ended_at' => $this->faker->boolean(50)
                ? $this->faker->dateTimeBetween($startedAt, 'now')
                : null,
            'created_at' => now(),
            'updated_at' => now(),
        ];
    }
}
