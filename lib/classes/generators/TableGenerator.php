<?php


namespace dis\orm\classes\generators;

require_once __DIR__.'/../../../vendor/autoload.php';


use dis\orm\classes\models\TestModel;
use Illuminate\Database\Capsule\Manager;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Schema\Blueprint as Table;
use Illuminate\Database\Schema\ColumnDefinition;
use ReflectionClass;
use ReflectionException;

class TableGenerator {
    private string $class;
    private array $parsedDoc = [];

    private function string2array(string $doc) {
        if ($doc) {
            $doc = str_replace(['/**', "\t", ' * ', '*/', ' *'], '', $doc);
            $doc = explode("\n", $doc);
            $doc = array_map(fn ($doc) => trim($doc), $doc);
            $tmp = [];
            foreach ($doc as $item) {
                if ($item !== '') $tmp[] = $item;
            }
            $doc = $tmp;
            foreach ($doc as $i => $item) {
                preg_match('/\@(?<key>[a-zA-Z\_\-]+)?(\ ?(?<value>[^\@\n]+))?$/sD', $item, $matches);
                if (!empty($matches)) {
                    if (!isset($matches['key']) || $matches['key'] === '') $matches['key'] = 'description';
                    if (isset($doc[$matches['key']]) && !is_array($doc[$matches['key']])) {
                        $doc[$matches['key']] = [$doc[$matches['key']], $matches['value']];
                    } else {
                        $doc[$matches['key']] = isset($matches['value']) ? $matches['value'] : true;
                    }
                }
                unset($doc[$i]);
            }
            return $doc;
        }
        return [];
    }

    /**
     * @param string $class
     * @throws ReflectionException
     */
    private function get_class_doc() {
        $class = $this->class;
        $r = new ReflectionClass($class);
        $class_doc = $r->getDocComment();
        if($r->getParentClass() && $r->getParentClass()->getName() === Model::class) {
            $doc = $this->string2array($class_doc);
            if(!empty($doc)) $this->parsedDoc[$class] = $doc;
        }
    }

    /**
     * @param string $class
     * @throws ReflectionException
     */
    private function get_class_properties_doc() {
        $class = $this->class;
        $r = new ReflectionClass($class);
        foreach ($r->getProperties() as $property) {
            if($property->isPublic()) {
                $doc = $this->string2array($property->getDocComment());
                if(!isset($this->parsedDoc[$class]['properties'])) $this->parsedDoc[$class]['properties'] = [];
                if(!empty($doc) && isset($doc['db_field'])) $this->parsedDoc[$class]['properties'][$property->getName()] = $doc;
            }
        }
    }

    /**
     * @return string
     */
    protected function getName() {
        if (isset($this->parsedDoc[$this->class]['name'])) {
            return $this->parsedDoc[$this->class]['name'];
        } else {
            return explode('\\', $this->class)[count(explode('\\', $this->class)) - 1];
        }
    }

    protected function getProperties() {
        return $this->parsedDoc[$this->class]['properties'];
    }

    /**
     * @param string $class
     * @throws ReflectionException
     */
    public function parse(string $class) {
        $this->class = $class;
        $this->get_class_doc();
        $this->get_class_properties_doc();
        var_dump($this->parsedDoc);
//        Manager::schema()->create($this->getName(), function (Table $table) {
//            if(!isset($this->getProperties()['id'])) {
//                $table->increments('id')->primary();
//            }
//            foreach ($this->getProperties() as $name => $items) {
//                if(isset($items['db_type'])) {
//                    $db_type = $items['var'];
//                    $db_type = explode(' ', $db_type)[0];
//                    $items['db_type'] = $db_type;
//                }
//                if(in_array($items['db_type'], get_class_methods(get_class($table)))) {
//                    /** @var ColumnDefinition $field */
//                    $field = $table->{$items['db_type']}($name);
//                    if(isset($items['nullable'])) $field->nullable();
//                    if(isset($items['unique'])) $field->unique();
//                    if(isset($items['unsigned'])) $field->unsigned();
//                    if(isset($items['auto-increment'])) $field->autoIncrement();
//                    if(isset($items['primary'])) $field->primary();
//                    if(isset($items['default'])) $field->default($items['default']);
//                }
//            }
//
//            $table->timestamps();
//
//            foreach ($this->getProperties() as $name => $items) {
//                if(isset($items['foreign_key'])) {
//                    $foreign = json_decode($items['foreign_key'], true);
//                    $table->foreign($name)->references($foreign['reference'])->on($foreign['table'])->onDelete('cascade');
//                }
//            }
//        });
    }
}

$table_generator = new TableGenerator();

$table_generator->parse(TestModel::class);