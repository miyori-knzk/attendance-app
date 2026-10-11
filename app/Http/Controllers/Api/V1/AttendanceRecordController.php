<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\IndexAttendanceRecordRequest;
use App\Http\Requests\Api\V1\StoreAttendanceRecordRequest;
use App\Http\Requests\Api\V1\UpdateAttendanceRecordRequest;
use App\Http\Resources\AttendanceRecordResource;
use App\Models\AttendanceRecord;
use App\Services\AttendanceService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class AttendanceRecordController extends Controller
{
    private AttendanceService $attendanceService;

    /**
     * AttendanceController constructor
     */
    public function __construct(AttendanceService $attendanceService)
    {
        $this->attendanceService = $attendanceService;
    }

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
     * @param  StoreAttendanceRecordRequest  $request  バリデーション済みデータ
     * @return AttendanceRecordResource 特定の勤怠データのみ返す
     */
    public function store(StoreAttendanceRecordRequest $request): JsonResponse
    {
        $attendanceRecord = $this->attendanceService->apiStore($request);

        return (new AttendanceRecordResource($attendanceRecord))->response()->setStatusCode(201);
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
     * 指定IDの勤怠を更新
     *
     * @param  AttendanceRecord  $attendanceRecord  ルートモデルバインディングでAttendanceRecordを取得
     * @return AttendanceRecordResource 特定の勤怠データのみ返す
     */
    public function update(UpdateAttendanceRecordRequest $request, AttendanceRecord $attendanceRecord): AttendanceRecordResource
    {
        $this->authorize('update', $attendanceRecord);
        $attendanceRecord = $this->attendanceService->apiUpdate($request, $attendanceRecord);

        return new AttendanceRecordResource($attendanceRecord->load(['user', 'breakRecords']));
    }

    /**
     * 指定IDの勤怠を削除
     *
     * @param  AttendanceRecord  $attendanceRecord  ルートモデルバインディングでAttendanceRecordを取得
     * @return JsonResponse 削除成功時はボディなしで返す
     */
    public function destroy(AttendanceRecord $attendanceRecord): JsonResponse
    {
        $this->authorize('delete', $attendanceRecord);

        $attendanceRecord->delete();

        return response()->json(null, 204);
    }
}
