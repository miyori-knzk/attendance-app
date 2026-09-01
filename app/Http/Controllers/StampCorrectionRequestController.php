<?php

namespace App\Http\Controllers;

use App\Models\Application;
use App\Models\Attendance;
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

        $query = Attendance::query();
        if ($isAdmin) {
            $view = 'admin.admin-application-list';
        } else {
            $query->where('user_id', $user->id);
            $data['user'] = auth()->user();
        }
        $attendances = $query->orderBy('date', 'asc')->with('application')->get();

        foreach ($attendances as $attendance) {
            if ($attendance->application) {
                $formattedApplications[] = [
                    'id' => $attendance->id,
                    'approval_status' => $attendance->application->approval_status,
                    'date' => dateFormat($attendance->date),
                    'comment' => $attendance->application->comment,
                    'application_date' => dateFormat($attendance->application->created_at),
                    'user' => $attendance->user,
                ];

            }
        }

        $data['formattedApplications'] = $formattedApplications;
        $data['applications'] = Application::all();

        return view($view, $data);
    }
}
