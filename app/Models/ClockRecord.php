<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ClockRecord extends Model
{
    use HasFactory;

    protected $fillable = [
        'attendance_id',
        'clock_in',
        'clock_out',
    ];

    public function attendance(): BelongsTo
    {
        return $this->belongsTo(Attendance::class);
    }
}
