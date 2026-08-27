<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class AttendanceApplicationStatus extends Model
{
    use HasFactory;

    public function attendance(): BelongsTo
    {
        return $this->belongsTo(Attendance::class);
    }
}
