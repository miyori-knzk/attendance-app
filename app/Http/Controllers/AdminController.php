<?php

namespace App\Http\Controllers;

use App\Http\Requests\AdminIndexRequest;
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

    public function edit(int $id)
    {
        $attendanceRecord = $this->attendanceService->makeEditData($id);
        $user = User::findOrFail($attendanceRecord['user_id']);

        return view('admin.admin-detail', compact('attendanceRecord', 'user'));
    }
}
