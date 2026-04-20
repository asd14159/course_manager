<?php

class Controller_Api_Course extends Controller_RestBase
{
    // レスポンスの形式をJSONに設定
    protected $format = 'json';

    //授業一覧の取得
    public function get_list()
    {
        try {
            $courses = \Model_Course::find_by_user($this->user_id);

            return $this->response([
                'status'  => 'success',
                'courses' => $courses ?: []
            ], 200);

        } catch (\Exception $e) {
            \Log::error("Course get_list Error: " . $e->getMessage() . " in " . $e->getFile() . ":" . $e->getLine());

            return $this->response([
                'status' => 'error',
                'message' => '授業の一覧に失敗しました'
            ], 500);
        }
    }

    //授業の削除
    public function post_delete()
    {
        //削除対象の授業IDを取得
        $course_id = \Input::post('id');

        //IDが正当かチェック
        if (!$course_id || !is_numeric($course_id)) {
            return $this->response([
                'status' => 'error',
                'message' => '無効な授業IDです'
            ], 400);
        }

        try {
            \DB::start_transaction();

            //ログイン中のユーザーか他ユーザーのcourseを消せてしまっていたため修正
            $course = \DB::select('id')
                ->from('courses')
                ->where('id', '=', $course_id)
                ->where('user_id', '=', $this->user_id)
                ->execute();

            if (count($course) === 0) {
                throw new \Exception('この授業を削除する権限がないか、存在しません', 403);
            }

            // 授業に紐付く「課題」をすべて削除
            \DB::delete('assignments')
                ->where('course_id', '=', $course_id)
                ->execute();

            //授業自体を削除
            \DB::delete('courses')
                ->where('id', '=', $course_id)
                ->execute();

            // すべて成功したら確定
            \DB::commit_transaction();

            return $this->response([
                'status' => 'success',
                'message' => '授業と関連する課題をすべて削除しました'
            ], 200);

        } catch (\Exception $e) {
            \DB::rollback_transaction();

            \Log::error("Course post_delete Error: " . $e->getMessage());

            $status_code = $e->getCode() ?: 500;

            $message = ($status_code === 403) ? $e->getMessage() : '削除処理中にシステムエラーが発生しました';

            return $this->response([
                'status' => 'error',
                'message' => $message
            ], $status_code);
        }
    }

    //授業名の更新
    public function post_update()
    {
        //入力値の取得
        $id = \Input::post('id');
        $name = \Input::post('name');
        $day_of_week = \Input::post('day_of_week');
        $period = \Input::post('period');

        //バリデーション
        if (!$id || !isset($name) || !isset($day_of_week) || !isset($period)) {
            return $this->response([
                'status' => 'error', 
                'message' => '必要な情報が不足しています'
            ], 400);
        }

        try {
            //データベース更新実行
            $result = \DB::update('courses')
                ->set(array(
                    'name'         => $name,
                    'day_of_week'  => (int) $day_of_week,
                    'period'       => (int) $period, 
                    'updated_at'   => date('Y-m-d H:i:s'),
                ))
                ->where('id', '=', $id)
                ->where('user_id', '=', $this->user_id)
                ->execute();

            //execute() の戻り値は「影響を受けた行数」のため 値が全く同じで更新されなかった場合も成功とする
            return $this->response([
                'status' => 'success',
                'message' => '授業情報を更新しました'
            ], 200);

        } catch (\Exception $e) {
            \Log::error("Course post_update Error: " . $e->getMessage());

            return $this->response([
                'status' => 'error', 
                'message' => '授業情報の更新中にエラーが発生しました'
            ], 500);
        }
    }
}
