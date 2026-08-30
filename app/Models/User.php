<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;

class User extends Authenticatable
{
    use HasApiTokens, HasFactory, Notifiable;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'name',
        'email',
        'password',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var array<int, string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'email_verified_at' => 'datetime',
        'password' => 'hashed',
    ];

    public function attendances(): HasMany
    {
        return $this->hasMany(Attendance::class);
    }

    public function todayAttendance(): HasOne
    {
        return $this->hasOne(Attendance::class)
            ->whereDate('date', date('Y-m-d'));
    }

    public function getAttendanceStatusAttribute(): string
    {
        $todayAttndance = $this->todayAttendance;

        if (! $todayAttndance) {
            return '勤務外';
        }

        $clockStatus = $todayAttndance->clockRecord;
        $breakStatus = $todayAttndance->breakRecords()->orderBy('break_in', 'desc')->firstOrNew();

        if ($clockStatus->clock_out) {
            return '退勤済';
        } elseif ($breakStatus->break_in && ! $breakStatus->break_out) {
            return '休憩中';
        } else {
            return '出勤中';
        }
    }
}
