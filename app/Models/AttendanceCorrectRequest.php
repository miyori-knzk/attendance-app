<?php

namespace App\Models;

use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class AttendanceCorrectRequest extends Model
{
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'attendance_record_id',
        'status',
        'comment',
    ];

    /**
     * 承認ステータスを日本語に変換
     *
     * @return string 承認待ち、承認済み、未申請（実際は使用されない）
     */
    public function getapprovalStatusAttribute(): string
    {
        $status = '未申請';

        if ($this->status == 1) {
            $status = '承認待ち';
        } elseif ($this->status >= 2) {
            $status = '承認済み';
        }

        return $status;
    }

    /**
     * proposal_breaksのアクセサ
     */
    public function getProposalBreaksAttribute(): Collection
    {
        return $this->breakCorrectRequests;
    }

    /**
     * AttendanceRecord の date カラムを CarbonImmutable に変換した値を取得。
     */
    public function getNewDateAttribute(): CarbonImmutable
    {
        return dateformat($this->attendanceRecord->date);
    }

    /**
     * 修正申請の出勤時刻を取得。
     */
    public function getNewClockInAttribute(): string
    {
        return $this->clockCorrectRequest->new_clock_in;
    }

    /**
     * 修正申請の退勤時刻を取得。
     */
    public function getNewClockOutAttribute(): string
    {
        return $this->clockCorrectRequest->new_clock_out;
    }

    /**
     *  修正申請のもとになるAttendanceRecordを取得
     *
     * @return BelongsTo<AttendanceRecord>
     */
    public function attendanceRecord(): BelongsTo
    {
        return $this->belongsTo(AttendanceRecord::class);
    }

    /**
     * 修正申請の休憩データを取得
     *
     * @return BelongsTo<BreakCorrectRequest>
     */
    public function breakCorrectRequests(): HasMany
    {
        return $this->hasMany(BreakCorrectRequest::class);
    }

    /**
     * 修正申請の出退勤データを取得
     *
     * @return HasOne<ClockCorrectRequest>
     */
    public function clockCorrectRequest(): HasOne
    {
        return $this->hasOne(ClockCorrectRequest::class);
    }

    /**
     * 修正申請のユーザーを取得
     */
    public function getUserAttribute(): User
    {
        return $this->attendanceRecord->user;
    }

    /**
     * 修正申請日をcreated_atとして扱う
     *
     * @return \Immutable\Support\Carbon
     */
    public function getApplicationDateAttribute(): Carbon
    {
        return $this->created_at;
    }
}
