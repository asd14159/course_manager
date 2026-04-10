<?php

// 条件5: 本来はここに namespace を書くのが理想的ですが、
// FuelPHPのControllerは標準でグローバル空間に置かれることが多いです。
class Controller_Auth extends Controller
{
    /**
     * 条件2: beforeメソッド
     * アクションが実行される前に必ず呼ばれる「門番」のようなメソッド
     */
    public function before()
    {
        parent::before();
        // ここで「すでにログイン済みならトップへ飛ばす」などの全体制御を書けます
    }

    public function action_login()
    {
        // すでにログイン中なら課題一覧へリダイレクト
        if (\Auth::check()) {
            \Response::redirect('home/index');
        }

        return \View::forge('auth/login');
    }

    public function post_login()
    {
        // 条件4: session/cookie
        // Auth::login() は内部的にセッションとクッキーを使ってログイン状態を維持します
        $username = \Input::post('username');
        $password = \Input::post('password');

        if (\Auth::login($username, $password)) {
            // ログイン成功
            \Response::redirect('home/index');
        } else {
            // ログイン失敗
            $view = \View::forge('auth/login');
            $view->set('error_message', 'ユーザー名またはパスワードが正しくありません', false);
            return $view;
        }
    }

    public function action_register()
    {
        return \View::forge('auth/register');
    }

    public function post_register()
    {
        // 入力値を取得
        $password = \Input::post('password');
        $password_confirm = \Input::post('password_confirm');

        // ★ ここに「一致チェック」を入れると、よりプロらしいコードになります！
        if ($password !== $password_confirm) {
             $view = \View::forge('auth/register');
             $view->set('error_message', 'パスワードが一致しません', false);
             return $view;
        }

        $data = [
            'username' => \Input::post('username'),
            'password' => $password, // チェック済みのパスワード
            'email'    => \Input::post('email'),
        ];

        try {
            // あなたが作った Model_User をここで呼び出す！
            \Model_User::create_user($data);
            
            // 登録できたらログイン画面へ（または自動ログイン）
            \Response::redirect('auth/login');
        } catch (\Exception $e) {
            // エラーがあれば登録画面に戻してメッセージ表示
            $view = \View::forge('auth/register');
            $view->set('error_message', $e->getMessage(), false);
            return $view;
        }
    }

    // --- ログアウト ---
    public function action_logout()
    {
        \Auth::logout();
        \Response::redirect('auth/login');
    }
}