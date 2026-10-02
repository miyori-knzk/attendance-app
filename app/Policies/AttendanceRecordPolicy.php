<?php

namespace App\Policies;

use App\Models\AttendanceRecord;
use App\Models\User;

class AttendanceRecordPolicy
{
    /**
     * Determine whether the user can view the model.
     */
    public function view(User $user, AttendanceRecord $attendanceRecord): bool
    {
        return $user->id == $attendanceRecord->user_id;
    }

    /**
     * Determine whether the user can update the model.
     */
    public function update(User $user, AttendanceRecord $attendanceRecord): bool
    {
        return $user->id == $attendanceRecord->user_id;
    }
}
