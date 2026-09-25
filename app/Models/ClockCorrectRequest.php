<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ClockCorrectRequest extends Model
{
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'attendance_correct_request_id',
        'new_clock_in',
        'new_clock_out',
    ];

    /**
     * 対象データの修正申請レコードを取得
     *
     * @return Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function attendanceCorrectRequest(): BelongsTo
    {
        return $this->belongsTo(AttendanceCorrectRequest::class);
    }
}
