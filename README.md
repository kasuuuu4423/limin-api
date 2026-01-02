# Limin API Server

状態トリアージ型・作業開始支援システム（Two-Step Nudge System）のバックエンドAPI。

---

## 技術スタック

| 技術 | 用途 |
|------|------|
| Laravel 11.x | APIサーバー |
| Laravel Sail | Docker開発環境 |
| Laravel Sanctum | 認証（APIトークン認証） |
| PostgreSQL 16 | データベース |
| Redis | キュー／一時データ |
| PHP 8.3 | ランタイム |
| Docker / Docker Compose | コンテナ環境 |

---

## 正本ドキュメント

実装時は以下のドキュメントを参照すること。

| ドキュメント | パス |
|-------------|------|
| 仕様書 | `../../docs/specification.md` |
| 技術選定書 | `../../docs/technology-selection.md` |
| DBスキーマ | `../../docs/database-schema.md` |
| 選定ロジック | `../../docs/use-case-selection-logic.md` |
| API仕様 | `../../docs/api/openapi.yaml` |
| 開発ガイドライン | `../../docs/development-guidelines.md` |

---

## 設計決定事項

### 認証・ユーザー管理

| 項目 | 決定 |
|------|------|
| ユーザー登録 | 招待制（初期は管理者作成のみ） |
| パスワードリセット | 初期は実装しない |
| メール認証 | 不要 |
| 複数デバイスログイン | 許可（上限5トークン、古いものから自動削除） |
| トークン有効期限 | 無期限（明示的なログアウトで失効） |

### セッション管理

| 項目 | 決定 |
|------|------|
| 既存セッションの扱い | 自動終了して新規開始 |
| セッションタイムアウト | 24時間で自動終了 |
| 複数デバイスセッション | 禁止（最新デバイスが優先、古いセッションは自動終了） |

### 通知（APNs）【フェーズ2】

通知機能はフェーズ2として後回しにする。まずコア機能（セッション・選定ロジック）を完成させる。

| 項目 | 決定 |
|------|------|
| 通知トリガー | ユーザー設定の時刻（1日1〜3回）※フェーズ2で実装 |
| SNOOZE | 実装しない |
| 通知文言 | 固定（「今やる？」のみ） |

### データ・ビジネスロジック

| 項目 | 決定 |
|------|------|
| `LATER` → `NOW` 復帰 | 毎日指定時刻に自動復帰（デフォルト5:00、ユーザー設定可） |
| `BLOCKED` の解除 | 手動のみ（API提供: `POST /items/unblock`） |
| 自然言語パース | 初期は実装しない |
| Item削除 | 論理削除（`deleted_at`） |
| Item編集 | `nextAction` のみ（既存API） |
| `WAIT`/`HOLD` 遷移API | 初期は実装しない |

### API仕様

| 項目 | 決定 |
|------|------|
| ヘルスチェック | `GET /health`（認証不要） |
| バージョニング | 初期はプレフィックスなし |
| レート制限 | 初期は実装しない |
| CORS | 許可しない（ネイティブアプリのみ） |

### ペンディング（後日決定）

| 項目 | 状況 |
|------|------|
| 本番環境 | 未定（VPS / クラウド等を後日選定） |
| ドメイン | 未定（`api.example.com` は仮） |
| SSL証明書 | 本番環境決定後に選定 |
| バックアップ戦略 | 本番環境決定後に策定 |

---

## 追加APIエンドポイント

OpenAPI（`docs/api/openapi.yaml`）への追加が必要。

| エンドポイント | メソッド | 用途 |
|---------------|---------|------|
| `/health` | GET | ヘルスチェック（認証不要） |
| `/device-tokens` | POST | デバイストークン登録 |
| `/device-tokens/{id}` | DELETE | デバイストークン削除 |
| `/item/{id}` | DELETE | Item削除（論理削除） |
| `/items/unblock` | POST | `BLOCKED` → `NOW` 一括解除 |
| `/settings` | GET | ユーザー設定取得 |
| `/settings` | PATCH | ユーザー設定更新 |

---

## 追加DBカラム

`users` テーブルへの追加が必要。

| カラム | 型 | デフォルト | 説明 |
|--------|-----|-----------|------|
| `later_restore_at` | time | `05:00:00` | LATER復帰時刻 |

