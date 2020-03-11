<?php


namespace dis\orm\classes\bootstrap;


use Illuminate\Database\Capsule\Manager;

class ORMBootstrap {
    public static function run($host = 'localhost', $username = 'root', $password = '', $database = '', $driver = 'mysql') {
        $capsuleManager = new Manager();
        $capsuleManager->addConnection([
            'driver' => $driver,
            'host' => $host,
            'database' => $database,
            'username' => $username,
            'password' => $password
        ]);

        $capsuleManager->setAsGlobal();
        $capsuleManager->bootEloquent();
    }
}