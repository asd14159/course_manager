<?php

namespace Fuel\Migrations;

class Create_Courses
{
    public function up()
    {
        \DBUtil::create_table('courses', array(
            'id' => array('type' => 'int', 'auto_increment' => true),

            'user_id' => array('type' => 'int'),

            'name' => array('type' => 'varchar', 'constraint' => 50),
            'day_of_week' => array('type' => 'tinyint'),
            'period' => array('type' => 'tinyint'),

            'created_at' => array('type' => 'datetime', 'null' => true),
            'updated_at' => array('type' => 'datetime', 'null' => true),
        ), array('id'), true, 'InnoDB');
    }

    public function down()
    {
        \DBUtil::drop_table('courses');
    }
}