---

## スケジューラー

### LATER復帰

毎分実行し、各ユーザーの `later_restore_at` に基づいて復帰処理を行う。

```php
// app/Console/Kernel.php
Schedule::command('items:restore-later')->everyMinute();

// app/Console/Commands/RestoreLaterItems.php
public function handle(): void
{
    $currentTime = now()->format('H:i');
    
    $users = User::where('later_restore_at', $currentTime)->get();
    
    foreach ($users as $user) {
        Item::where('user_id', $user->id)
            ->where('availability', Availability::LATER)
            ->whereNull('done_at')
            ->update(['availability' => Availability::NOW]);
    }
}
```

### セッションタイムアウト

5分ごとに実行し、24時間経過したアクティブセッションを自動終了する。

```php
// app/Console/Kernel.php
Schedule::command('sessions:timeout')->everyFiveMinutes();

// app/Console/Commands/TimeoutSessions.php
public function handle(): void
{
    Session::whereNull('stopped_at')
        ->where('started_at', '<', now()->subHours(24))
        ->update(['stopped_at' => now()]);
}
```

---

## トークン上限処理

トークン発行時に5件を超える場合、古いものから削除する。

```php
// app/Http/Controllers/AuthController.php
public function token(Request $request): JsonResponse
{
    // ... 認証処理 ...
    
    // 古いトークンを削除（5件を超える場合）
    $tokenCount = $user->tokens()->count();
    if ($tokenCount >= 5) {
        $user->tokens()
            ->orderBy('created_at', 'asc')
            ->limit($tokenCount - 4)  // 4件残す（新規発行で5件になる）
            ->delete();
    }
    
    $token = $user->createToken($request->device_name)->plainTextToken;
    
    return response()->json(['token' => $token]);
}
```

---

## ユーザー作成

### Artisanコマンド

管理者がユーザーを作成するためのコマンド。

```bash
./vendor/bin/sail artisan user:create user@example.com "User Name" password123
```

```php
// app/Console/Commands/CreateUser.php
public function handle(): void
{
    $user = User::create([
        'email' => $this->argument('email'),
        'name' => $this->argument('name'),
        'password' => Hash::make($this->argument('password')),
    ]);
    
    $this->info("User created: {$user->email} (ID: {$user->id})");
}
```

### Seeder（開発用）

開発・テスト用の初期ユーザー。

```php
// database/seeders/UserSeeder.php
public function run(): void
{
    User::factory()->create([
        'name' => 'Test User',
        'email' => 'test@example.com',
        'password' => Hash::make('password'),
    ]);
}
```

```bash
./vendor/bin/sail artisan db:seed --class=UserSeeder
```

---

## アーキテクチャ方針

本プロジェクトは**クリーンアーキテクチャ**をベースに実装する。

### 層構成

```
┌─────────────────────────────────────────────────────────────┐
│                    Presentation Layer                       │
│         Controllers, Requests, Resources, Middleware        │
├─────────────────────────────────────────────────────────────┤
│                    Application Layer                        │
│              Use Cases, DTOs, Application Services          │
├─────────────────────────────────────────────────────────────┤
│                      Domain Layer                           │
│        Entities, Value Objects, Domain Services,            │
│              Repository Interfaces                          │
├─────────────────────────────────────────────────────────────┤
│                   Infrastructure Layer                      │
│      Eloquent Models, Repository Implementations,           │
│              External Services (APNs等)                     │
└─────────────────────────────────────────────────────────────┘
```

### 依存関係のルール

- 内側の層は外側の層に依存しない
- Domain層は他の層に依存しない（純粋なPHP）
- Application層はDomain層にのみ依存
- Infrastructure層・Presentation層はすべての層に依存可能
- 依存性逆転の原則（DIP）により、Domain層でインターフェースを定義し、Infrastructure層で実装

### 各層の責務

| 層 | 責務 | 配置 |
|----|------|------|
| Presentation | HTTPリクエスト/レスポンスの変換、認証・認可 | `app/Http/` |
| Application | ユースケースの実行、トランザクション制御 | `app/UseCases/` |
| Domain | ビジネスルール、エンティティ、値オブジェクト | `packages/Domain/` |
| Infrastructure | データ永続化、外部サービス連携 | `app/Infrastructure/` |

