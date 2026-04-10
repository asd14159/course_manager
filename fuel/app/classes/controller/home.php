<?php

// 1. Baseコントローラを継承することで、自動的にログインチェックを適用する
class Controller_Home extends Controller_Base
{
    // 2. テンプレート（外枠）の中身を生成するメインのアクション
    public function action_index($course_id = null)
    {
        // 3. 表示に使うデータを格納するための空の配列を用意
        $data = array();

        // 4. 左側のサイドバー用：ログインユーザーに紐づく全ての授業を取得
        $data['courses'] = \Model_Course::get_all_by_user($this->user_id);

        // 5. 右側のメインエリア用：表示する課題の切り分け
        if ($course_id) 
        {
            // 6. 特定の授業IDがURL（/home/index/1 など）で指定されている場合
            $data['assignments'] = \Model_Assignment::get_by_course($course_id);
            
            // 7. 現在選択されている授業名を表示するために取得
            $data['selected_course'] = \Model_Course::find($course_id);
        } 
        else 
        {
            // 8. ID指定がない場合（/home）は、ユーザーの全課題を表示
            // 8. ID指定がない場合
            $data['assignments'] = \Model_Assignment::get_all_by_user($this->user_id);
            
            // ★修正ポイント：明示的に null を代入せず、変数自体を作らない
            // これにより、Security::clean の get_class(null) エラーを回避します
        }

        // 9. ページのタイトルをテンプレートにセット
        $this->template->title = 'ダッシュボード';

        // 10. Viewファイル (home/index.php) を読み込み、準備したデータを渡す
        $this->template->content = \View::forge('home/index', $data);
    }
}