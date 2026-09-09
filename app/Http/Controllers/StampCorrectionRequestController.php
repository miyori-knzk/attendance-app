<?php

namespace App\Http\Controllers;

use App\Models\AttendanceCorrectRequest;
use App\Services\StampCorrectionRequestService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class StampCorrectionRequestController extends Controller
{
    private $stampCorrectionRequestService;

    public function __construct(StampCorrectionRequestService $stampCorrectionRequestService)
    {
        $this->stampCorrectionRequestService = $stampCorrectionRequestService;
    }

    /**
     * 申請一覧画面の表示
     * 管理者はユーザー全員の申請が表示
     * 一般ユーザーは自分の申請のみ表示
     * ミドルウエアで表示するビューを設定している
     *
     * @param  Request  $request  HTTP リクエスト
     * @return View
     */
    public function index(Request $request)
    {
        $data = [];
        $formattedApplications = [];
        $user = Auth()->user();

        $applications = AttendanceCorrectRequest::orderBy('created_at', 'asc')->with('attendanceRecord')->get();
        $formattedApplications = $this->stampCorrectionRequestService->formatUsersAppData($applications, $user);
        $view = $request->attributes->get('view');

        $data['formattedApplications'] = $formattedApplications;
        $data['applications'] = $applications;
        $data['user'] = $user;

        return view($view, $data);
    }

    /**
     * 申請詳細画面の表示
     *
     * @param  int  $attendanceCorrectRequestId  AttendanceCorrectRequestのID
     * @return View
     */
    public function show($attendanceCorrectRequestId)
    {
        $application = AttendanceCorrectRequest::findOrFail($attendanceCorrectRequestId);
        $user = $application->getUserAttribute();

        return view('admin.admin-application-detail', compact('application', 'user'));
    }

    /**
     * 申請の承認処理
     *
     * @param  int  $attendanceCorrectRequestId  AttendanceCorrectRequestのID
     * @return RedirectResponse
     */
    public function update($attendanceCorrectRequestId)
    {
        $this->stampCorrectionRequestService->approve($attendanceCorrectRequestId);

        return redirect('/stamp_correction_request/approve/' . $attendanceCorrectRequestId);
    }
}
