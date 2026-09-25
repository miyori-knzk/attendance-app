<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ClockRecord extends Model
{
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'attendance_record_id',
        'clock_in',
        'clock_out',
    ];

    /**
     * 対象データのの勤怠レコードを取得
     */
    public function attendance(): BelongsTo
    {
        return $this->belongsTo(AttendanceRecord::class);
    }
}
