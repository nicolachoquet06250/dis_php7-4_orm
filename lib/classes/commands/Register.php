<?php


namespace dis\orm\classes\commands;


class Register extends \dis\core\classes\commands\Register {
    public static function set_commands() {
        parent::set_commands();
        static::$commands = array_merge(static::$commands, [
            'migrate' => Migrate::class
        ]);
    }
}