# COACHTECH 勤怠管理システム

一般ユーザーは出退勤・休憩の入戻の打刻、勤怠情報の修正申請ができます。
管理者は、スタッフ一覧やスタッフ全員の勤怠情報の閲覧、勤怠情報の修正、修正申請の承認ができます。

## 作成者

赤池美優

## 使用技術

- **言語**: PHP 8.5.9
- **フレームワーク**: Laravel Framework 10.50.3
- **データベース**: MySQL 8.4.11
- **データベース管理ツール**: phpMyadmin 5.2.3
- **ビルドツール**: Vite 5.4.21
- **開発環境**: Doocker Sail
- **バージョン管理**: Git 2.43.0
- **テストツール**: Postman for Windows 12.24.6

## ER図

```mermaid

erDiagram
    users {
        id  BIGINTUNSIGNED "PK"
        name VERCHAR
        email VERCHAR
        email_verified_at TIMESTAMP
        password VERCHAR
        admin_status TINYINTEGER "管理者は1を入力"
        remember_token VERCHAR(100)
        created_at TIMESTAMP
        updated_at TIMESTAMP
    }

    attendance_records {
        id BIGINTUNSIGNED "PK"
        user_id BIGINTUNSIGNED "FK, UK1"
        date DATE "UK1"
        comment varchar(255)
        created_at TIMESTAMP
        updated_at TIMESTAMP
    }

    clock_records {
        id BIGINTUNSIGNED "PK"
        attendance_record_id BIGINTUNSIGNED "FK"
        clock_in TIME
        clock_out TIME
        created_at TIMESTAMP
        updated_at TIMESTAMP
    }

    break_records {
        id BIGINTUNSIGNED "PK"
        attendance_record_id BIGINTUNSIGNED "FK"
        break_in TIME
        break_out TIME
        created_at TIMESTAMP
        updated_at TIMESTAMP
    }

    attendance_correct_requests {
        id BIGINTUNSIGNED "PK"
        attendance_record_id BIGINTUNSIGNED "FK, UK1"
        status INTEGER 　"UK1 1は承認待ち、2以上は承認済み"
        comment varchar(255)
        created_at TIMESTAMP
        updated_at TIMESTAMP

    }

    clock_correct_requests {
        id BIGINTUNSIGNED "PK"
        attendance_correct_requests_id BIGINTUNSIGNED "FK"
        new_clock_in TIME
        new_clock_out TIME
        created_at TIMESTAMP
        updated_at TIMESTAMP
    }

    break_correct_requests {
        id BIGINTUNSIGNED "PK"
        attendance_correct_requests_id BIGINTUNSIGNED "FK"
        new_break_in TIME
        new_break_out TIME
        created_at TIMESTAMP
        updated_at TIMESTAMP
    }

    users ||--o{ attendance_records : "has many"
    attendance_records ||--|| clock_records : "has one"
    attendance_records ||--o{ break_records : "has many"
    attendance_records ||--o{ attendance_correct_requests : "has many"
    attendance_correct_requests ||--|| clock_correct_requests : "has one"
    attendance_correct_requests ||--o{ break_correct_requests : "has many"

```

## 開発環境URL

http://localhost

## 動作環境

Windows11上のWSL(Ubuntu)で開発しています。
また、Docker上でPHP、Laravel、MySQLを使用して動作しています。

## 環境構築手順

1. **リポジトリをクローン**

    ```
        https://github.com/miyori-knzk/attendance-app.git
    ```

2. **.envファイルの準備**

    プロジェクトフォルダに移動し、
    .env.exsampleをコピーして.envを作成

    ```
    cd task-manager2
    cp .env.example .env
    ```

    .envファイルを開き、データベース情報が以下のようになっているか確認

    ```
    DB_CONNECTION=mysql
    DB_HOST=mysql
    DB_PORT=3306
    DB_DATABASE=laravel
    DB_USERNAME=sail
    DB_PASSWORD=password
    ```

3. **Composer依存パッケージのインストール**

    ```
    docker run --rm
    -u "$(id -u):$(id -g)"
    -v "$(pwd):/var/www/html"
    -w /var/www/html
    -e COMPOSER_CACHE_DIR=/tmp/composer_cache
    laravelsail/php82-composer:latest
    composer install --ignore-platform-reqs
    ```

4. **Laravel Sailの起動**

    ```
    ./vendor/bin/sail up -d
    ```

5. **NPM依存パッケージのインストール**

    ```
    ./vendor/bin/sail npm install
    ```

6. **アプリケーションキーの生成**

    ```
    ./vendor/bin/sail artisan key:generate
    ```

7. **データベースのマイグレーションと初期データ投入**

    マイグレーションの実行

    ```
    ./vendor/bin/sail artisan migrate
    ```

    初期データ投入

    ```
    ./vendor/bin/sail artisan db:seed
    ```

8. **フロントエンドのビルド**

    ```
    ./vendor/bin/sail npm run build
    ```

9. **アプリケーションへのアクセス**
    - 一般ユーザー
      ブラウザで<http://localhost/login>にアクセスしログイン
      メールアドレス：user1@example.com
      パスワード：password
    - 管理者ユーザー
      メールアドレス：user3@example.com
      パスワード：password
    - データベース
      ブラウザで<http://localhost:8080>にアクセスしphpMyAdminが表示されるか確認

## テスト実行

```
./vendor/bin/sail test
```

## 機能一覧

- 一般ユーザー(勤怠打刻、勤怠一覧、勤怠修正申請、ログイン、ログアウト)
- 一般ユーザー(勤怠一覧、スタッフ一覧、勤怠修正、修正申請の承認、ログイン、ログアウト)

## APIエンドポイント一覧

未実装
