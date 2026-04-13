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
            echo json_encode([
                'status' => 'error',
                'message' => 'Unauthorized'
            ]);
            exit;
        }

        $user_info = \Auth::get_user_id();
        $this->user_id = $user_info[1];
    }
}