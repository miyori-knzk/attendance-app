<?php

namespace App\Http\Controllers;

use App\Http\Requests\ExportControllerRequest;
use App\Services\ExportService;
use Symfony\Component\HttpFoundation\StreamedResponse;

class ExportController extends Controller
{
    private ExportService $exportService;

    /**
     * ExportController constructor
     *
     * @param  ExportService  $exportService  CSV出力に関する処理をする
     */
    public function __construct(ExportService $exportService)
    {
        $this->exportService = $exportService;
    }

    /**
     * CSVデータの出力
     *
     * @param  App\Http\Requests\ExportControllerRequest  $request  バリデーション済みのデータ(user_id,year_monthが格納されている)
     * @return StreamedResponse CSVデータの出力
     */
    public function export(ExportControllerRequest $request): StreamedResponse
    {
        $validated = $request->validated();
        $data = $this->exportService->makeExportData($validated);

        $callback = $data['callback'];
        $filename = $data['filename'];
        $headers = $data['headers'];

        return response()->streamDownload($callback, $filename, $headers);
    }
}
