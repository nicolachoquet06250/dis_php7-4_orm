<?php


namespace dis\orm\classes\bootstrap;


use Illuminate\Database\Capsule\Manager;

class ORMBootstrap {
    public static function run() {
        $capsuleManager = new Manager();
        $capsuleManager->addConnection([
            'driver' => 'mysql',
            'host' => 'localhost',
            'database' => 'test_orm',
            'username' => 'nchoquet',
            'password' => 'nchoquet'
        ]);

        $capsuleManager->setAsGlobal();
        $capsuleManager->bootEloquent();
    }
}