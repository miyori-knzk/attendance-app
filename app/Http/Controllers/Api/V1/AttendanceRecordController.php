<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\IndexAttendanceRecordRequest;
use App\Http\Resources\AttendanceRecordResource;
use App\Models\AttendanceRecord;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class AttendanceRecordController extends Controller
{
    /**
     * 勤怠一覧API
     *
     * @param  IndexAttendanceRecordRequest  $request  バリデーション済みのリクエスト
     * @return AnonymousResourceCollection data,links,meta を付与して返す
     */
    public function index(IndexAttendanceRecordRequest $request): AnonymousResourceCollection
    {
        $perPage = 20;
        $validated = $request->validated();

        if (array_key_exists('per_page', $validated)) {
            $perPage = $validated['per_page'];
        }

        $query = AttendanceRecord::makeQuery($validated);
        $attendanceRecords = $query->with('user', 'clockRecord', 'breakRecords')->latest('date')->paginate($perPage);

        return AttendanceRecordResource::collection($attendanceRecords);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        //
    }

    /**
     * @param  AttendanceRecord  $attendanceRecord  ルーとモデルバインディングでAttendanceRecordを取得
     * @return AttendanceRecordResource 特定の勤怠データのみ返す
     */
    public function show(AttendanceRecord $attendanceRecord): AttendanceRecordResource
    {
        $attendanceRecord->load(['user', 'clockRecord', 'breakRecords', 'attendanceCorrectRequests']);

        return new AttendanceRecordResource($attendanceRecord);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, string $id)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        //
    }
}
