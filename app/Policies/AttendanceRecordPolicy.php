<?php

namespace App\Policies;

use App\Models\AttendanceRecord;
use App\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;
use Illuminate\Auth\Access\Response;

class AttendanceRecordPolicy
{
    use HandlesAuthorization;

    /**
     * 管理者は全部OK
     */
    public function before(User $user)
    {
        if ($user->admin_status == 1) {
            return true;
        }
    }

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
    public function update(User $user, AttendanceRecord $attendanceRecord): bool|Response
    {
        if ($user->id !== $attendanceRecord->user_id) {
            return $this->deny('この操作を実行する権限がありません。');
        }

        return true;
    }

    /**
     * Determine whether the user can delete the model.
     */
    public function delete(User $user, AttendanceRecord $attendanceRecord): bool|Response
    {
        if ($user->id !== $attendanceRecord->user_id) {
            return $this->deny('この操作を実行する権限がありません。');
        }

        return true;
    }
}
