<?php

class Model_Assignment extends \Model_Crud
{
    protected static $_table_name = 'assignments';

    protected static $_created_at = 'created_at';
    protected static $_updated_at = 'updated_at';
    protected static $_mysql_timestamp = true;

    //特定の授業に紐づく課題を取得
    public static function get_by_course($course_id)
    {
            $result =  \DB::select(
                'assignments.*',
                array('courses.name', 'course_name'), 
                \DB::expr("DATE_FORMAT(deadline, '%Y-%m-%d') AS deadline_formatted") 
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

    //ユーザーの全課題を取得
    public static function get_all_by_user($user_id)
    {
        return \DB::select(
            'assignments.*',
            array('courses.name', 'course_name'),
            \DB::expr("DATE_FORMAT(deadline, '%Y-%m-%d') AS deadline_formatted")
        )
        ->from(static::$_table_name)
        ->join('courses', 'INNER')
        ->on('assignments.course_id', '=', 'courses.id')
        ->where('courses.user_id', '=', $user_id) 
        ->order_by('is_completed', 'asc') 
        ->order_by('deadline', 'asc')    
        ->order_by('priority', 'desc')     
        ->execute()
        ->as_array();
    }
}