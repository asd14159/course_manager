<?php

// API専用のベースコントローラ
class Controller_RestBase extends \Controller_Rest
{
    // 子クラスからもアクセスできるよう protected で定義
    protected $user_id;

    public function before()
    {
        parent::before();

        // Authによるログインチェック
        if (! \Auth::check())
        {
            // APIなのでリダイレクトではなく、JSONで401エラーを返す
            return $this->response(array(
                'status' => 'error',
                'message' => 'Unauthorized'
            ), 401);
        }

        // ログイン済みならユーザーIDを保持
        $user_info = \Auth::get_user_id();
        $this->user_id = $user_info[1]; 
    }
}