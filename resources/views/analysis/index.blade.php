<!DOCTYPE html>
<html lang="ja">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>AI画像分析システム</title>
    <style>
        * { box-sizing: border-box; margin: 0; padding: 0; }
        body { font-family: 'Segoe UI', sans-serif; background: #f0f2f5; color: #333; }
        .container { max-width: 960px; margin: 40px auto; padding: 0 20px; }
        h1 { font-size: 1.6rem; margin-bottom: 24px; color: #2c3e50; }
        h2 { font-size: 1.1rem; margin-bottom: 16px; color: #2c3e50; }

        .card {
            background: #fff;
            border-radius: 8px;
            padding: 24px;
            margin-bottom: 24px;
            box-shadow: 0 1px 4px rgba(0,0,0,.1);
        }

        .form-group { margin-bottom: 16px; }
        label { display: block; font-weight: 600; margin-bottom: 6px; font-size: .9rem; }
        input[type="text"] {
            width: 100%;
            padding: 10px 12px;
            border: 1px solid #ccc;
            border-radius: 6px;
            font-size: .95rem;
        }
        input[type="text"]:focus { outline: none; border-color: #3498db; box-shadow: 0 0 0 3px rgba(52,152,219,.2); }

        .hint { font-size: .8rem; color: #888; margin-top: 4px; }
        .error-text { font-size: .8rem; color: #e74c3c; margin-top: 4px; }

        .radio-group { display: flex; gap: 12px; }
        .radio-group label {
            display: flex; align-items: center; gap: 6px;
            font-weight: normal; cursor: pointer;
            padding: 8px 16px; border: 2px solid #ddd;
            border-radius: 6px; font-size: .9rem;
        }
        .radio-group input[type="radio"] { accent-color: #3498db; }
        .radio-group label:has(input:checked) { border-color: #3498db; background: #eaf4fd; }

        button[type="submit"] {
            background: #3498db;
            color: #fff;
            border: none;
            padding: 10px 28px;
            border-radius: 6px;
            font-size: .95rem;
            cursor: pointer;
        }
        button[type="submit"]:hover { background: #2980b9; }

        .alert {
            padding: 12px 16px;
            border-radius: 6px;
            margin-bottom: 20px;
            font-size: .9rem;
        }
        .alert-success { background: #d4edda; color: #155724; border: 1px solid #c3e6cb; }
        .alert-error   { background: #f8d7da; color: #721c24; border: 1px solid #f5c6cb; }

        table { width: 100%; border-collapse: collapse; font-size: .85rem; }
        th { background: #f8f9fa; padding: 10px 12px; text-align: left; border-bottom: 2px solid #dee2e6; }
        td { padding: 9px 12px; border-bottom: 1px solid #eee; vertical-align: middle; }
        tr:hover td { background: #fafafa; }

        .badge {
            display: inline-block;
            padding: 2px 8px;
            border-radius: 12px;
            font-size: .78rem;
            font-weight: 600;
        }
        .badge-success { background: #d4edda; color: #155724; }
        .badge-failure { background: #f8d7da; color: #721c24; }
        .no-data { text-align: center; color: #aaa; padding: 20px; }
    </style>
</head>
<body>
<div class="container">
    <h1>AI画像分析システム</h1>

    @if(session('success'))
        <div class="alert alert-success">{{ session('success') }}</div>
    @endif
    @if(session('error'))
        <div class="alert alert-error">{{ session('error') }}</div>
    @endif

    {{-- 分析リクエストフォーム --}}
    <div class="card">
        <h2>画像パスを入力して分析</h2>
        <form method="POST" action="{{ route('analysis.analyze') }}">
            @csrf
            <div class="form-group">
                <label for="image_path">画像ファイルパス</label>
                <input
                    type="text"
                    id="image_path"
                    name="image_path"
                    value="{{ old('image_path', '/image/d03f1d36ca69348c51aa/c413eac329e1c0d03/test.jpg') }}"
                    placeholder="/image/xxx/yyy/sample.jpg"
                >
                <p class="hint">例: /image/d03f1d36ca69348c51aa/c413eac329e1c0d03/test.jpg</p>
                @error('image_path')
                    <p class="error-text">{{ $message }}</p>
                @enderror
            </div>
            <div class="form-group">
                <label>モックレスポンス</label>
                <div class="radio-group">
                    <label>
                        <input type="radio" name="mock_result" value="success"
                            {{ old('mock_result', 'success') === 'success' ? 'checked' : '' }}>
                        Success
                    </label>
                    <label>
                        <input type="radio" name="mock_result" value="failure"
                            {{ old('mock_result', 'success') === 'failure' ? 'checked' : '' }}>
                        Failure
                    </label>
                    <label>
                        <input type="radio" name="mock_result" value="invalid"
                            {{ old('mock_result', 'success') === 'invalid' ? 'checked' : '' }}>
                        異常レスポンス
                    </label>
                </div>
                <p class="hint">「異常レスポンス」はAPI仕様外のレスポンス（estimated_data欠落）を返します</p>
            </div>
            <button type="submit">分析実行</button>
        </form>
    </div>

    {{-- 分析ログ一覧 --}}
    <div class="card">
        <h2>分析ログ（直近20件）</h2>
        @if($logs->isEmpty())
            <p class="no-data">まだ分析ログはありません。</p>
        @else
        <table>
            <thead>
                <tr>
                    <th>ID</th>
                    <th>結果</th>
                    <th>Class</th>
                    <th>Confidence</th>
                    <th>画像パス</th>
                    <th>メッセージ</th>
                    <th>リクエスト日時</th>
                    <th>レスポンス日時</th>
                </tr>
            </thead>
            <tbody>
                @foreach($logs as $log)
                <tr>
                    <td>{{ $log->id }}</td>
                    <td>
                        <span class="badge {{ $log->success ? 'badge-success' : 'badge-failure' }}">
                            {{ $log->success ? 'Success' : 'Failure' }}
                        </span>
                    </td>
                    <td>{{ $log->class ?? '-' }}</td>
                    <td>{{ $log->confidence !== null ? number_format($log->confidence, 4) : '-' }}</td>
                    <td style="max-width:200px; word-break:break-all;">{{ $log->image_path }}</td>
                    <td>{{ $log->message }}</td>
                    <td>{{ $log->request_timestamp }}</td>
                    <td>{{ $log->response_timestamp }}</td>
                </tr>
                @endforeach
            </tbody>
        </table>
        @endif
    </div>
</div>
</body>
</html>
