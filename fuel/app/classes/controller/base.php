<?php

class Controller_Base extends \Controller_Template
{
    public $template = 'template';

    public function before()
    {
        parent::before();

        // 1. 無限リダイレクト防止策 (重要！)
        // 現在のコントローラ名を取得し、ログイン・新規登録画面ならチェックをスキップする
        $current_controller = \Request::active()->controller;
        
        if ($current_controller !== 'Controller_Auth') 
        {
            if (! \Auth::check())
            {
                \Response::redirect('auth/login');
            }
        }

        // 2. ログイン済みの場合の処理
        if (\Auth::check())
        {
            // 名前だけでなくIDも取得しておくと、後のDB操作（Model::findなど）で便利
            $user_info = \Auth::get_user_id();
            $this->user_id = $user_info[1]; 
            
            // ビュー全体で使えるようにセット
            $user_name = \Auth::get_screen_name();
            \View::set_global('current_user', $user_name);
        }
    }
}