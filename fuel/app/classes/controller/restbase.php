<?php

// API専用のベースコントローラ
class Controller_RestBase extends \Controller_Rest
{
    protected $user_id;

    public function before()
    {
        parent::before();

        if (!\Auth::check())
        {
            //apiのレスポンス形式を統一
            return $this->response([
                'status' => 'error',
                'message' => 'Unauthorized'
            ], 401);
        }

        $user_info = \Auth::get_user_id();
        $this->user_id = $user_info[1];
    }
}