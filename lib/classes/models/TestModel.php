<?php


namespace dis\orm\classes\models;


use dis\orm\classes\mvc\Model;
use Illuminate\Database\Query\Builder;

/**
 * Class TestModel
 * @package dis\orm\classes\models
 *
 * @name test
 *
 * @method static bool insert(string $test, integer $user_id)
 * @method static Builder|TestModel find($id, string ...$columns)
 *
 * @method integer getId()
 *
 * @method string getTest()
 * @method static setTest(string $test)
 *
 * @method integer getUserId()
 * @method static setUserId(integer $userId)
 */
class TestModel extends Model {
    /**
     * @db_field
     * @db_type string
     *
     * @var string
     */
    public string $test;

    /**
     * @db_field
     * @db_type integer
     *
     * @var integer
     */
    public int $user_id;
}