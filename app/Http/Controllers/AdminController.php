<?php

namespace App\Http\Controllers;

use App\Http\Requests\AdminIndexRequest;
use App\Http\Requests\AttendanceUpdateRequest;
use App\Http\Requests\StaffAttendanceListRequest;
use App\Models\User;
use App\Services\AttendanceService;
use Illuminate\View\View;

class AdminController extends Controller
{
    private $attendanceService;

    /**
     * AttendanceController constructor.
     *
     * @param  AttendanceService  $attendanceService  勤怠に関する処理をする
     */
    public function __construct(AttendanceService $attendanceService)
    {
        $this->attendanceService = $attendanceService;
    }

    /**
     * 管理画面の勤怠一覧画面表示。
     *
     * @param  AdminIndexRequest  $request
     *                                      バリデーション済みで `date` キーがあればその日付、無ければ今日の日付を扱う。
     * @return View
     *              `admin.admin-attendance-list` ビューを返す。
     *              ビューに渡される変数は `$date`, `$previousDay`, `$nextDay`, `$users`,
     *              `$attendanceRecords` 。
     */
    public function index(AdminIndexRequest $request)
    {
        $validated = $request->validated();
        $date = dateFormat(date('Y-m-d'));

        if (array_key_exists('date', $validated)) {
            $date = dateFormat($validated['date']);
        }

        $previousDay = $date->subDay()->format('Y-m-d');
        $nextDay = $date->addDay()->format('Y-m-d');
        $users = User::all();

        $attendanceRecords = $this->attendanceService->attDatasCollection($date->format('Y-m-d'));

        return view('admin.admin-attendance-list', compact('date', 'previousDay', 'nextDay', 'users', 'attendanceRecords'));
    }

    /**
     * 管理者の勤怠詳細画面表示
     *
     * @param  int  $id  AttendanceRecordのID
     * @return View
     *              'admin.admin-detail' ビューを返す
     */
    public function edit(int $id)
    {
        $attendanceRecord = $this->attendanceService->makeEditData($id);
        $user = User::findOrFail($attendanceRecord['user_id']);

        return view('admin.admin-detail', compact('attendanceRecord', 'user'));
    }

    /**
     * 管理者の勤怠詳細画面からの修正
     *
     * @param  int  $id  AttenanceRecordのID
     * @return RedirectResponse 管理者の勤怠詳細画面にリダイレクト
     */
    public function update(AttendanceUpdateRequest $request, int $id)
    {
        $validated = $request->validated();
        $attendanceRecord = $this->attendanceService->updateAttendance($validated, $id);

        return redirect('/admin/attendance/' . $id);
    }

    /**
     * 選択したスタッフの勤怠一覧表示
     *
     * @param  StaffAttendanceListRequest  $request
     *                                               StaffAttendanceListRequest でバリデーション済みのリクエスト
     * @return View 'admin.staff-attendance-list'　へビューを返す
     */
    public function staffAttendance(StaffAttendanceListRequest $request, $id)
    {
        $user = User::findOrFail($id);
        // リクエストにdateがあれば、バリデート済みのdateを使用、
        // なければ今日の日付を使用
        $date = dateFormat(date('Y-m-d'));

        if (array_key_exists('date', $request->validated())) {
            $date = dateFormat($request->validated()['date']);
        }

        $previousMonth = getFirstOfMonth($date)->subMonth()->format('Y-m-d');
        $nextMonth = getFirstOfMonth($date)->addMonth()->format('Y-m-d');

        $formattedAttendanceRecords = $this->attendanceService->formatAttDatas($date, $user);

        return view('admin.staff-attendance-list', compact('date', 'user', 'previousMonth', 'nextMonth', 'formattedAttendanceRecords'));
    }

    /**
     * スタッフ一覧表示
     *
     * @return View 'admin.staff-list' へビューを返す
     */
    public function staffIndex()
    {
        $users = User::all();

        return view('admin.staff-list', compact('users'));
    }
}