### Laravelとの共存方針

- Eloquentモデルは Infrastructure層に配置（Domain Entityとは別）
- Domain EntityとEloquentモデル間の変換はRepositoryで行う
- Laravel標準機能（認証、バリデーション、ジョブ等）は積極的に活用
- 過度な抽象化を避け、実用性を重視

---

## 実装フェーズ

### フェーズ1（MVP）

| 機能 | 状態 |
|------|------|
| 認証（トークン発行・失効） | 対象 |
| Capture（1行登録） | 対象 |
| セッション（開始・終了） | 対象 |
| `/next`（選定ロジック） | 対象 |
| 締切割り込み | 対象 |
| Item操作（完了・先送り・nextAction更新・削除） | 対象 |
| BLOCKED解除 | 対象 |
| ユーザー設定（LATER復帰時刻） | 対象 |
| スケジューラー（LATER復帰・セッションタイムアウト） | 対象 |

### フェーズ2（通知）

| 機能 | 状態 |
|------|------|
| デバイストークン登録・削除 | 対象 |
| 通知スケジュール設定 | 対象 |
| APNs送信 | 対象 |
| 通知ログ | 対象 |

---

## ディレクトリ構造

```
packages/
└── Domain/                              # Domain Layer（composer autoloadで読み込み）
    └── Limin/
        ├── Entity/
        │   ├── Item.php                 # Itemエンティティ
        │   ├── Session.php              # Sessionエンティティ
        │   └── User.php                 # Userエンティティ
        ├── ValueObject/
        │   ├── ItemType.php             # message | task
        │   ├── ItemState.php            # DO | WAIT | HOLD
        │   ├── Availability.php         # NOW | LATER | BLOCKED
        │   └── NextAction.php           # 最小の一手（1行テキスト）
        ├── Repository/
        │   ├── ItemRepositoryInterface.php
        │   ├── SessionRepositoryInterface.php
        │   └── UserRepositoryInterface.php
        └── Service/
            └── ItemSelectionService.php # 選定ロジック（純粋なドメインロジック）

app/
├── Http/                                # Presentation Layer
│   ├── Controllers/
│   │   ├── AuthController.php
│   │   ├── CaptureController.php
│   │   ├── SessionController.php
│   │   ├── NextController.php
│   │   ├── ItemController.php
│   │   ├── SettingsController.php
│   │   └── HealthController.php
│   ├── Middleware/
│   │   └── EnsureActiveSession.php
│   ├── Requests/
│   │   ├── CaptureRequest.php
│   │   ├── DeferRequest.php
│   │   └── UpdateNextActionRequest.php
│   └── Resources/
│       ├── NextItemResource.php
│       └── UserSettingsResource.php
│
├── UseCases/                            # Application Layer
│   ├── Capture/
│   │   └── CaptureItemUseCase.php
│   ├── Session/
│   │   ├── StartSessionUseCase.php
│   │   └── StopSessionUseCase.php
│   ├── Next/
│   │   ├── GetNextItemUseCase.php
│   │   ├── AcceptInterruptUseCase.php
│   │   └── RejectInterruptUseCase.php
│   ├── Item/
│   │   ├── CompleteItemUseCase.php
│   │   ├── DeferItemUseCase.php
│   │   ├── UpdateNextActionUseCase.php
│   │   ├── DeleteItemUseCase.php
│   │   └── UnblockItemsUseCase.php
│   └── Auth/
│       ├── IssueTokenUseCase.php
│       └── RevokeTokenUseCase.php
│
├── Infrastructure/                      # Infrastructure Layer
│   ├── Eloquent/
│   │   ├── EloquentItemRepository.php
│   │   ├── EloquentSessionRepository.php
│   │   └── EloquentUserRepository.php
│   ├── Models/                          # Eloquent Models
│   │   ├── User.php
│   │   ├── Item.php
│   │   ├── Session.php
│   │   ├── DeviceToken.php
│   │   └── NotificationLog.php
│   └── External/
│       └── ApnsService.php              # APNs送信（フェーズ2）
│
├── Console/
│   └── Commands/
│       ├── RestoreLaterItems.php
│       ├── TimeoutSessions.php
│       └── CreateUser.php
│
├── Jobs/
│   └── SendPushNotificationJob.php      # フェーズ2
│
└── Providers/
    └── RepositoryServiceProvider.php    # Repository DIバインド
```

