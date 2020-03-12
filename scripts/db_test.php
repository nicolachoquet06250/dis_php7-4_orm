<?php


require_once __DIR__.'/../vendor/autoload.php';
// USERNAME, PASSWORD and DATABASE constants definitions
require_once __DIR__.'/constants.php';

use dis\orm\classes\bootstrap\ORMBootstrap;
use dis\orm\classes\generators\TableGenerator;
use dis\orm\classes\models\TestModel;

ORMBootstrap::setConnection('mysql', USERNAME, PASSWORD, DATABASE)::run();

try {

    (new TableGenerator(TestModel::class))->create();

    TestModel::insert('coucou', 1);

} catch (PDOException $e) {
    echo "PDO ".$e->getMessage()."\n";
} catch (Exception $e) {
    echo $e->getMessage()."\n";
}