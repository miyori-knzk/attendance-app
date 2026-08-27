<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
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
        return $this->hasMany(Attendances::class);
    }

    public function todayAttendance(): HasOne
    {
        return $this->hasOne(Attendances::class)
            ->whereDate('date', Carbon::today());
    }

    public function getAttendanceStatusAttribute(): string
    {
        $todayAttndance = $this->todayAttendance;

        if (! $todayAttndance) {
            return '勤務外';
        }

        $latestStatus = $todayAttndance->status()->latest()->first();

        return match ($latestStatus->status) {
            1 => '勤務中',
            2 => '休憩中',
            3 => '退勤済',
            default => '勤務外',
        };
    }
}