### composer.json への追加

```json
{
    "autoload": {
        "psr-4": {
            "App\\": "app/",
            "Domain\\": "packages/Domain/"
        }
    }
}
```

### RepositoryServiceProvider

```php
// app/Providers/RepositoryServiceProvider.php
public function register(): void
{
    $this->app->bind(
        \Domain\Limin\Repository\ItemRepositoryInterface::class,
        \App\Infrastructure\Eloquent\EloquentItemRepository::class
    );
    $this->app->bind(
        \Domain\Limin\Repository\SessionRepositoryInterface::class,
        \App\Infrastructure\Eloquent\EloquentSessionRepository::class
    );
    $this->app->bind(
        \Domain\Limin\Repository\UserRepositoryInterface::class,
        \App\Infrastructure\Eloquent\EloquentUserRepository::class
    );
}
```

---

## 前提条件

- Docker Desktop（または Docker Engine + Docker Compose）
- macOS / Linux / Windows (WSL2)

---

## セットアップ

### 1. 環境変数

`.env.example` をコピーして `.env` を作成する。

```bash
cp .env.example .env
```

### 2. 依存インストール（初回のみ）

Docker環境でcomposerを実行する。

```bash
docker run --rm \
    -u "$(id -u):$(id -g)" \
    -v "$(pwd):/var/www/html" \
    -w /var/www/html \
    laravelsail/php83-composer:latest \
    composer install --ignore-platform-reqs
```

### 3. Sail起動

```bash
./vendor/bin/sail up -d
```

初回起動時はイメージのビルドが行われるため時間がかかる。

### 4. アプリケーションキー生成

```bash
./vendor/bin/sail artisan key:generate
```

### 5. データベースマイグレーション

```bash
./vendor/bin/sail artisan migrate
```

### 6. 動作確認

```bash
curl http://localhost/api/health
```

---

## Sailコマンド

頻繁に使用するコマンド。

```bash
# コンテナ起動
./vendor/bin/sail up -d

# コンテナ停止
./vendor/bin/sail down

# コンテナ再ビルド（Dockerfile変更時）
./vendor/bin/sail build --no-cache

# Artisanコマンド
./vendor/bin/sail artisan <command>

# Composerコマンド
./vendor/bin/sail composer <command>

# シェルアクセス
./vendor/bin/sail shell

# ログ確認
./vendor/bin/sail logs -f
```

### エイリアス設定（推奨）

`.bashrc` または `.zshrc` に以下を追加:

```bash
alias sail='./vendor/bin/sail'
```

設定後は `sail up -d` のように短縮形で実行可能。

---

## 環境変数

`.env.example` に以下を含める（実際の値はユーザーが設定）。

```env
APP_NAME=Limin
APP_ENV=local
APP_KEY=
APP_DEBUG=true
APP_URL=http://localhost
APP_PORT=80

# Sail設定
SAIL_XDEBUG_MODE=off
WWWGROUP=1000
WWWUSER=1000

# PostgreSQL（Sail）
DB_CONNECTION=pgsql
DB_HOST=pgsql
DB_PORT=5432
DB_DATABASE=limin
DB_USERNAME=sail
DB_PASSWORD=password

# Redis（Sail）
REDIS_HOST=redis
REDIS_PASSWORD=null
REDIS_PORT=6379

CACHE_DRIVER=redis
QUEUE_CONNECTION=redis
SESSION_DRIVER=redis

# APNs設定
APNS_KEY_ID=XXXXXXXXXX
APNS_TEAM_ID=XXXXXXXXXX
APNS_BUNDLE_ID=com.example.limin
APNS_KEY_PATH=storage/app/apns/AuthKey_XXXXXXXXXX.p8
APNS_ENVIRONMENT=development
```

Sail環境では `DB_HOST=pgsql`、`REDIS_HOST=redis` のようにサービス名を指定する。

---

## 実装方針

### 認証（Sanctum）

モバイル・macOSともにAPIトークン認証（Bearerヘッダ）を使用する。

#### トークン発行フロー

