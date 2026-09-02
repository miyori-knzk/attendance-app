<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class AttendanceRecord extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'date',
        'clock_in',
        'clock_out',
    ];

    public function getDateAttribute($value)
    {
        return date('Y-m-d', strtotime($value));
    }

    public function getClockInAttribute($value)
    {
        $default = null;
        if ($value) {
            $default = date('H:i', strtotime($value));
        }

        return $default;
    }

    public function getClockOutAttribute($value)
    {
        $default = null;
        if ($value) {
            $default = date('H:i', strtotime($value));
        }

        return $default;
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class)->withDefault();
    }

    public function breakRecords(): HasMany
    {
        return $this->hasMany(BreakRecord::class);
    }

    public function attendanceCorrectRequest(): HasOne
    {
        return $this->hasOne(AttendanceCorrectRequest::class)->withDefault();
    }

    public function requestIsPending()
    {
        $pending = null;

        if ($this->attendanceCorrectRequest->status == 1) {
            $pending = $this->attendanceCorrectRequest;
        }

        return $pending;
    }
}
