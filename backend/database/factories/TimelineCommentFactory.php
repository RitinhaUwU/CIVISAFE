<?php

namespace Database\Factories;

use App\Models\Incident;
use App\Models\TimelineComment;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Carbon;

class TimelineCommentFactory extends Factory
{
    protected $model = TimelineComment::class;

    public function definition(): array
    {
        return [
            'body' => $this->faker->paragraph(),
            'incident_id' => Incident::factory(),
            'user_id'     => User::factory(),
            'start_datetime' => Carbon::now(),
            'created_at' => Carbon::now(),
            'updated_at' => Carbon::now(),
        ];
    }
}
