<?php

class Controller_Api_Assignment extends Controller_RestBase
{
    protected $format = 'json';

    public function get_all()
    {
        $assignments = \Model_Assignment::get_all_by_user($this->user_id);
        return $this->response($assignments);
    }

    public function get_list($course_id = null)
    {
        try {
            // 1. パラメータのバリデーション
            // 授業IDが指定されていない、あるいは数値でない場合は 400 Bad Request
            if ($course_id === null || !is_numeric($course_id)) {
                return $this->response(array(
                    'status'  => 'error',
                    'message' => '授業IDが正しく指定されていません'
                ), 400);
            }

            // 2. データの取得
            $assignments = \Model_Assignment::get_by_course($course_id);

            // 3. レスポンス（データが0件でも空の配列 [] を 200 OK で返す）
            // 常に一貫したデータ型（この場合は配列）を返すのがポイントです
            return $this->response($assignments, 200);

        } catch (\Exception $e) {
            // 4. 万が一のサーバーエラー対応
            \Log::error("Assignment list fetch error: " . $e->getMessage());
            return $this->response(array(
                'status'  => 'error',
                'message' => '課題データの取得中にエラーが発生しました'
            ), 500);
        }
    }
    /**
     * 課題の新規登録 (POST)
     * 要件9: CRUDのCreate / 要件7: DBクラスの使用
     */
    public function post_create()
    {
        $val = \Validation::forge();
        $val->add_field('course_id', '授業', 'required');
        $val->add_field('title', '課題名', 'required|max_length[50]');
        $val->add_field('deadline', '締め切り', 'required');
        $val->add_field('priority', '優先度', 'required|is_numeric');

        if ($val->run())
        {
            $course_id = \Input::post('course_id');
            if (\Auth::check()) {
                $current_user_id = \Auth::get_user_id()[1];
            } else {
                // ログインしていない場合のエラー処理
                return $this->response(array('status' => 'error', 'message' => 'セッションが切れました'), 401);
            }

            // --- A. 新しい授業を作成する場合 ---
            if ($course_id === 'new') {
                $new_course_name = \Input::post('new_course_name');
                if (empty($new_course_name)) {
                    return $this->response(array('status' => 'error', 'message' => array('new_course_name' => '新しい授業名を入力してください')), 400);
                }

                // DB構造に合わせてカラム名を修正 (day -> day_of_week)
                // user_id が必須なので、現在のユーザーIDを入れる
                list($new_id, $rows) = \DB::insert('courses')->set(array(
                    'user_id'     => $current_user_id, 
                    'name'        => $new_course_name,
                    'day_of_week' => \Input::post('new_course_day', 1),
                    'period'      => \Input::post('new_course_period', 1),
                    'created_at'  => date('Y-m-d H:i:s'),
                    'updated_at'  => date('Y-m-d H:i:s'),
                ))->execute();
                
                $course_id = $new_id;
            } 
            else if (!is_numeric($course_id)) {
                return $this->response(array('status' => 'error', 'message' => array('course_id' => '不正な授業IDです')), 400);
            }

            // 2. 課題データの挿入
            list($insert_id, $rows_affected) = \DB::insert('assignments')->set(array(
                'course_id'    => $course_id,
                'title'        => $val->validated('title'),
                'description'  => \Input::post('description', ''),
                'deadline'     => $val->validated('deadline'),
                'priority'     => $val->validated('priority'),
                'is_completed' => 0,
                'created_at'   => date('Y-m-d H:i:s'),
                'updated_at'   => date('Y-m-d H:i:s'),
            ))->execute();

            return $this->response(array('status' => 'success', 'id' => $insert_id), 200);
        }

        return $this->response(array('status' => 'error', 'message' => $val->error_message()), 400);
    }

    /**
     * 完了状態の更新 (POST)
     * 要件4: 非同期で完了状態を保存
     */
    public function post_update_status()
    {
        // JSON形式のPOSTデータを取得
        $id = \Input::json('id');
        $is_completed = \Input::json('is_completed');

        // DB::update で特定のレコードを更新
        \DB::update('assignments')
            ->value('is_completed', $is_completed)
            ->value('updated_at', date('Y-m-d H:i:s'))
            ->where('id', '=', $id)
            ->execute();

        return $this->response(array('status' => 'success'), 200);
    }

    public function post_delete()
    {
        $id = \Input::post('id');

        if ($id && is_numeric($id)) {
            // DBから削除を実行
            \DB::delete('assignments')
                ->where('id', '=', $id)
                ->execute();

            return $this->response(array('status' => 'success'), 200);
        }

        return $this->response(array('status' => 'error', 'message' => '不正なIDです'), 400);
    }
}