# AI画像分析システム

画像ファイルのパスをAI分析APIに送信し、分析結果をデータベースに保存するシステムです。
実際のAPIが存在しないため、同一アプリ内にモックAPIを実装しています。

---

## 使用技術

| 項目 | 内容 |
|---|---|
| 言語 | PHP 8.2 |
| フレームワーク | Laravel 12 |
| データベース | SQLite（デフォルト） / MySQL対応 |
| HTTPクライアント | Laravel HTTP Client（Guzzle） |

---

## 機能

- 画像ファイルパスを入力してAI分析APIにリクエストを送信
- APIのレスポンスを `ai_analysis_log` テーブルに保存
- モックAPIにより Success / Failure / 異常レスポンスの3パターンを再現
- API仕様外のレスポンスを検出するバリデーション処理
- 分析ログの一覧表示UI

---

## セットアップ

### 必要環境

- PHP 8.1 以上
- Composer

### 手順

**1. 依存パッケージのインストール**

```bash
composer install
```

**2. 環境設定ファイルの作成**

`.env` が存在しない場合のみ実行してください。

```bash
cp .env.example .env
php artisan key:generate
```

**3. データベースの作成（SQLiteの場合）**

```bash
touch database/database.sqlite
```

**4. マイグレーションの実行**

```bash
php artisan migrate
```

**5. 開発サーバーの起動**

```bash
php artisan serve --port=8000
```

**6. ブラウザでアクセス**

```
http://localhost:8000
```

---

## Dockerで起動する場合

### 必要環境

- Docker Desktop

### 手順

**1. コンテナのビルドと起動**

```bash
docker compose up -d --build
```

**2. 依存パッケージのインストール**

```bash
docker compose exec php composer install
```

**3. 環境設定ファイルの作成**

`.env` が存在しない場合のみ実行してください。

```bash
docker compose exec php cp .env.example .env
docker compose exec php php artisan key:generate
```

> `.env` がすでにある場合はスキップしてください。上書きすると設定が初期化されます。

**4. マイグレーションの実行**

```bash
docker compose exec php php artisan migrate
```

**5. ブラウザでアクセス**

```
http://localhost:8000
```

**停止する場合**

```bash
docker compose down
```

**再度起動する場合**

```bash
docker compose up -d
```

---

## MySQLを使用する場合

`.env` の DB設定を変更してください。

```env
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=your_database
DB_USERNAME=your_username
DB_PASSWORD=your_password
```

変更後、マイグレーションを実行します。

```bash
php artisan migrate
```

---

## API切り替え方法

### 本番API → モックAPIに切り替える場合

**ローカル（php artisan serve）の場合**

`.env` を変更してください。

```env
AI_USE_MOCK=true
AI_API_URL=http://localhost:8000/api/mock/analyze
```

**Docker環境の場合**

`docker-compose.yml` の `environment` を変更してください。

```yaml
php:
  environment:
    AI_USE_MOCK: "true"
    AI_API_URL: http://nginx/api/mock/analyze
```

変更後、コンテナを再起動してください。

```bash
docker compose down
docker compose up -d
```

---

### モックAPI → 本番APIに切り替える場合

**ローカル（php artisan serve）の場合**

`.env` を変更してください。

```env
AI_USE_MOCK=false
AI_API_URL=https://example.com/
```

**Docker環境の場合**

`docker-compose.yml` の `environment` を変更してください（Dockerはシステム環境変数が `.env` より優先されるため）。

```yaml
php:
  environment:
    AI_API_URL: https://example.com/
    AI_USE_MOCK: "false"
```

変更後、コンテナを再起動してください。

```bash
docker compose down
docker compose up -d
```

---

## 画面の使い方

1. 画像ファイルパスを入力する（例: `/image/d03f1d36ca69348c51aa/c413eac329e1c0d03/test.jpg`）
2. モックレスポンスの種別をラジオボタンで選択する

| 選択肢 | 動作 |
|---|---|
| **Success** | 正常な成功レスポンスを返す（Class・Confidenceがランダムに生成される）|
| **Failure** | API側が失敗を返すレスポンスを再現する |
| **異常レスポンス** | API仕様外のレスポンス（`estimated_data` 欠落）を再現する |

3. 「分析実行」ボタンを押す
4. 結果がページ上部にメッセージで表示され、下部のログ一覧に記録される

---

## API仕様

### 外部AI分析API（モック対象）

| 項目 | 内容 |
|---|---|
| URL | `http://example.com/`（`.env` の `AI_API_URL` で変更可） |
| メソッド | POST |
| パラメータ | `image_path` (string) |

**成功レスポンス**

```json
{
    "success": true,
    "message": "success",
    "estimated_data": {
        "class": 3,
        "confidence": 0.8683
    }
}
```

**失敗レスポンス**

```json
{
    "success": false,
    "message": "Analysis failed due to an internal error."
}
```

### モックAPI（開発用）

同一アプリ内に実装した仮想エンドポイントです。

```
POST http://localhost:8000/api/mock/analyze
```

```bash
curl -X POST http://localhost:8000/api/mock/analyze \
  -H "Content-Type: application/json" \
  -d '{"image_path": "/image/test.jpg"}'
```

---

## レスポンス検証

APIレスポンスが仕様と異なる場合、`AiAnalysisService::validateResponse()` が検出し `success=false` としてDBに保存します。

| 検出するケース |
|---|
| JSONでないレスポンス（HTMLエラーページなど） |
| `success` フィールドが存在しない / bool型でない |
| `message` フィールドが存在しない / 文字列でない |
| `success=true` なのに `estimated_data` が存在しない |
| `estimated_data.class` が整数でない |
| `estimated_data.confidence` が 0〜1 の数値でない |
| HTTP 4xx / 5xx エラー |

---

## ファイル構成

```
app/
├── Http/Controllers/
│   ├── AiAnalysisController.php   # UI画面の受付・Serviceへの委譲
│   └── MockApiController.php      # モックAPIエンドポイント
├── Models/
│   └── AiAnalysisLog.php          # ai_analysis_logテーブルのモデル
└── Services/
    └── AiAnalysisService.php      # APIリクエスト・検証・DB保存のコアロジック

database/migrations/
└── ****_create_ai_analysis_log_table.php  # テーブル定義

resources/views/analysis/
└── index.blade.php                # 分析フォーム・ログ一覧UI

routes/
└── web.php                        # ルーティング定義
```

---

## DBテーブル定義

**テーブル名**: `ai_analysis_log`

| カラム | 型 | NULL | 説明 |
|---|---|---|---|
| id | int AUTO_INCREMENT | NO | 主キー |
| image_path | varchar(255) | YES | リクエストした画像パス |
| success | tinyint(1) | NO | 成功可否（1=成功 / 0=失敗） |
| message | varchar(255) | YES | APIメッセージまたはエラー内容 |
| class | int | YES | AIが判定した分類クラス番号 |
| confidence | decimal(5,4) | YES | 判定の信頼度スコア（0〜1） |
| request_timestamp | datetime(6) | YES | リクエスト送信日時 |
| response_timestamp | datetime(6) | YES | レスポンス受信日時 |

---

## ドキュメント

システムの詳細な設計説明は `document.html` をブラウザで直接開いて確認できます。
（Laravelサーバーの起動不要）
