<?php

namespace App\Models;

use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;

class User extends Authenticatable implements MustVerifyEmail
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

    /**
     * 勤怠レコードを取得
     */
    public function attendanceRecords(): HasMany
    {
        return $this->hasMany(AttendanceRecord::class);
    }

    /**
     * 対象日付の勤怠レコードを取得
     *
     * @return HasOne 今日の出勤データを返す
     */
    public function todayAttendance(): HasOne
    {
        return $this->hasOne(AttendanceRecord::class)
            ->whereDate('date', date('Y-m-d'));
    }

    /**
     * 一般ユーザーのみ取得
     *
     * @return Collection 一般ユーザーのみ取得したデータを返す
     */
    public static function getNomalUser(): Collection
    {
        return self::whereNull('admin_status')->get();
    }

    /**
     * 出退勤登録時のステータスを表示
     *
     * @return string 出勤中、休憩中、退勤済のステータスを返す
     */
    public function getAttendanceStatusAttribute(): string
    {
        $todayAttndance = $this->todayAttendance;

        if (! $todayAttndance) {
            return '勤務外';
        }

        $breakStatus = $todayAttndance->breakRecords()->orderBy('break_in', 'desc')->orderBy('id', 'desc')->first();

        if ($todayAttndance->clockRecord->clock_out) {
            return '退勤済';
        } elseif ($breakStatus && $breakStatus->break_in && ! $breakStatus->break_out) {
            return '休憩中';
        } else {
            return '出勤中';
        }
    }
}
