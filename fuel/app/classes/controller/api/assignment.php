<?php

class Controller_Api_Assignment extends Controller_RestBase
{
    protected $format = 'json';

    public function get_all()
    {
        try {
            $assignments = \Model_Assignment::find_by_user($this->user_id);

            return $this->response([
                'status'      => 'success',
                'assignments' => $assignments ?: [],
            ], 200);

        } catch (\Exception $e) {
            \Log::error("課題の全取得に失敗: " . $e->getMessage());
            return $this->response([
                'status'      => 'error',
                'assignments' => '課題読み込み中にエラーが発生しました',
            ], 500);
        }
    }

    public function get_list($course_id = null)
    {
        try {
            //バリデーション
            if ($course_id === null || !is_numeric($course_id)) {
                return $this->response([
                    'status'  => 'error',
                    'message' => '授業IDが正しく指定されていません'
                ], 400);
            }

            $course = \Model_Course::find_by_pk($course_id);

            //授業idを指定すれば他ユーザーの授業情報も取得できてしまったため追加
            if (!$course || (int)$course['user_id'] !== $this->user_id) {
                return $this->response(array(
                    'status'  => 'error',
                    'message' => '指定された授業にアクセスする権限がありません'
                ), 403);
            }

            //データの取得
            $assignments = \Model_Assignment::get_by_course($course_id);

            $result = !empty($assignments) ? array_values($assignments) : array();

            // レスポンス（データが0件でも空の配列 [] を 200 OK で返す）
            return $this->response([
                'status'      => 'success',
                'assignments' => $result
            ], 200);

        } catch (\Exception $e) {
            //サーバーエラー
            \Log::error("Assignment list fetch error: " . $e->getMessage());
            return $this->response([
                'status'  => 'error',
                'message' => '課題データの取得中にエラーが発生しました'
            ], 500);
        }
    }
    
    //課題の追加
    public function post_create()
    {
        //バリデーションの設定
        $val = \Validation::forge();
        $val->add_field('course_id', '授業', 'required');
        $val->add_field('title', '課題名', 'required|max_length[50]');
        $val->add_field('deadline', '締め切り', 'required');
        $val->add_field('priority', '優先度', 'required|is_numeric');

        if (!$val->run()) {
            return $this->response(array('status' => 'error', 'message' => $val->error_message()), 400);
        }

        $course_id = \Input::post('course_id');

        \DB::start_transaction();

        try {
            //新しい授業を作成
            if ($course_id === 'new') {
                $new_course_name = \Input::post('new_course_name');
                if (empty($new_course_name)) {
                    throw new \Exception('新しい授業名を入力してください', 400);
                }

                $insert_result = \DB::insert('courses')->set([
                    'user_id'     => $this->user_id,
                    'name'        => $new_course_name,
                    'day_of_week' => \Input::post('new_course_day', 1),
                    'period'      => \Input::post('new_course_period', 1),
                    'created_at'  => date('Y-m-d H:i:s'),
                    'updated_at'  => date('Y-m-d H:i:s'),
                ])->execute();
                
                $course_id = $insert_result[0];
            } 
            //既存の授業を選択
            else {
                if (!is_numeric($course_id)) {
                    throw new \Exception('不正な授業IDです', 400);
                }
                $course = \Model_Course::find_by_pk($course_id);
                if (!$course || (int)$course['user_id'] !== $this->user_id) {
                    throw new \Exception('指定された授業に課題を追加する権限がありません', 403);
                }
            }

            //課題データの挿入
            $assignment_result = \DB::insert('assignments')->set([
                'course_id'    => $course_id,
                'title'        => $val->validated('title'),
                'description'  => \Input::post('description', ''),
                'deadline'     => $val->validated('deadline'),
                'priority'     => $val->validated('priority'),
                'is_completed' => 0,
                'created_at'   => date('Y-m-d H:i:s'),
                'updated_at'   => date('Y-m-d H:i:s'),
            ])->execute();

            $insert_id = $assignment_result[0];

            // すべて成功したらコミット
            \DB::commit_transaction();

            return $this->response(['status' => 'success', 'id' => $insert_id], 200);

        } catch (\Exception $e) {
            // 失敗したらロールバック（無かったことにする）
            \DB::rollback_transaction();
            
            \Log::error("Assignment post_create Error: " . $e->getMessage());
            
            $code = is_numeric($e->getCode()) && $e->getCode() >= 400 ? $e->getCode() : 500;
            return $this->response([
                'status' => 'error', 
                'message' => $e->getMessage()
            ], $code);
        }
    }

