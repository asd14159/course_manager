<?php

class Controller_Api_Course extends Controller_Rest
{
    // レスポンスの形式をJSONに設定
    protected $format = 'json';

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
}
