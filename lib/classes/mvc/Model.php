<?php


namespace dis\orm\classes\mvc;


use DateTime;
use Exception;
use Illuminate\Database\Capsule\Manager as DB;
use dis\orm\classes\generators\TableGenerator;
use Illuminate\Database\Query\Builder;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;
use ReflectionClass;
use ReflectionException;

/**
 * Class Model
 * @package dis\orm\classes\mvc
 */
class Model extends \Illuminate\Database\Eloquent\Model {
    const EQUALS = '=';
    const INFERIOR = '<';
    const SUPERIOR = '>';
    const INFERIOR_EQUALS = '<=';
    const SUPERIOR_EQUALS = '>=';
    const DIFFERENT = '!=';
    const LIKE = 'like';
    const LIKE_BINARY = 'like binary';
    const NOT_LIKE = 'not like';
    const BINARY_AND = '&';
    const BINARY_OR = '|';
    const BINARY_LEFT = '<<';
    const BINARY_RIGHT = '>>';
    const REGEXP = 'regexp';
    const NOT_REGEXP = 'not regexp';
    const NOT_SIMILAR_TO = 'not similar to';

    protected array $fillable = [];

    protected array $hidden = [];

	private array $hidden_keys = [ 'hidden' ];

    private array $doc = [];

	/**
	 * @db_field
	 * @automatically-added
	 * @db_type timestamp
	 *
	 * @var integer
	 */
	protected int $created_at;

	/**
	 * @db_field
	 * @automatically-added
	 * @db_type timestamp
	 *
	 * @var integer
	 */
	protected int $updated_at;

    /**
     * Model constructor.
     * @param array $attributes
     * @throws ReflectionException
     */
    public function __construct(array $attributes = []) {
        $this->createProperties();
        parent::__construct($attributes);
    }

    /**
     * @param bool $static
     * @return array|null
     * @throws ReflectionException
     */
    public function createProperties($static = false): ?array {
        $doc = TableGenerator::getDocumentation(static::class)[static::class];
        if(!$static) {
            if (isset($doc['name'])) $this->table = $doc['name'];
            foreach ($doc['properties'] as $name => $items) {
                $hidden = false;
                foreach ($this->hidden_keys as $hidden_key) {
                    if (isset($items[$hidden_key])) {
                        $hidden = true;
                        break;
                    }
                }

                if ($hidden) $this->hidden[] = $name;
                else $this->fillable[] = $name;
            }
            if(!in_array('id', $this->fillable)) $this->fillable[] = 'id';
            return null;
        } else return $doc;
    }

    /**
     * @return array
     * @throws ReflectionException
     */
    public static function staticCreateProperties(): array {
        return static::createProperties(true);
    }

    /**
     * @param string $method
     * @param array $parameters
     * @return mixed|static
     * @throws ReflectionException
     * @throws Exception
     */
    public function __call($method, $parameters) {
	    $r = new ReflectionClass(static::class);
    	if(!$r->hasMethod($method)) {
		    preg_match( '/(?<methodType>get|set)(?<propertyName>[A-Z][a-zA-Z]+)$/sD', $method, $matches );
		    if ( ! empty( $matches ) && isset( $matches['methodType'] ) && isset( $matches['propertyName'] ) ) {
			    preg_match_all( '/([A-Z][a-z]+)/sD', $matches['propertyName'], $_matches );
			    $prop_parts = [];
			    foreach ( $_matches[1] as $k => $v ) {
				    $prop_parts[ $k ] = strtolower( $v );
			    }
			    $property = implode( '_', $prop_parts );
			    switch ( $matches['methodType'] ) {
				    case 'set':
					    if ( $r->hasProperty( $property ) ) {
						    $r->getProperty( $property )->setValue( $this, $parameters[0] );
					    } else if ( $r->hasProperty( $matches['propertyName'] ) ) {
						    $r->getProperty( $matches['propertyName'] )->setValue( $this, $parameters[0] );
					    } else {
						    throw new Exception( static::class . '::$' . $matches['propertyName'] . ' or ' . static::class . '::$' . $property . ' property not found !' );
					    }

					    return $this;
				    case 'get':
					    if ( $r->hasProperty( $property ) ) {
						    return $r->getProperty( $property )->getValue( $this );
					    } else if ( $r->hasProperty( $matches['propertyName'] ) ) {
						    return $r->getProperty( $matches['propertyName'] )->getValue( $this );
					    } else {
						    throw new Exception( static::class . '::$' . $matches['propertyName'] . ' or ' . static::class . '::$' . $property . ' property not found !' );
					    }
			    }
		    }
	    }
        return parent::__call($method, $parameters);
    }

    /**
     * @return Builder
     * @throws ReflectionException
     */
    public static function table(): Builder {
        $doc = static::staticCreateProperties();
        $name = isset($doc['name']) ? $doc['name'] : Str::snake(Str::pluralStudly(class_basename(static::class)));
        return DB::table($name);
    }

    /**
     * @param mixed[] ...$fields
     * @return bool
     * @throws Exception
     */
    public static function insert(...$fields): bool {
        if(empty($fields)) throw new Exception("Can't insert no values !");
        $properties = static::staticCreateProperties()['properties'];
        $properties_values = [];
        $i = 0;
        foreach ($properties as $property => $doc) {
            if(isset($fields[$i])) $properties_values[$property] = $fields[$i];
        }

        $current_timestamp = (new DateTime())->getTimestamp();

        $properties_values['created_at'] = $properties_values['updated_at'] = $current_timestamp;

        return static::table()->insert($properties_values);
    }

    /**
     * @param mixed $id
     * @param string[] ...$columns
     * @return Builder|static
     * @throws ReflectionException
     */
    public static function find($id, string ...$columns) {
        if(empty($columns)) $columns = ['*'];
        $result = static::table()->find($id, $columns);
        if($result instanceof \stdClass) {
            return new static((array)$result);
        }
        return $result;
    }

    /**
     * @param mixed ...$columns
     * @return Collection
     * @throws ReflectionException
     */
    public static function findAll(...$columns): Collection {
        return static::table()->get(empty($columns) ? ['*'] : $columns);
    }

    /**
     * @return array|null
     * @throws ReflectionException
     */
    public static function columns(): ?array {
        return static::table()->columns;
    }

    /**
     * @param string[] ...$columns
     * @return int
     * @throws ReflectionException
     */
    public static function count(...$columns): int {
        return static::table()->count(empty($columns) ? '*' : implode(', ', $columns));
    }

    /**
     * @param string $name
     * @param callable|object $macro
     * @throws ReflectionException
     */
    public static function macro(string $name, $macro): void {
        static::table()::macro($name, $macro);
    }

    /**
     * @param array $options
     *
     * @return bool
     * @throws Exception
     */
    public function save(array $options = []): bool {
        $this->setUpdatedAt((new DateTime())->getTimestamp());
        return parent::save($options);
    }

	/**
	 * @return int
	 */
    public function getCreatedAt(): int {
    	return $this->created_at;
    }

	/**
	 * @return int
	 */
	public function getUpdatedAt(): int {
		return $this->created_at;
	}
}