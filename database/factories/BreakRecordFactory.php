<?php

namespace Database\Factories;

use App\Models\AttendanceRecord;
use App\Models\ClockRecord;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<ClockRecord>
 */
class BreakRecordFactory extends Factory
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
            'break_in' => $this->faker->time(),
            'break_out' => $this->faker->time(),
        ];
    }
}
