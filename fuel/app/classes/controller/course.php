<?php
class Controller_Course extends Controller_Base
{
    public function action_index()
    {
        $user_id = \Auth::get_user_id()[1];
        
        $this->template->title = "履修管理ダッシュボード";
        $this->template->content = \View::forge('home/index');

        // 全授業データを取得してViewに渡す（これをKnockout.jsの初期データにする）
        $courses = \Model_Course::get_all_by_user($user_id);
        
        // 配列形式に変換して渡す
        $this->template->content->set('courses', $courses, false);
    }

    /**
     * API: 特定の授業の課題一覧をJSONで返す
     * Knockout.jsから呼び出される
     */
    public function action_get_assignments($course_id = null)
    {
        // 実際は Model_Assignment から取得
        // 今回は構造の例としてダミーを返します
        $data = [
            ['title' => 'レポート提出', 'deadline' => '2026-04-10', 'priority' => '高'],
            ['title' => '小テスト', 'deadline' => '2026-04-15', 'priority' => '中'],
        ];

        return \Response::forge(json_encode($data), 200, [
            'Content-Type' => 'application/json'
        ]);
    }
}