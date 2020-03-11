<?php


require_once __DIR__.'/../vendor/autoload.php';

use dis\orm\classes\bootstrap\ORMBootstrap;
use dis\orm\classes\generators\TableGenerator;
use dis\orm\classes\models\TestModel;

ORMBootstrap::run();

(new TableGenerator(TestModel::class))->create();

$m = TestModel::getModel();
var_dump($m);