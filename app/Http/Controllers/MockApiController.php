<?php

namespace App\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * 外部AI分析APIのモック
 *
 * 実際のAPIが存在しないため、ランダムなレスポンスを返すことで
 * Success / Failure の両パターンをシミュレートする。
 */
class MockApiController extends Controller
{
    public function analyze(Request $request): JsonResponse
    {
        $imagePath  = $request->input('image_path');
        $mockResult = $request->input('mock_result', 'success');

        if (empty($imagePath)) {
            return response()->json([
                'success' => false,
                'message' => 'image_path is required.',
            ], 400);
        }

        return match ($mockResult) {
            'failure' => response()->json([
                'success' => false,
                'message' => 'Analysis failed due to an internal error.',
            ]),
            'invalid' => response()->json([
                // 仕様外レスポンスのシミュレート（estimated_dataが欠落）
                'success' => true,
                'message' => 'success',
            ]),
            default => $this->successResponse(),
        };
    }

    /**
     * 成功レスポンスを返す
     */
    private function successResponse(): JsonResponse
    {
        return response()->json([
            'success' => true,
            'message' => 'success',
            'estimated_data' => [
                'class'      => mt_rand(1, 5),
                'confidence' => round(mt_rand(5000, 9999) / 10000, 4),
            ],
        ]);
    }
}
