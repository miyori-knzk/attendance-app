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
        'comment',
    ];

    public static function getMonthUserData($startDay, $endDay, $user = null)
    {
        if ($user === null) {
            $user = auth()->user();
        }

        return self::where('user_id', $user->id)
            ->where('date', '>=', $startDay)
            ->where('date', '<=', $endDay)
            ->with('clockRecord', 'breakRecords')
            ->orderBy('date')
            ->get();
    }

    public function onBreakData()
    {
        return $this->breakRecords->whereNull('break_out')->first();
    }

    public static function getLatestAttendance($user)
    {
        return self::where('user_id', $user->id)->orderBy('date', 'desc')->first();
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class)->withDefault();
    }

    public function breakRecords(): HasMany
    {
        return $this->hasMany(BreakRecord::class);
    }

    public function clockRecord(): HasOne
    {
        return $this->hasOne(ClockRecord::class);
    }

    public function attendanceCorrectRequests(): HasMany
    {
        return $this->hasMany(AttendanceCorrectRequest::class);
    }

    public function requestIsPending()
    {
        return $this->attendanceCorrectRequests()->where('status', 1)->first();
    }
}
