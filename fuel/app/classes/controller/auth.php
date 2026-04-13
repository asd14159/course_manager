<?php

class Controller_Auth extends \Controller
{
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

        if ($password !== $password_confirm) {
             $view = \View::forge('auth/register');
             $view->set('error_message', 'パスワードが一致しません', false);
             return $view;
        }

        $data = [
            'username' => \Input::post('username'),
            'password' => $password,
            'email'    => \Input::post('email'),
        ];

        try {
            \Model_User::create_user($data);
            \Response::redirect('auth/login');
        } catch (\Exception $e) {
            $view = \View::forge('auth/register');
            $view->set('error_message', $e->getMessage(), false);
            return $view;
        }
    }

    //  ログアウト
    public function action_logout()
    {
        \Auth::logout();
        \Response::redirect('auth/login');
    }
}