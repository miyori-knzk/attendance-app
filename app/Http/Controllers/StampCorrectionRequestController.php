<?php

namespace App\Http\Controllers;

use App\Models\AttendanceCorrectRequest;
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

        $query = AttendanceCorrectRequest::query();
        if ($isAdmin) {
            $view = 'admin.admin-application-list';
        } else {
            $data['user'] = auth()->user();
        }

        $applications = $query->orderBy('created_at', 'asc')->with('attendanceRecord')->get();

        foreach ($applications as $application) {
            if ($application->user->id == $user->id) {
                $formattedApplications[] = [
                    'id' => $application->attendance_id,
                    'approval_status' => $application->approval_status,
                    'date' => dateFormat($application->attendanceRecord->date),
                    'comment' => $application->comment,
                    'application_date' => dateFormat($application->created_at),
                    'user' => $application->user,
                ];
            }
        }

        $data['formattedApplications'] = $formattedApplications;
        $data['applications'] = AttendanceCorrectRequest::all();

        return view($view, $data);
    }
}
