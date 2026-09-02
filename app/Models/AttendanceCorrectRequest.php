<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AttendanceCorrectRequest extends Model
{
    use HasFactory;

    protected $fillable = [
        'attendance_record_id',
        'status',
        'comment',

    ];

    public function getapprovalStatusAttribute(): string
    {
        return match ($this->status) {
            1 => '承認待ち',
            2 => '承認済み',
            default => '未申請',
        };
    }

    public function getProposalBreaksAttribute()
    {
        return $this->attendanceRecord->breakRecords;
    }

    public function getNewDateAttribute()
    {
        return dateToCarbon($this->attendanceRecord->date);
    }

    public function getNewClockInAttribute()
    {
        return $this->attendanceRecord->clock_in;
    }

    public function getNewClockOutAttribute()
    {
        return $this->attendanceRecord->clock_out;
    }

    public function attendanceRecord(): BelongsTo
    {
        return $this->belongsTo(AttendanceRecord::class);
    }

    public function getUserAttribute()
    {
        return $this->attendanceRecord->user;
    }

    public function getApplicationDateAttribute()
    {
        return $this->created_at;
    }
}
