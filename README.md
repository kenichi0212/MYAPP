# 賞味期限管理Webアプリ

納品先売場向けの賞味期限チェック業務を効率化するWebアプリケーション。現在Excelで行っている「バーコード読取→入力→CSV加工→Excel転記」の業務フローを置き換え、二重入力・チェック漏れを解消することを目的とする。

詳細な仕様は [SPEC.md](SPEC.md)、実装タスクの分解は [TASKS.md](TASKS.md)、開発方針は [CLAUDE.md](CLAUDE.md) を参照。

## 技術スタック

| 区分 | 技術 |
|---|---|
| バックエンド | PHP / Laravel 11 |
| 認証 | Laravel Breeze（Blade版） |
| フロントエンド | Laravel Blade + Tailwind CSS |
| データベース | PostgreSQL（ローカルはLaravel Sail付属DB、本番はSupabase） |
| ローカル開発環境 | Docker / Laravel Sail |
| テスト | PHPUnit |
| CI | GitHub Actions |
| 本番ホスティング | Render（Dockerデプロイ） |

## セットアップ（ローカル開発）

### 前提

- Docker Desktop（WSL2バックエンド推奨）
- **Windows環境の場合**：Laravel Sailの起動スクリプトは`uname -s`が`Linux`または`Darwin`であることを前提としているため、素のGit Bash（MINGW64）では動作しない。**WSL2（Ubuntu等）上で実行する**か、後述の「Sailを使わない代替手順」でDocker Composeを直接操作すること。

### 手順

```bash
# 1. リポジトリをクローン
git clone git@github.com:kenichi0212/MYAPP.git
cd MYAPP

# 2. .envを作成
cp .env.example .env

# 3. Sail起動（WSL2上のbashで実行）
./vendor/bin/sail up -d

# 4. APP_KEY生成
./vendor/bin/sail artisan key:generate

# 5. マイグレーション + シード
./vendor/bin/sail artisan migrate --seed

# 6. フロントエンドアセットビルド
./vendor/bin/sail npm install
./vendor/bin/sail npm run build
```

`http://localhost/` にアクセスして起動を確認する。

### Sailを使わない代替手順（WSL2のDocker統合が無効な場合）

Windows側のDocker Desktopが起動していれば、`docker compose`を直接呼び出すことでも同様に動作する。

```bash
docker compose -f compose.yaml up -d
docker exec -u sail myapp-laravel.test-1 composer install
docker exec -u sail myapp-laravel.test-1 npm install
docker exec -u sail myapp-laravel.test-1 npm run build
docker exec -u sail myapp-laravel.test-1 php artisan migrate --seed
```

`docker exec`は`-u sail`を付けて実行すること。付けずに実行すると生成物がroot所有になり、コンテナ内のWebサーバープロセス（sailユーザーで起動）が`storage/`配下に書き込めずエラーになる。

## テスト

```bash
./vendor/bin/sail artisan test
```

GitHub Actions（`.github/workflows/test.yml`）でPull Request・main push時に自動実行される。

## 本番デプロイ（Render + Supabase）

- データベースはSupabaseのPostgreSQLを使用する。Supabaseの「Direct connection」はIPv6専用ホストのため、IPv6非対応のRenderからは接続できない。**「Session pooler」の接続情報を使用すること**。
- 本番用の接続情報は`.env.production`（gitignore対象、リポジトリには含めない）にローカルで保管し、Renderの環境変数に登録する。`render.yaml`に環境変数の構成一覧を記載している（値は手動入力）。
- デプロイは`Dockerfile`を使用したDockerビルド。RenderはPHPのネイティブランタイムを提供していないため必須。
- RenderはTLSをロードバランサー側で終端しHTTPでコンテナに転送するため、`bootstrap/app.php`で`trustProxies(at: '*')`を設定している（未設定だとCSS/JSがhttpの絶対URLで生成され、HTTPS配信時にMixed Contentでブロックされる）。

## 開発用デモログイン

ログイン画面に「開発用デモユーザーでログイン」ボタンを用意している（`APP_ENV=production`以外でのみ表示）。パスワード入力なしでシード済みのデモユーザーとしてログインできる。