    //非同期で完了処理を実行
    public function post_update_status()
    {
        try {
            $id = \Input::json('id');
            $is_completed = \Input::json('is_completed');

            //バリデーション
            if (!$id || !is_numeric($id)) {
                return $this->response(array('status' => 'error', 'message' => '不正なIDです'), 400);
            }

            //権限チェックを追加
            // assignments を courses と結合して、courses.user_id を見る
            $assignment = \DB::select('assignments.id')
                ->from('assignments')
                ->join('courses', 'INNER')
                ->on('assignments.course_id', '=', 'courses.id')
                ->where('assignments.id', '=', $id)
                ->where('courses.user_id', '=', $this->user_id)
                ->execute()
                ->current();

            if (!$assignment) {
                return $this->response(array('status' => 'error', 'message' => '指定された課題を更新する権限がありません'), 403);
            }

            // 更新実行
            \DB::update('assignments')
                ->value('is_completed', $is_completed)
                ->value('updated_at', date('Y-m-d H:i:s'))
                ->where('id', '=', $id)
                ->execute();

            return $this->response(array('status' => 'success'), 200);

        } catch (\Exception $e) {
            \Log::error("Assignment update_status error: " . $e->getMessage());
            return $this->response(array('status' => 'error', 'message' => '更新中にエラーが発生しました'), 500);
        }
    }

    public function post_update($id = null) {
        $update_id = $id ?: \Input::post('id');
        $val = \Input::post();

        //バリデーション
        if (empty($update_id) || empty($val['title'])) {
            return $this->response(['status' => 'error', 'message' => '必要な情報が不足しています'], 400);
        }
        try {
            //権限のチェック
            // そのIDが存在し、かつ自分の授業に紐づいているか
            $exists = \DB::select('id')
                ->from('assignments')
                ->where('id', '=', $update_id)
                ->and_where('course_id', 'IN', \DB::select('id')
                    ->from('courses')
                    ->where('user_id', '=', $this->user_id)
                )
                ->execute()
                ->count();

            if ($exists === 0) {
                return $this->response([
                    'status'  => 'error',
                    'message' => '指定された課題が見つからないか、編集権限がありません'
                ], 404);
            }

            //更新処理
            \DB::update('assignments')
                ->set([
                    'title'       => $val['title'],
                    'description' => isset($val['description']) ? $val['description'] : '',
                    'deadline'    => $val['deadline'],
                    'priority'    => (int) $val['priority'],
                    'updated_at'  => date('Y-m-d H:i:s'),
                ])
                ->where('id', '=', $update_id)
                ->execute();

            return $this->response([
                'status'  => 'success',
                'message' => '課題を更新しました'
            ], 200);

        } catch (\Exception $e) {
            \Log::error("Assignment Update Error: " . $e->getMessage());
            return $this->response([
                'status'  => 'error',
                'message' => 'サーバーエラーが発生しました'
            ], 500);
        }
    }

    public function post_delete()
    {
        $id = \Input::post('id');

        if (!$id || !is_numeric($id)) {
            return $this->response(['status' => 'error', 'message' => '不正なIDです'], 400);
        }

        try {
            // 削除の実行(実行権限のチェックを追加)
            $affected_rows = \DB::delete('assignments')
                ->where('id', '=', $id)
                ->and_where('course_id', 'IN', \DB::select('id')
                    ->from('courses')
                    ->where('user_id', '=', $this->user_id)
                )
                ->execute();

            if ($affected_rows === 0) {
                return $this->response([
                    'status'  => 'error', 
                    'message' => '対象が見つからないか、削除権限がありません'
                ], 404);
            }

            return $this->response(['status' => 'success'], 200);

        } catch (\Exception $e) {
            \Log::error("Assignment Delete Error: " . $e->getMessage());
            return $this->response(['status' => 'error', 'message' => 'サーバーエラー'], 500);
        }
    }
}