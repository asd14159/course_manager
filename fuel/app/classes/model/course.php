<?php

class Model_Course extends \Model_Crud
{
    protected static $_table_name = 'courses';

    // 作成日時・更新日時のカラム名を指定するだけで、自動で time() を入れてくれます
    protected static $_created_at = 'created_at';
    protected static $_updated_at = 'updated_at';

    // もしDBの型が DATETIME なら、以下も追加して形式を指定します
    protected static $_mysql_timestamp = true;
    /**
     * ここが重要！「public static」になっているか確認してください
     */
    public static function get_all_by_user($user_id)
    {
        return static::find(array(
            'where' => array(
                array('user_id', '=', $user_id),
            ),
            'order_by' => array(
                'day_of_week' => 'asc',
                'period' => 'asc'
            ),
        ));
    }
}