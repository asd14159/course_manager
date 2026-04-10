<?php

namespace Fuel\Migrations;

class Create_assignments
{
	public function up()
	{
		\DBUtil::create_table('assignments', array(
			'id' => array('constraint' => 11, 'type' => 'int', 'auto_increment' => true, 'unsigned' => true),
			'course_id' => array('constraint' => 11, 'type' => 'int'),
			'title' => array('constraint' => 50, 'type' => 'varchar'),
			'description' => array('type' => 'text'),
			'deadline' => array('type' => 'datetime'),
			'priority' => array('type' => 'tinyint'),
			'is_completed' => array('constraint' => 1, 'type' => 'tinyint', 'default' => 0),
			'created_at' => array('type' => 'datetime'),
			'updated_at' => array('type' => 'datetime'),

		), array('id'));
	}

	public function down()
	{
		\DBUtil::drop_table('assignments');
	}
}