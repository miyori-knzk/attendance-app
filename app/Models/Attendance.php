<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class Attendance extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'date',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function clockRecord(): HasOne
    {
        return $this->hasOne(ClockRecord::class);
    }

    public function breakRecords(): HasMany
    {
        return $this->hasMany(BreakRecord::class);
    }

    public function applicationStatus(): HasOne
    {
        return $this->hasOne(AttendanceApplicationStatus::class);
    }
}
