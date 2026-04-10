<?php
return array(
    'default' => array(
        'type'       => 'pdo',
        'connection' => array(
            'dsn'        => 'mysql:host=db;dbname=course_manager;charset=utf8',
            'username'   => 'root',
            'password'   => 'root',
            'persistent' => false,
        ),
        'identifier'   => '`',
        'table_prefix' => '',
        'charset'      => 'utf8',
        'collation'    => 'utf8_unicode_ci',
        'enable_cache' => true,
        'profiling'    => false,
    ),
);