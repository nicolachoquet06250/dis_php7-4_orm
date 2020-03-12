<?php


namespace dis\orm\classes\bootstrap;


use Exception;
use Illuminate\Database\Capsule\Manager;

class ORMBootstrap {
    protected static array $connections = [];

    /**
     * @param string $name
     * @param string $username
     * @param string $password
     * @param string $database
     * @param string $charset
     * @param string $collation
     * @param string $prefix
     * @param string $driver
     * @param string[] ...$hosts
     * @return static
     */
    public static function setConnection(string $name, string $username, string $password, string $database,
                                         string $charset = 'utf8', string $collation = 'utf8_unicode_ci',
                                         string $prefix = '', string $driver = 'mysql', string ...$hosts) {
        if(empty($hosts)) $read_host = 'localhost';
        else $read_host = $hosts[0];
        if(!isset($hosts[0])) $write_host = $read_host;
        else $write_host = $hosts[1];

        static::$connections = [
            'read' => ['host' => $read_host],
            'write' => ['host' => $write_host],
            'driver' => $driver,
            'database' => $database,
            'username' => $username,
            'password' => $password,
            'charset' => $charset,
            'collation' => $collation,
            'prefix' => $prefix,
        ];
        return static::class;
    }

    /**
     * @throws Exception
     */
    public static function run() {
        if(!empty(static::$connections)) {
            $capsuleManager = new Manager();
            $capsuleManager->addConnection(static::$connections);

            $capsuleManager->setAsGlobal();
            $capsuleManager->bootEloquent();
        } else throw new Exception('Veuillez entrer au moins un credential pour la connexion à la base de données');
    }
}