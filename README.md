# second-mock-project

## 環境構築
**Dockerビルド**
1. `git clone git@github.com:satsuki-fukuda/second-mock-project.git`
2. cd second-mock-project
3. DockerDesktopアプリを立ち上げる
4. `docker-compose up -d --build`


**Laravel環境構築**
1. `docker-compose exec php bash`
2. `composer install`
3. 「.env.example」ファイルを 「.env」ファイルに命名を変更。または、新しく.envファイルを作成
4. .envに以下の環境変数を追加
``` text
DB_CONNECTION=mysql
DB_HOST=mysql
DB_PORT=3306
DB_DATABASE=laravel_db
DB_USERNAME=laravel_user
DB_PASSWORD=laravel_pass
```
5. アプリケーションキーの作成
``` bash
php artisan key:generate
```

6. マイグレーションの実行
``` bash
php artisan migrate
```

7. シーディングの実行
``` bash
php artisan db:seed
```

**メール認証**
<br>ローカル環境でのメール送信テストにMailHogを使用します</br>
1. `docker-compose up -d mailhog`
2. .envに以下の環境変数を追加
``` text
MAIL_MAILER=smtp
MAIL_HOST=mailhog
MAIL_PORT=1025
MAIL_USERNAME=null
MAIL_PASSWORD=null
MAIL_ENCRYPTION=null
MAIL_FROM_ADDRESS=hello@example.com
```
3. ブラウザで http://localhost:8025 にアクセスします
4. アプリケーションからメールを送信します。
5. MailHogのUI上にメールが表示されます。

**PHPUnitテストについて**
<br>phpコンテナにてvendor/bin/phpunit tests コマンドにて実行</br>

**ログイン情報**
一般ユーザー
<br>ID:user1@example.com/user2@example.com</br>
PASS:password

管理者
<br>ID:admin@example.com</br>
PASS:password

## 使用技術(実行環境)
- PHP8.3.0
- Laravel8.83.27
- MySQL8.0.26

## テーブル設計
<img width="759" height="264" alt="スクリーンショット 2026-06-04 13 37 39" src="https://github.com/user-attachments/assets/f5cfe7cd-0e73-4408-9014-230cb796e2c9" />
<img width="758" height="254" alt="スクリーンショット 2026-06-04 13 38 16" src="https://github.com/user-attachments/assets/11ba400b-b7fb-49ad-876f-af86f947a75a" />
<img width="801" height="278" alt="スクリーンショット 2026-06-04 13 38 47" src="https://github.com/user-attachments/assets/b37650bb-05b4-485b-83bf-3332c904e954" />
<img width="834" height="176" alt="スクリーンショット 2026-06-09 10 38 06" src="https://github.com/user-attachments/assets/5ae009f5-9305-4285-adae-a0ad3f30d42c" />
<img width="809" height="181" alt="スクリーンショット 2026-06-04 13 39 33" src="https://github.com/user-attachments/assets/c4d7fe32-5ae4-4fe2-9b32-cc837ea1c7c0" />

## ER図
<img width="381" height="416" alt="スクリーンショット 2026-05-24 15 38 25" src="https://github.com/user-attachments/assets/3fff421d-e35b-4e53-b05c-9e78a93d336c" />
![Uploading スクリーンショット 2026-06-04 13.37.39.png…]()

## URL
- 開発環境：http://localhost/
- phpMyAdmin:：http://localhost:8080/
