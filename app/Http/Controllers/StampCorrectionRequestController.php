<?php

namespace App\Http\Controllers;

use App\Models\AttendanceCorrectRequest;
use App\Models\AttendanceRecord;
use Illuminate\Http\Request;

class StampCorrectionRequestController extends Controller
{
    public function index(Request $request)
    {

        $isAdmin = $request->attributes->get('is_admin', false);

        $formattedApplications = [];
        $data = [];
        $view = 'user.user-application-list';
        $user = Auth()->user();

        $query = AttendanceRecord::query();
        if ($isAdmin) {
            $view = 'admin.admin-application-list';
        } else {
            $query->where('user_id', $user->id);
            $data['user'] = auth()->user();
        }

        $attendances = $query->orderBy('date', 'asc')->with('attendanceCorrectRequest')->get();

        foreach ($attendances as $attendance) {
            if ($attendance->attendanceCorrectRequest) {
                $formattedApplications[] = [
                    'id' => $attendance->id,
                    'approval_status' => $attendance->attendanceCorrectRequest->approval_status,
                    'date' => dateFormat($attendance->date),
                    'comment' => $attendance->attendanceCorrectRequest->comment,
                    'application_date' => dateFormat($attendance->attendanceCorrectRequest->created_at),
                    'user' => $attendance->user,
                ];

            }
        }

        $data['formattedApplications'] = $formattedApplications;
        $data['applications'] = AttendanceCorrectRequest::all();

        return view($view, $data);
    }
}
