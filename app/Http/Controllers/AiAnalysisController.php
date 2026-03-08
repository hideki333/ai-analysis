<?php

namespace App\Http\Controllers;

use App\Models\AiAnalysisLog;
use App\Services\AiAnalysisService;
use Illuminate\Http\Request;

class AiAnalysisController extends Controller
{
    public function __construct(private AiAnalysisService $service) {}

    /**
     * 分析フォーム画面
     */
    public function index()
    {
        $logs = AiAnalysisLog::orderByDesc('id')->limit(20)->get();
        return view('analysis.index', compact('logs'));
    }

    /**
     * 分析実行 → DBへ保存 → 画面にリダイレクト
     */
    public function analyze(Request $request)
    {
        $request->validate([
            'image_path'  => 'required|string|max:255',
            'mock_result' => 'in:success,failure,invalid',
        ]);

        $mockMode = $request->input('mock_result', 'success');
        $log = $this->service->analyze($request->input('image_path'), $mockMode);

        $message = $log->success
            ? "分析成功: Class={$log->class}, Confidence={$log->confidence}"
            : "分析失敗: {$log->message}";

        return redirect()->route('analysis.index')->with(
            $log->success ? 'success' : 'error',
            $message
        );
    }
}
