<?php

return [
    'medoo' => [
        'database_type' => 'mysql',
        'database_name' => 'dbname',
        'server' => '127.0.0.1',
        'username' => 'root',
        'password' => ''
    ],
    'predis' => [
        'scheme' => 'tcp',
        'host' => '127.0.0.1',
        'port' => 6379
    ],
    'monolog' => [
        'name' => 'name',
        'dir' => '/tmp/',
        'level' => \Monolog\Logger::INFO
    ],
    'aes' => [
        'key' => '18b1db0370a0d612be59e851944c470b',
        'iv' => '55eaa49877495b8e6b6fd831d42f8e96'
    ]
];