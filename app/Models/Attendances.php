<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Attendances extends Model
{
    use HasFactory;

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function status(): HasMany
    {
        return $this->hasMany(AttendanceStatus::class);
    }

    public function applicationStatus(): hasOne
    {
        return $this->hasMany(AttendanceApplicationStatus::class);
    }
}
