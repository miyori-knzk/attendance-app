<?php

namespace App\Models;

use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class AttendanceRecord extends Model
{
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'user_id',
        'date',
        'comment',
    ];

    /**
     * ユーザーの月ごとの勤怠を取得
     *
     * @param  CarbonImmutable  $date  この日付が入る月を対象の月としてデータを取得
     * @param  User|null  $user  対象のユーザー　nullの時はログインユーザーを使用
     * @return Collection&static[]
     */
    public static function getMonthUserData(CarbonImmutable $date, ?User $user = null): Collection
    {
        if ($user === null) {
            $user = auth()->user();
        }

        $firstOfMonth = $date->firstOfMonth()->format('Y-m-d');
        $endOfMonth = $date->endOfMonth()->format('Y-m-d');

        return self::where('user_id', $user->id)
            ->where('date', '>=', $firstOfMonth)
            ->where('date', '<=', $endOfMonth)
            ->with('clockRecord', 'breakRecords')
            ->orderBy('date')
            ->get();
    }

    /**
     * @param  $validated  IndexAttendanceRecordRequestでバリデーション済みのデータ
     * @return Builder パラメーターの条件適用済みのBuilder
     */
    public static function makeQuery(array $validated): Builder
    {
        $query = self::query();

        if (array_key_exists('user_id', $validated)) {
            $query->where('user_id', $validated['user_id']);
        }
        if (array_key_exists('date', $validated)) {
            $query->where('date', $validated['date']);
        }
        if (array_key_exists('month', $validated)) {
            $formatedMonth = CarbonImmutable::parse($validated['month']);
            $firstOfMonth = getFirstOfMonth($formatedMonth)->format('Y-m-d');
            $endOfMonth = getEndOfMonth($formatedMonth)->format('Y-m-d');
            $query->where('date', '>=', $firstOfMonth)
                ->where('date', '<=', $endOfMonth);
        }

        return $query;
    }

    /**
     * 対象の日付のデータを取得
     */
    public static function todayData(string $date): Builder
    {
        return self::where('date', $date);
    }

    /**
     * 休憩中のデータを取得
     */
    public function onBreakData(): ?BreakRecord
    {
        return $this->breakRecords->whereNull('break_out')->first();
    }

    /**
     * 最新の勤怠レコードを取得
     */
    public static function getLatestAttendance(User $user): ?AttendanceRecord
    {
        return self::where('user_id', $user->id)->orderBy('date', 'desc')->first();
    }

    /**
     * 該当の勤怠レコードのユーザーを取得
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class)->withDefault();
    }

    /**
     * 対象データの休憩レコードを取得
     *
     * @return \Immutable\Database\Eloquent\Relations\HasMany
     */
    public function breakRecords(): HasMany
    {
        return $this->hasMany(BreakRecord::class);
    }

    /**
     * 対象データの出退勤レコードを取得
     *
     * @return \Illuminate\Database\Eloquent\Relations\HasOne;
     */
    public function clockRecord(): HasOne
    {
        return $this->hasOne(ClockRecord::class)->withDefault();
    }

    /**
     * 対象データの修正申請レコードを取得
     *
     * @return Illuminate\Database\Eloquent\Relations\HasMany;
     */
    public function attendanceCorrectRequests(): HasMany
    {
        return $this->hasMany(AttendanceCorrectRequest::class);
    }

    /**
     * 承認待ちの修正申請を取得
     */
    public function requestIsPending(): ?AttendanceCorrectRequest
    {
        return $this->attendanceCorrectRequests()->where('status', 1)->first();
    }
}
