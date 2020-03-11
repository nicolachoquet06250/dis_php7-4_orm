<?php


namespace dis\orm\classes\models;


use dis\orm\classes\mvc\Model;

/**
 * Class TestModel
 * @package dis\orm\classes\models
 *
 * @name test
 */
class TestModel extends Model {

    /**
     * @db_field
     * @db_type string
     * @hidden
     *
     * @var string
     */
    public string $test;

    /**
     * @db_field
     * @db_type integer
     * @foreign_key {"reference": "id", "table": "users"}
     *
     * @var integer
     */
    public int $user_id;
}