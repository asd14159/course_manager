# 概要
- 履修管理システム（FuelPHP 1.8 + MySQL）
- ユーザー登録・ログイン・履修管理機能を提供

## 環境構築
- PHP 8.x
- FuelPHP 1.8
- MySQL
- Docker

# 制作ポイント

### 1.サーバサイド言語はPHPで、フレームワークのFuelPHPを使用する
- FuelPHPのMVC構成で実装

### 2.beforeメソッドを使う
- fuel/app/classes/controller/base.php
- 全メソッドの実行前に呼び出されるログインチェックを実装

### 3. configファイルをカスタマイズする
- fuel/app/config/config.php
- Security::htmlentities を設定し、XSS対策を適用

### 4.sessionやcookieを使う
- fuel/app/classes/controller/auth.php
- Authパッケージを利用し、ユーザーの認証状態をセッションで管理。

### 5.ネームスペースを使う
- 今回は未使用

### 6.\（バックスラッシュ）を使ったグローバル名前空間へのアクセス
- FuelPHPのコアクラス（Input、Auth、 Response、View、DB等）や、PHP標準の関数・クラスを呼び出す際に使用

### 7.データベースとのやり取りはDBクラスを使う
- fuel/app/classes/model/user.php
- fuel/app/classes/model/course.php
- fuel/app/classes/model/assignment.php

### 8.1:n関係のテーブル構造がある
- users : courses = 1 : n
- courses : assignment = 1: n
- user_id、course_idで紐づけ

### 9.CRUDの機能が網羅されている
- /api/assignment/
- /api/course/
- すべての操作は非同期通信（fetch API）で実装
- Controller_Rest を利用し、JSON形式でレスポンスを統一

### 10.フロントエンドのライブラリにknockout.jsを使用
- public/assets/js/app/home.jsにて ViewModel（HomeViewModel）を定義
- ページ遷移を行わず、fetch APIによる非同期通信と組み合わせた

### 11.UXを考慮して一部動的なUIを実装（非同期処理）
- public/assets/js/app/home.js
- fuel/app/views/home/index.phpで実装

### 12.GitHubでコードの管理を行う
- 変更ごと更新

### 13. セキュリティ資料を読み必要な実装を行う
- フォーム送信時にCSRFトークンを利用
- 各種バリデーションを実装

--------------------------------------------

## 起動方法

### 1. リポジトリ取得
`git clone https://github.com/asd14159/course_manager`
`cd project`

### 2. コンテナ起動（Dockerの場合）
docker-compose up -d

### 3. マイグレーション実行
php oil refine migrate --all

### 4. サーバ起動（ローカルの場合）
php -S localhost:8000 -t public