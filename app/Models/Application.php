<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Application extends Model
{
    use HasFactory;

    protected $fillable = [
        'attendance_id',
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

    public function attendanceRecord(): BelongsTo
    {
        return $this->belongsTo(Attendance::class, 'attendance_id', 'id');
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
