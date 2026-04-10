<?php

namespace Fuel\Migrations;

class Create_Users
{
    public function up()
    {
        \DBUtil::create_table('users', array(
            'id' => array(
                'type' => 'int',
                'constraint' => 11,
                'unsigned' => true,
                'auto_increment' => true
            ),
            'username' => array('type' => 'varchar', 'constraint' => 50),
            'password' => array('type' => 'varchar', 'constraint' => 255),
            'email' => array('type' => 'varchar', 'constraint' => 255),
            'created_at' => array('type' => 'datetime'),
            'updated_at' => array('type' => 'datetime'),
        ), array('id'));
    }

    public function down()
    {
        \DBUtil::drop_table('users');
    }
}