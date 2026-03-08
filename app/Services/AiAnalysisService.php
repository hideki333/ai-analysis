<?php

namespace App\Services;

use App\Models\AiAnalysisLog;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Carbon\Carbon;

class AiAnalysisService
{
    private string $apiUrl;
    private bool   $useMock;

    public function __construct()
    {
        $this->apiUrl  = config('services.ai_analysis.url');
        $this->useMock = config('services.ai_analysis.use_mock', false);
    }

    /**
     * 画像パスをAI分析APIに送信し、結果をDBに保存する
     *
     * @param string $mockMode モックAPI使用時のレスポンス種別: 'success' | 'failure' | 'invalid'
     */
    public function analyze(string $imagePath, string $mockMode = 'success'): AiAnalysisLog
    {
        $requestTimestamp = Carbon::now();
        $log = new AiAnalysisLog();

        try {
            // AI_USE_MOCK=true（ローカル開発）: php artisan serve のシングルスレッド問題を
            // 回避するためHTTP通信せずモックレスポンスを直接生成する
            // AI_USE_MOCK=false（Docker）: 実際にHTTPリクエストを送信する
            $body = $this->useMock
                ? $this->callMockDirectly($mockMode)
                : $this->callApi($imagePath, $mockMode);

            $responseTimestamp = Carbon::now();

            // レスポンス構造の検証
            $validationError = $this->validateResponse($body);
            if ($validationError !== null) {
                Log::warning('AI Analysis API invalid response', [
                    'image_path' => $imagePath,
                    'body'       => $body,
                    'reason'     => $validationError,
                ]);
                $log->fill([
                    'image_path'         => $imagePath,
                    'success'            => false,
                    'message'            => '不正なレスポンス: ' . $validationError,
                    'class'              => null,
                    'confidence'         => null,
                    'request_timestamp'  => $requestTimestamp,
                    'response_timestamp' => $responseTimestamp,
                ]);
            } else {
                $success = (bool) $body['success'];
                $log->fill([
                    'image_path'         => $imagePath,
                    'success'            => $success,
                    'message'            => $body['message'],
                    'class'              => $success ? (int) $body['estimated_data']['class'] : null,
                    'confidence'         => $success ? (float) $body['estimated_data']['confidence'] : null,
                    'request_timestamp'  => $requestTimestamp,
                    'response_timestamp' => $responseTimestamp,
                ]);
            }

        } catch (\Throwable $e) {
            Log::error('AI Analysis API error', ['error' => $e->getMessage(), 'image_path' => $imagePath]);

            $log->fill([
                'image_path'         => $imagePath,
                'success'            => false,
                'message'            => 'API接続エラー: ' . $e->getMessage(),
                'class'              => null,
                'confidence'         => null,
                'request_timestamp'  => $requestTimestamp,
                'response_timestamp' => Carbon::now(),
            ]);
        }

        $log->save();

        return $log;
    }

    /**
     * APIにHTTPリクエストを送信する（Docker環境など、マルチプロセス環境で使用）
     *
     * @throws \RuntimeException HTTPエラーステータスの場合
     */
    private function callApi(string $imagePath, string $mockMode): ?array
    {
        $response = Http::timeout(30)->post($this->apiUrl, [
            'image_path'  => $imagePath,
            'mock_result' => $mockMode,
        ]);

        if ($response->failed()) {
            throw new \RuntimeException(
                "HTTPエラー: ステータスコード {$response->status()}"
            );
        }

        return $response->json();
    }

    /**
     * モックレスポンスを直接生成する
     * （php artisan serve のシングルスレッド問題を回避するためのローカル開発用）
     *
     * @param string $mode 'success' | 'failure' | 'invalid'
     */
    private function callMockDirectly(string $mode): array
    {
        return match ($mode) {
            'failure' => [
                'success' => false,
                'message' => 'Analysis failed due to an internal error.',
            ],
            'invalid' => [
                'success' => true,
                'message' => 'success',
            ],
            default => [
                'success' => true,
                'message' => 'success',
                'estimated_data' => [
                    'class'      => mt_rand(1, 5),
                    'confidence' => round(mt_rand(5000, 9999) / 10000, 4),
                ],
            ],
        };
    }

    /**
     * レスポンス構造をAPI仕様に沿って検証する
     *
     * 以下のいずれかに該当する場合は異常とみなしエラーメッセージを返す:
     * - JSONがnull（非JSONレスポンス）
     * - 'success' フィールドが存在しない、またはboolでない
     * - 'message' フィールドが存在しない
     * - success=true なのに 'estimated_data' が存在しない
     * - success=true なのに 'class' が整数でない
     * - success=true なのに 'confidence' が0〜1の数値でない
     *
     * @return string|null 正常ならnull、異常なら理由文字列
     */
    private function validateResponse(?array $body): ?string
    {
        if ($body === null) {
            return 'レスポンスがJSONではありません';
        }

        if (!array_key_exists('success', $body) || !is_bool($body['success'])) {
            return "'success' フィールドがbool型で存在しません";
        }

        if (!array_key_exists('message', $body) || !is_string($body['message'])) {
            return "'message' フィールドが文字列で存在しません";
        }

        if ($body['success'] === true) {
            if (!isset($body['estimated_data']) || !is_array($body['estimated_data'])) {
                return "success=true なのに 'estimated_data' が存在しません";
            }

            $data = $body['estimated_data'];

            if (!isset($data['class']) || !is_int($data['class'])) {
                return "'estimated_data.class' が整数で存在しません";
            }

            if (!isset($data['confidence']) || !is_numeric($data['confidence'])
                || $data['confidence'] < 0 || $data['confidence'] > 1) {
                return "'estimated_data.confidence' が0〜1の数値で存在しません";
            }
        }

        return null;
    }
}
