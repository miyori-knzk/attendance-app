<?php

namespace Database\Factories;

use App\Models\AttendanceRecord;
use App\Models\ClockRecord;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<ClockRecord>
 */
class ClockRecordFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'attendance_record_id' => AttendanceRecord::factory(),
            'clock_in' => $this->faker->time(),
            'clock_out' => $this->faker->time(),
        ];
    }
}
