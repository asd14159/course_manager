<?php

class Controller_Api_Course extends Controller_RestBase
{
    // レスポンスの形式をJSONに設定
    protected $format = 'json';

    /**
     * 授業一覧の取得
     * URL: /api/course/list.json (GET)
     */
    public function get_list()
    {
        $auth_info = \Auth::get_user_id();

        // ログインしていない場合は空の配列などを返す処理が必要（安全策）
        if (!$auth_info) {
             return $this->response(array(), 200);
        }
        $current_user_id = $auth_info[1];
        
        try {
            // 1. データベースから全授業を取得
            // order_by を入れることで、曜日順や時限順に綺麗に並びます
            $courses = \DB::select()
                ->from('courses')
                ->where('user_id', '=', $current_user_id)
                ->order_by('day_of_week', 'asc')
                ->order_by('period', 'asc')
                ->execute()
                ->as_array(); // 扱いやすいように配列に変換

            // 2. 取得したデータをJSONとしてレスポンス
            // Controller_Rest を継承しているので、これだけでJSONになります
            return $this->response($courses, 200);

        } catch (\Exception $e) {
            return $this->response(array(
                'status' => 'error',
                'message' => '授業情報の取得に失敗しました: ' . $e->getMessage()
            ), 500);
        }
    }

//     /**
//      * 授業の削除処理
//      * URL: /api/course/delete.json
//      */
    public function post_delete()
    {
        // 1. 削除対象の授業IDを取得
        $course_id = \Input::post('id');

        // 2. IDが正当かチェック
        if (!$course_id || !is_numeric($course_id)) {
            return $this->response(array(
                'status' => 'error',
                'message' => '無効な授業IDです'
            ), 400);
        }

        try {
            // 3. トランザクション開始（重要！）
            // 「課題だけ消えて授業が残る」といった中途半端な失敗を防ぎます
            \DB::start_transaction();

            // 4. まず、その授業に紐付く「課題」をすべて削除
            \DB::delete('assignments')
                ->where('course_id', '=', $course_id)
                ->execute();

            // 5. 授業自体を削除
            $result = \DB::delete('courses')
                ->where('id', '=', $course_id)
                ->execute();

            // 6. 削除対象が存在しなかった場合
            if ($result === 0) {
                throw new Exception('削除対象の授業が見つかりませんでした');
            }

            // すべて成功したら確定
            \DB::commit_transaction();

            return $this->response(array(
                'status' => 'success',
                'message' => '授業と関連する課題をすべて削除しました'
            ), 200);

        } catch (\Exception $e) {
            // 失敗した場合は元に戻す
            \DB::rollback_transaction();

            return $this->response(array(
                'status' => 'error',
                'message' => $e->getMessage()
            ), 500);
        }
    }

    /**
 * 授業名の更新処理
 * URL: /api/course/update.json
 */
    public function post_update()
    {
        // 1. 入力値の取得
        $id = \Input::post('id');
        $name = \Input::post('name');
        $day_of_week = \Input::post('day_of_week');
        $period = \Input::post('period');

        // 2. バリデーション（必須項目と数値型のチェック）
        // ID、名前、曜日は0が入りうるため isset() や strlen() で厳密にチェック
        if (!$id || !isset($name) || !isset($day_of_week) || !isset($period)) {
            return $this->response(array(
                'status' => 'error', 
                'message' => '必要な情報が不足しています'
            ), 400);
        }

        try {
            // 3. データベース更新実行
            $result = \DB::update('courses')
                ->set(array(
                    'name'         => $name,
                    'day_of_week'  => (int) $day_of_week, // TinyIntにキャスト
                    'period'       => (int) $period,      // TinyIntにキャスト
                    'updated_at'   => date('Y-m-d H:i:s'), // ここを修正
                ))
                ->where('id', '=', $id)
                // セキュリティのため、本来はここに ->where('user_id', '=', $current_user_id) を入れるのがベスト
                ->execute();

            // execute() の戻り値は「影響を受けた行数」
            // 値が全く同じで更新されなかった場合も成功とするため、エラーにはしません
            return $this->response(array(
                'status' => 'success',
                'message' => '授業情報を更新しました'
            ), 200);

        } catch (\Exception $e) {
            return $this->response(array(
                'status' => 'error', 
                'message' => $e->getMessage(), // ← 本物のエラーメッセージが出るようになる
                'file' => $e->getFile(),
                'line' => $e->getLine()
            ), 500);
        }
    }
}
