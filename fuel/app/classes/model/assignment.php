<?php

class Model_Assignment extends \Model_Crud
{
    protected static $_table_name = 'assignments';

    // 作成日時・更新日時のカラム名を指定するだけで、自動で time() を入れてくれます
    protected static $_created_at = 'created_at';
    protected static $_updated_at = 'updated_at';

    // もしDBの型が DATETIME なら、以下も追加して形式を指定します
    protected static $_mysql_timestamp = true;

    /**
     * 特定の授業に紐づく課題を取得する
     */
    public static function get_by_course($course_id)
    {
        // DB::selectを使って、JSが欲しがっている全ての項目を揃える
            $result =  \DB::select(
                'assignments.*',
                array('courses.name', 'course_name'), // これが必要！
                \DB::expr("DATE_FORMAT(deadline, '%Y-%m-%d') AS deadline_formatted") // これも必要！
            )
            ->from(static::$_table_name)
            ->join('courses', 'LEFT')
            ->on('assignments.course_id', '=', 'courses.id')
            ->where('assignments.course_id', '=', $course_id)
            ->order_by('deadline', 'asc')
            ->execute()
            ->as_array();

            return $result ?: array();
    }

    /**
     * ユーザーの全課題を取得する（複雑な結合はクエリビルダを併用）
     */
    public static function get_all_by_user($user_id)
    {
        return \DB::select(
            'assignments.*',
            // 授業テーブルを結合して「授業名」を取得（これが無いとJSが落ちます）
            array('courses.name', 'course_name'),
            // 日付をフォーマットして取得（これが無いと期限が表示されません）
            \DB::expr("DATE_FORMAT(deadline, '%Y-%m-%d') AS deadline_formatted")
        )
        ->from(static::$_table_name)
        ->join('courses', 'INNER')
        ->on('assignments.course_id', '=', 'courses.id')
        // 修正ポイント：$course_id ではなく $user_id で絞り込む
        ->where('courses.user_id', '=', $user_id) 
        
        ->order_by('is_completed', 'asc') 
        ->order_by('deadline', 'asc')    
        ->order_by('priority', 'desc')     
        ->execute()
        ->as_array();
    }
}