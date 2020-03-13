<?php


namespace dis\orm\classes\commands;


class Register extends \dis\core\classes\commands\Register {
    public static function set_commands() {
        static::set_commands();
        static::$commands = [
            ...static::$commands,
            'migrate' => Migrate::class
        ];
    }
}