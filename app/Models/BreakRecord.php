<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class BreakRecord extends Model
{
    use HasFactory;

    protected $fillable = [
        'attendance_record_id',
        'break_in',
        'break_out',
    ];

    public function getBreakInAttribute($value)
    {
        $default = null;
        if ($value) {
            $default = date('H:i', strtotime($value));
        }

        return $default;
    }

    public function getBreakOutAttribute($value)
    {
        $default = null;
        if ($value) {
            $default = date('H:i', strtotime($value));
        }

        return $default;
    }

    public function attendance(): BelongsTo
    {
        return $this->belongsTo(AttendanceRecord::class);
    }
}
