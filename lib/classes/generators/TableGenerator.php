<?php


namespace dis\orm\classes\generators;


use DateTime;
use dis\orm\classes\mvc\Model;
use Illuminate\Database\Capsule\Manager;
use Illuminate\Database\Schema\Blueprint as Table;
use Illuminate\Database\Schema\ColumnDefinition;
use PDOException;
use ReflectionClass;
use ReflectionException;

class TableGenerator {
    private string $class;
    private array $parsedDoc = [];

    /**
     * TableGenerator constructor.
     * @param string $class
     */
    public function __construct(string $class) {
        $this->class = $class;
    }

    /**
     * @param string $doc
     * @return array
     */
    private function string2array(string $doc): array {
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
     * @throws ReflectionException
     */
    private function get_class_properties_doc() {
        $class = $this->class;
        $r = new ReflectionClass($class);
        foreach ($r->getProperties() as $property) {
            if($property->isPublic() || $property->isProtected()) {
                $doc = $this->string2array($property->getDocComment());
                if(!isset($this->parsedDoc[$class]['properties'])) $this->parsedDoc[$class]['properties'] = [];
                if(!empty($doc) && isset($doc['db_field'])) $this->parsedDoc[$class]['properties'][$property->getName()] = $doc;
            }
        }
    }

    /**
     * @return string
     */
    protected function getName(): string {
        if (isset($this->parsedDoc[$this->class]['name'])) {
            return $this->parsedDoc[$this->class]['name'];
        } else {
            return explode('\\', $this->class)[count(explode('\\', $this->class)) - 1];
        }
    }

    /**
     * @return array
     */
    protected function getProperties(): array {
        return $this->parsedDoc[$this->class]['properties'];
    }

    /**
     * @param string $class
     * @return array
     * @throws ReflectionException
     */
    public static function getDocumentation(string $class): array {
        $tg = new static($class);
        $tg->get_class_doc();
        $tg->get_class_properties_doc();
        return $tg->parsedDoc;
    }

    /**
     * @throws ReflectionException
     */
    public function create() {
        $this->get_class_doc();
        $this->get_class_properties_doc();

        try {
            if(!Manager::schema()->hasTable($this->getName())) {
                Manager::schema()->create($this->getName(), function (Table $table) {
                    if (!isset($this->getProperties()['id'])) $table->increments('id');
                    foreach ($this->getProperties() as $name => $items) {
                        if (isset($items['db_type']) && !isset($items['automatically-added'])) {
                            $db_type = $items['var'];
                            $db_type = explode(' ', $db_type)[0];
                            $items['db_type'] = $db_type;

                            if (in_array($items['db_type'], get_class_methods(get_class($table)))) {
                                /** @var ColumnDefinition $field */
                                $field = $table->{$items['db_type']}($name);
                                if (isset($items['nullable'])) $field->nullable();
                                if (isset($items['unique'])) $field->unique();
                                if (isset($items['unsigned'])) $field->unsigned();
                                if (isset($items['auto-increment'])) $field->autoIncrement();
                                if (isset($items['primary'])) $field->primary();
                                if (isset($items['default'])) {
                                    switch ($items['default']) {
                                        case 'NOW':
                                            if($db_type === 'date' || $db_type === 'datetime' || $db_type === 'timestamp')
                                                $items['default'] = (new DateTime())->getTimestamp();
                                            $field->default($items['default']);
                                            break;
                                        default:
                                            $field->default($items['default']);
                                            break;
                                    }
                                }
                            }
                        }
                    }

                    $table->timestamps();

                    foreach ($this->getProperties() as $name => $items) {
                        if (isset($items['foreign_key'])) {
                            $foreign = json_decode($items['foreign_key'], true);
                            $table->foreign($name)->references($foreign['reference'])->on($foreign['table'])->onDelete('cascade');
                        }
                    }
                });
                echo "`{$this->getName()}` table has been created\n";
            } else echo "`{$this->getName()}` table already exists\n";
        } catch (PDOException $e) {
            echo $e->getMessage()."\n";
        }
    }
}