1. クライアントが `POST /auth/token` にメールアドレス・パスワード・デバイス名を送信
2. 認証成功時、`$user->createToken($deviceName)->plainTextToken` でトークン発行
3. クライアントはトークンをSecure Storageに保存し、以降のリクエストで `Authorization: Bearer {token}` を送信

#### 実装上の注意

- `config/sanctum.php` でトークン有効期限を設定可能（デフォルトは無期限）
- `Sanctum::currentApplicationUrlWithPort()` を使わない（API専用のため）

### API設計原則

#### 絶対に作成しないもの

- クライアント向け一覧API（`GET /items` 等）
- 「タスク一覧・件数・優先順位」を返すレスポンス

#### レスポンスに含めないフィールド

- `state`（DO/WAIT/HOLD）
- `availability`（NOW/LATER/BLOCKED）

これらを含めると、クライアント側で一覧・分類UIを作る誘因となるため、意図的に除外する。

### エンドポイント実装方針

#### `/capture`（POST）

- 1行テキストのみ受け取る
- サーバー側でデフォルト値を付与: `type=task`, `state=DO`, `availability=NOW`, `meta=false`
- 自然言語パース（任意実装）: 「明日」「1/10」等から `due_at` を補完。失敗しても `next_action` として保存

#### `/session/start`（POST）

- 新規セッションを作成（`stopped_at = NULL`）
- 既存のアクティブセッションがある場合の挙動を決定する必要あり（上書き or エラー）

#### `/session/stop`（POST）

- アクティブセッションの `stopped_at` に現在時刻を記録
- アクティブセッションがない場合は 404

#### `/next`（GET）

- 選定ロジック（後述）に従って次の1件を返す
- 提示すべきItemがない場合は 204 No Content

#### `/next/interrupt/accept`（POST）

- 締切割り込みを採用
- `session.interrupt_accepted_at` に現在時刻を記録
- そのItemを `session.current_item_id` に設定

#### `/next/interrupt/reject`（POST）

- 締切割り込みを却下
- 通常レーンのItemを返す（なければ 204）
- そのセッション中は締切割り込みを再提示しない

#### `/item/{id}/complete`（POST）

- `done_at` に現在時刻を記録
- ステータスコード 204

#### `/item/{id}/defer`（POST）

- `availability` を更新（デフォルト: LATER）
- `last_presented_at` を更新（セッション内抑制のため）

#### `/item/{id}/next-action`（POST）

- `next_action` を1行テキストで更新

---

## 選定ロジック（ItemSelectionService）

`docs/use-case-selection-logic.md` に定義されたアルゴリズムを実装する。

### 通常レーン

```php
Item::query()
    ->where('user_id', $user->id)
    ->where('availability', Availability::NOW)
    ->where('state', ItemState::DO)
    ->whereNotNull('next_action')
    ->where('next_action', '!=', '')
    ->whereNull('done_at')
    ->excludeSessionDeferred($session)
    ->orderBy('created_at', 'asc')
    ->first();
```

### 締切割り込みレーン

```php
Item::query()
    ->where('user_id', $user->id)
    ->where('availability', Availability::NOW)
    ->where('state', ItemState::DO)
    ->whereNotNull('next_action')
    ->where('next_action', '!=', '')
    ->whereNull('done_at')
    ->whereNotNull('due_at')
    ->where('due_at', '<=', now()->addHours(48))
    ->excludeSessionDeferred($session)
    ->orderBy('due_at', 'asc')
    ->first();
```

### セッション内抑制（スコープ）

`Item` モデルにスコープを定義:

```php
public function scopeExcludeSessionDeferred(Builder $query, Session $session): Builder
{
    return $query->where(function ($q) use ($session) {
        $q->whereNull('last_presented_at')
          ->orWhere('last_presented_at', '<', $session->started_at)
          ->orWhere('availability', Availability::NOW);
    });
}
```

### 選定フロー

1. セッションの `interrupt_offered_at` が NULL なら締切割り込みを検索
2. 締切割り込み対象があれば `is_interrupt: true` を付けて返す
3. なければ通常レーンから選定

---

## セッション管理

### アクティブセッションの取得

```php
Session::query()
    ->where('user_id', $user->id)
    ->whereNull('stopped_at')
    ->latest('started_at')
    ->first();
```

