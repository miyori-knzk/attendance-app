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
        $status = '未申請';

        if ($this->status == 1) {
            $status = '承認待ち';
        } elseif ($this->status >= 2) {
            $status = '承認済み';
        }

        return $status;
    }

    public function getProposalBreaksAttribute()
    {
        return $this->attendanceRecord->breakRecords;
    }

    public function getNewDateAttribute()
    {
        return dateformat($this->attendanceRecord->date);
    }

    public function getNewClockInAttribute()
    {
        return $this->clockCorrectRequest->new_clock_in;
    }

    public function getNewClockOutAttribute()
    {
        return $this->clockCorrectRequest->new_clock_out;
    }

    public function attendanceRecord(): BelongsTo
    {
        return $this->belongsTo(AttendanceRecord::class);
    }

    public function breakCorrectRequests()
    {
        return $this->hasMany(BreakCorrectRequest::class);
    }

    public function clockCorrectRequest()
    {
        return $this->hasOne(ClockCorrectRequest::class);
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
