<?php

class Model_Course extends \Model_Crud
{
    protected static $_table_name = 'courses';
    protected static $_created_at = 'created_at';
    protected static $_updated_at = 'updated_at';
    protected static $_mysql_timestamp = true;
    
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