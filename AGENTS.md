# AGENTS.md (limin-server)

## 正本ドキュメントの場所

システムドキュメントは親リポジトリに存在する。

| ドキュメント | 相対パス |
|-------------|----------|
| 仕様書 | `../../docs/specification.md` |
| 選定ロジック | `../../docs/use-case-selection-logic.md` |
| API仕様 | `../../docs/api/openapi.yaml` |
| DBスキーマ | `../../docs/database-schema.md` |
| 開発ガイドライン | `../../docs/development-guidelines.md` |

## 必須コマンド（変更提案前に必ず実行）

```bash
# 依存インストール
composer install

# テスト実行
php artisan test

# フォーマットチェック（差分があれば失敗）
./vendor/bin/pint --test

# 静的解析
./vendor/bin/phpstan analyse
```

## テスト対象（重点項目）

### `/next` 選定ロジック

- 通常レーン: `availability=NOW`, `state=DO`, `next_action` 存在, FIFO
- 締切割り込み: `due_at` が48時間以内、セッション中1回のみ
- セッション内抑制: 同セッション中の先送りItemは再提示しない

### セッション管理

- `POST /session/start`: セッション開始
- `POST /session/stop`: セッション終了
- 整合性: `stopped_at IS NULL` でアクティブ判定

### Item 状態遷移

- `POST /item/{id}/complete`: 完了（`done_at` 記録）
- `POST /item/{id}/defer`: 先送り（`availability` 更新）
- `POST /item/{id}/next-action`: nextAction 更新

### 認証（Sanctum）

- トークン発行: `POST /auth/token`
- 不正トークン・期限切れトークンの拒否
- 運用方式: モバイル/macOS ともに Bearer トークン認証

## 変更禁止事項

- クライアント向け一覧API（`GET /items` 等）の追加
- 「タスク一覧・件数・優先順位」を返すレスポンスの追加
- API レスポンスに `state` / `availability` を含めることは原則禁止（一覧・分類を誘発するため）

## APNs 送信

- キュー経由で送信（Redis）
- リクエストID・ステータスのログ記録を必須とする
- リトライポリシーを明確にし、変更時はテスト戦略を提示する

## CI ステータスチェック

| チェック名 | コマンド |
|------------|---------|
| test | `php artisan test` |
| pint | `./vendor/bin/pint --test` |
| phpstan | `./vendor/bin/phpstan analyse` |

## 秘密情報

- `.env` をコミットしない
- APNs鍵・証明書をリポジトリに含めない
- 例示にはダミー値を使用する

