<?php

namespace App\Http\Controllers;

use App\Http\Requests\AttendanceIndexRequest;
use App\Http\Requests\AttendanceStoreRequest;
use App\Http\Requests\CorrectStoreRequest;
use App\Models\AttendanceRecord;
use App\Services\AttendanceService;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

class AttendanceController extends Controller
{
    private $attendanceService;

    public function __construct(AttendanceService $attendanceService)
    {
        $this->attendanceService = $attendanceService;
    }

    /**
     * 勤怠登録画面の表示
     */
    public function create(): View
    {
        $user = auth()->user();
        $formattedDate = date('Y年n月j日', strtotime('today'));
        $formattedTime = now()->format('H:i');

        return view('user.attendance-register', compact('user', 'formattedDate', 'formattedTime'));
    }

    /**
     * 出退勤、休憩入り戻りを記録
     * $actionはAttendanceStoreRequestでバリデーション済みの値で、
     * cloci_in, clock_out, break_in, break_outのうちどれかが必須で入っている
     *
     * @param  AttendanceStoreRequest  $request  バリデーション済みリクエスト
     * @return RedirectResponse /attendance へリダイレクト
     */
    public function store(AttendanceStoreRequest $request): RedirectResponse
    {
        $validated = $request->validated();
        $action = $validated['action'];

        $this->attendanceService->storeAttendanceRecord($action);

        return Redirect('/attendance');
    }

    /**
     * 勤怠一覧画面を表示
     *
     * 処理の概要
     * ログインユーザーの勤怠情報をformatAttDatasで取得し、日別に
     *  ① 出勤時刻 ② 退勤時刻 ③ 休憩時間合計 ④ 実働時間合計 をビューに渡す。
     *
     * 取得結果は `$formattedAttendanceRecords` に配列形式でまとめる
     *
     * @param  AttendanceIndexRequest  $request  バリデーション済みリクエスト
     * @return View 勤怠一覧ページ
     */
    public function index(AttendanceIndexRequest $request)
    {

        // リクエストにdateがあれば、バリデート済みのdateを使用、
        // なければ今日の日付を使用

        $date = dateFormat(date('Y-m-d'));

        if (array_key_exists('date', $request->validated())) {
            $date = dateFormat($request->validated()['date']);
        }

        $previousMonth = getFirstOfMonth($date)->subMonth()->format('Y-m-d');
        $nextMonth = getFirstOfMonth($date)->addMonth()->format('Y-m-d');

        $formattedAttendanceRecords = $this->attendanceService->formatAttDatas($date);

        return view('user.user-attendance-list', compact('previousMonth', 'date', 'nextMonth', 'formattedAttendanceRecords'));
    }

    /**
     * 勤怠詳細画面を表示
     *
     * 処理の概要
     * AttendanceRecordのIDに基づく勤怠レコードを表示する。
     * 修正申請中であれば、修正申請中の出退勤と休憩データを表示
     * 修正申請がない場合、勤怠登録時の出退勤と休憩データ
     * 修正承認済みであれば、承認済みの出退勤と休憩データを表示する
     *
     * @param  int  $id  AttendanceRecord の ID
     * @return View 勤怠詳細ページ
     */
    public function edit($id)
    {
        $user = auth()->user();
        $data = $this->attendanceService->makeEditData($id);

        return view('user.user-detail', compact('data', 'user'));
    }

    /**
     * 打刻修正申請を保存
     *
     * @param  AttendanceUpdateRequest  $request  バリデート済みリクエストオブジェクト
     * @param  int  $id  AttendanceRecord の ID
     * @return RedirectResponse
     *
     * @throws ModelNotFoundException // AttendanceRecord が見つからない場合
     */
    public function requestStore(CorrectStoreRequest $request, $id)
    {
        $attendanceRecord = AttendanceRecord::findOrFail($id);
        $this->attendanceService->saveRequestRecord($request, $attendanceRecord);

        return Redirect('/attendance/detail/' . $attendanceRecord->id);
    }
}