### ミドルウェア

`/next`、`/item/{id}/*` などセッション必須のエンドポイントには `EnsureActiveSession` ミドルウェアを適用:

```php
public function handle(Request $request, Closure $next)
{
    $session = $request->user()->activeSession;
    
    if (!$session) {
        return response()->json(['message' => 'No active session'], 400);
    }
    
    $request->merge(['activeSession' => $session]);
    
    return $next($request);
}
```

---

## APNs送信

### 実装方針

- 通知送信はキュー経由（Redis）で非同期処理
- 送信結果は `notification_logs` に記録

### ライブラリ

`edamov/pushok` または `laravel-notification-channels/apn` を使用。

### 送信Job

```php
class SendPushNotificationJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;
    
    public function __construct(
        public DeviceToken $deviceToken,
        public string $notificationType,
        public array $payload,
        public ?Item $item = null,
    ) {}
    
    public function handle(ApnsService $apns): void
    {
        $log = NotificationLog::create([
            'user_id' => $this->deviceToken->user_id,
            'device_token_id' => $this->deviceToken->id,
            'item_id' => $this->item?->id,
            'notification_type' => $this->notificationType,
            'payload' => $this->payload,
            'status' => 'pending',
        ]);
        
        try {
            $apnsId = $apns->send($this->deviceToken, $this->payload);
            $log->update([
                'status' => 'sent',
                'apns_id' => $apnsId,
                'sent_at' => now(),
            ]);
        } catch (ApnsException $e) {
            $log->update([
                'status' => 'failed',
                'error_code' => $e->getCode(),
                'error_message' => $e->getMessage(),
            ]);
            
            // InvalidToken等の場合はデバイストークンを無効化
            if ($e->shouldInvalidateToken()) {
                $this->deviceToken->update(['is_active' => false]);
            }
        }
    }
}
```

### リトライポリシー

- ネットワークエラー: 最大3回リトライ（exponential backoff）
- APNsエラー（BadDeviceToken等）: リトライしない、トークン無効化

---

## テスト戦略

### 重点テスト項目

| 機能 | テスト内容 |
|------|----------|
| `/next` 選定ロジック | 通常レーン・締切割り込み・セッション内抑制 |
| セッション管理 | 開始・終了・アクティブ判定 |
| Item状態遷移 | 完了・先送り・nextAction更新 |
| 認証 | トークン発行・検証・失効 |

### テストデータセットアップ

```php
// 通常レーンのItem
$item = Item::factory()->create([
    'user_id' => $user->id,
    'state' => ItemState::DO,
    'availability' => Availability::NOW,
    'next_action' => 'テスト用アクション',
    'done_at' => null,
]);

// 締切割り込み対象のItem
$deadlineItem = Item::factory()->create([
    'user_id' => $user->id,
    'state' => ItemState::DO,
    'availability' => Availability::NOW,
    'next_action' => '締切が近いアクション',
    'due_at' => now()->addHours(24),
    'done_at' => null,
]);
```

### 実行コマンド

```bash
./vendor/bin/sail artisan test
```

---

## コード規約

### フォーマット

Laravel Pint を使用。

```bash
# チェック
./vendor/bin/sail bin pint --test

# 自動修正
./vendor/bin/sail bin pint
```

### 静的解析

PHPStan + Larastan を使用。

```bash
./vendor/bin/sail bin phpstan analyse
```

### ローカル環境（Sail外）での実行

CI環境やSail外で実行する場合:

```bash
./vendor/bin/pint --test
./vendor/bin/phpstan analyse
```

### 設定ファイル

`phpstan.neon`:

```neon
includes:
    - vendor/larastan/larastan/extension.neon

parameters:
    paths:
        - app
    level: 8
    ignoreErrors: []
```

`pint.json`:

```json
{
    "preset": "laravel"
}
```

---

## ルーティング

`routes/api.php`:

