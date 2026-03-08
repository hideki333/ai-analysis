<?php

use App\Http\Controllers\AiAnalysisController;
use App\Http\Controllers\MockApiController;
use Illuminate\Support\Facades\Route;

// UI画面
Route::get('/', [AiAnalysisController::class, 'index'])->name('analysis.index');
Route::post('/analyze', [AiAnalysisController::class, 'analyze'])->name('analysis.analyze');

// モックAPI (本来は外部サービスだが、開発・テスト用として同一アプリ内に実装)
Route::post('/api/mock/analyze', [MockApiController::class, 'analyze'])->name('mock.analyze');
