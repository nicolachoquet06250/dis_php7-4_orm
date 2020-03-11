<?php


namespace dis\orm\classes\mvc;


use dis\orm\classes\generators\TableGenerator;
use ReflectionException;

class Model extends \Illuminate\Database\Eloquent\Model {

    protected array $fillable = [];

    protected array $hidden = [];

    private array $hidden_keys = ['hidden'];

    /**
     * @throws ReflectionException
     */
    public function createProperties() {
        $class_doc = TableGenerator::getDocumentation(static::class);
        foreach ($class_doc[static::class]['properties'] as $name => $items) {
            $hidden = false;
            foreach ($this->hidden_keys as $hidden_key) {
                if(isset($items[$hidden_key])) {
                    $hidden = true;
                    break;
                }
            }

            if($hidden) $this->hidden[] = $name;
            else $this->fillable[] = $name;
        }
    }

    /**
     * @return static
     * @throws ReflectionException
     */
    public static function getModel() {
        $m = new static();
        $m->createProperties();
        return $m;
    }
}