```php
use App\Http\Controllers\AuthController;
use App\Http\Controllers\CaptureController;
use App\Http\Controllers\ItemController;
use App\Http\Controllers\NextController;
use App\Http\Controllers\SessionController;

// 認証不要
Route::post('/auth/token', [AuthController::class, 'token']);

// 認証必須
Route::middleware('auth:sanctum')->group(function () {
    Route::post('/auth/logout', [AuthController::class, 'logout']);
    Route::post('/capture', [CaptureController::class, 'store']);
    
    // セッション管理
    Route::post('/session/start', [SessionController::class, 'start']);
    Route::post('/session/stop', [SessionController::class, 'stop']);
    
    // セッション必須
    Route::middleware('active_session')->group(function () {
        Route::get('/next', [NextController::class, 'show']);
        Route::post('/next/interrupt/accept', [NextController::class, 'acceptInterrupt']);
        Route::post('/next/interrupt/reject', [NextController::class, 'rejectInterrupt']);
        
        Route::post('/item/{item}/complete', [ItemController::class, 'complete']);
        Route::post('/item/{item}/defer', [ItemController::class, 'defer']);
        Route::post('/item/{item}/next-action', [ItemController::class, 'updateNextAction']);
    });
});
```

---

## Docker構成

### サービス一覧

| サービス | ポート | 用途 |
|----------|-------|------|
| laravel.test | 80 | アプリケーション |
| pgsql | 5432 | PostgreSQL |
| redis | 6379 | Redis |

### docker-compose.yml のカスタマイズ

Sailのデフォルト構成を使用する場合、`docker-compose.yml` を publish する:

```bash
./vendor/bin/sail artisan sail:publish
```

PostgreSQLを使用するため、`docker-compose.yml` でサービスを確認・調整する。

### PostgreSQLへの接続（外部ツール）

| 項目 | 値 |
|------|-----|
| Host | localhost |
| Port | 5432 |
| Database | limin |
| Username | sail |
| Password | password |

---

## キューワーカー

バックグラウンドでキューを処理する場合:

```bash
./vendor/bin/sail artisan queue:work
```

開発中は `queue:listen` を使用すると、コード変更が即座に反映される:

```bash
./vendor/bin/sail artisan queue:listen
```

---

## CI

GitHub Actionsで以下を実行（CIはDockerなしで実行）:

| チェック名 | コマンド |
|------------|---------|
| test | `php artisan test` |
| pint | `./vendor/bin/pint --test` |
| phpstan | `./vendor/bin/phpstan analyse` |

### CI用データベース設定

CIでは SQLite（in-memory）または PostgreSQL service を使用する。

```yaml
# .github/workflows/ci.yml（例）
services:
  postgres:
    image: postgres:16
    env:
      POSTGRES_USER: sail
      POSTGRES_PASSWORD: password
      POSTGRES_DB: limin_test
    ports:
      - 5432:5432
    options: >-
      --health-cmd pg_isready
      --health-interval 10s
      --health-timeout 5s
      --health-retries 5
```

---

## 新規プロジェクト作成時の手順

Laravelプロジェクトを新規作成する場合:

```bash
# Laravel + Sail でプロジェクト作成（PostgreSQL + Redis）
curl -s "https://laravel.build/limin-api?with=pgsql,redis" | bash

cd limin-api
./vendor/bin/sail up -d
```

既存プロジェクトにSailを追加する場合:

```bash
composer require laravel/sail --dev
php artisan sail:install --with=pgsql,redis
```

---

## トラブルシューティング

### ポートが既に使用されている

```bash
# ポートを変更して起動
APP_PORT=8080 ./vendor/bin/sail up -d
```

または `.env` で `APP_PORT=8080` を設定。

### コンテナを完全にリセット

```bash
./vendor/bin/sail down -v  # ボリュームも削除
./vendor/bin/sail build --no-cache
./vendor/bin/sail up -d
```

### データベース接続エラー

PostgreSQLコンテナの起動を待つ必要がある場合がある:

```bash
./vendor/bin/sail up -d
sleep 5  # コンテナ起動待機
./vendor/bin/sail artisan migrate
```

---

## 秘密情報の取り扱い

- `.env` をコミットしない
- APNs鍵（`.p8`ファイル）をリポジトリに含めない
- 本番環境の資格情報はGitHub Secretsまたは環境変数で管理
- `docker-compose.yml` に秘密情報を直接記載しない

---

## 関連リポジトリ

| リポジトリ | 役割 |
|-----------|------|
| limin | 親リポジトリ（ドキュメント母艦） |
| limin-ios | iOSアプリ |
| limin-macos | macOSアプリ |

