<?php
/**
 * ZnZend
 *
 * @author Zion Ng <zion@intzone.com>
 * @link   http://github.com/zionsg/ZnZend for canonical source repository
 */

namespace ZnZend\Db;

use DateTime;
use Zend\Form\Annotation;
use Zend\Permissions\Acl\Resource\ResourceInterface;
use Zend\Stdlib\ArraySerializableInterface;
use ZnZend\Db\EntityInterface;
use ZnZend\Db\Exception;

/**
 * Base class for entities corresponding to rows in database tables
 *
 * Methods from EntityInterface are implemented for defaults and should
 * be overwritten by concrete classes if required.
 *
 * Getters are preferred over public properties as the latter would likely be named
 * after the actual database columns, which the user should not know about.
 * Also, additional validation/other stuff can be added to the setter/getter without having
 * to get everyone in the world to convert their code from $entity->foo = $x; to $entity->setFoo($x);
 *
 * Typically, type checking/casting is done once in setters and not repeated in getters.
 *
 * @Annotation\Name("Entity")
 * @Annotation\Type("ZnZend\Form\Form")
 * @Annotation\Hydrator("Zend\Stdlib\Hydrator\ArraySerializable")
 */
abstract class AbstractEntity implements EntityInterface
{
    /**
     * NOTE: 5 things to do for each entity property: protected, Annotation, setter, getter, $_mapGettersColumns
     *
     * $id is for example only, and fulfils the 5 things above - setter is setId() and getter is getId().
     * Internal variables should be prefixed with an underscore and Annotation\Exclude(),
     * eg. $_mapGettersColumns, to differentiate between them and entity properties.
     *
     * The primary key column is usually set to null with Annotation\Exclude() as the value is generated
     * by the database.
     *
     * @Annotation\Exclude()
     * @var int
     */
    protected $id;

    /**
     * Singular noun for entity - to be set by extending classes
     *
     * @Annotation\Exclude()
     * @var string
     */
    protected $_singularNoun = 'entity';

    /**
     * Plural noun for entity - to be set by extending classes
     *
     * @Annotation\Exclude()
     * @var string
     */
    protected $_pluralNoun = 'entities';

    /**
     * Array mapping getters to columns - to be set by extending class
     *
     * Various parts of this class assume that for a getter getX() or isX(),
     * the corresponding setter will be setX().
     *
     * @Annotation\Exclude()
     * @example array(
     *              'getId' => 'person_id', // maps to property
     *              'getFullName' => "CONCAT(person_firstname, ' ', person_lastname)"), // maps to SQL expression
     *              'isDeleted' => '!enabled', // simple negation is allowed
     *          )
     * @var array
     */
    protected static $_mapGettersColumns = array(
        // The mappings below are for the getters defined in EntityInterface
        // and are provided for easy copying when coding extending classes
        'getId'     => 'id',
        'getName'   => 'name',
        'isHidden'  => 'ishidden',
        'isDeleted' => 'isdeleted',
    );

    /**
     * Constructor
     *
     * @param array $data Optional array to populate entity
     */
    public function __construct(array $data = array())
    {
        $this->exchangeArray($data);
    }

    /**
     * Defined by EntityInterface; Value when entity is treated as a string
     *
     * This is vital if a getter such as getCreator() returns an EntityInterface (instead of string)
     * and it is used in log text or in a view script. Should default to getName().
     *
     * @return string
     */
    public function __toString()
    {
        return $this->getName();
    }

    /**
     * Defined by ArraySerializableInterface via EntityInterface; Set entity properties from array
     *
     * This uses $_mapGettersColumns - a column must be mapped and have a setter
     * for the corresponding key in $data to be set. In general, for getX() or isX(),
     * the corresponding setter is assumed to be setX().
     * Extending classes should override this if this is not desired.
     *
     * This method is used by \Zend\Stdlib\Hydrator\ArraySerializable::hydrate()
     * typically in forms to populate an object.
     *
     * @param  array $data
     * @return void
     */
    public function exchangeArray(array $data)
    {
        $map = array_flip(static::$_mapGettersColumns);
        foreach ($data as $key => $value) {
            if (!array_key_exists($key, $map)) {
                continue;
            }
            $getter = $map[$key];
            $setter = ('get' === substr($getter, 0, 3))
                    ? substr_replace($getter, 'set', 0, 3)  // getX() becomes setX()
                    : substr_replace($getter, 'set', 0, 2); // isX() becomes setX()
            if (is_callable(array($this, $setter))) {
                $this->$setter($value);
            }
        }
    }

    /**
     * Defined by ArraySerializableInterface via EntityInterface; Get entity properties as an array
     *
     * This uses $_mapGettersColumns and calls all the getters to populate the array.
     * All values are cast to string for use in forms and database calls.
     * If the value is DateTime, $value->format('c') is used to return the ISO 8601 timestamp.
     * If the value is an object, $value->__toString() must be defined.
     * Extending classes should override this if this is not desired.
     *
     * This method is used by \Zend\Stdlib\Hydrator\ArraySerializable::extract()
     * typically in forms to extract values from an object.
     *
     * @return array
     */
    public function getArrayCopy()
    {
        $result = array();
        $map = static::$_mapGettersColumns;
        foreach ($map as $getter => $column) {
            if (!property_exists($this, $column)) {
                continue; // in case the column is an SQL expression
            }
            $value = $this->$getter();
            if ($value instanceof DateTime) {
                $value = $value->format('c');
            }
            $result[$column] = (string) $value;
        }

        return $result;
    }

    /**
     * Defined by ResourceInterface via EntityInterface; Returns the string identifier of the Resource
     *
     * By default, the resource id is derived from the class name.
     * Eg: ZnZend\Db\AbstractEntity becomes znzend.db.abstractentity
     *
     * @return string
     */
    public function getResourceId()
    {
        return strtolower(str_replace(array('\\', '/', '_'), '.', get_called_class()));
    }

    /**
     * Defined by EntityInterface; Map getters to column names in table
     *
     * @example array('getId' => 'person_id', 'getFullName' => "CONCAT(person_firstname, ' ', person_lastname)")
     * @return  array
     */
    public static function mapGettersColumns()
    {
        return static::$_mapGettersColumns;
    }

    /**
     * Get resource id for entity property
     *
     * @example getPropertyResourceIdFromGetter('id') returns znzend.db.abstractentity.id
     * @example getPropertyResourceIdFromGetter($idFormElement->getName()) returns znzend.db.abstractentity.id
     * @param   string $property Name of property
     * @return  null|string Return null if property does not exist
     */
    public function getPropertyResourceId($property)
    {
        if (!property_exists($this, $property)) {
            return null;
        }

        return $this->getResourceId() . '.' . $property;
    }

    /**
     * Get resource id for entity property using getter to identify property
     *
     * Property getter must be registered in $_mapGettersColumns.
     *
     * @example getPropertyResourceIdFromGetter('getId') returns znzend.db.abstractentity.id
     * @param   string $propertyGetter Name of getter used to retrieve property
     * @return  null|string Return null if property does not exist
     */
    public function getPropertyResourceIdFromGetter($propertyGetter)
    {
        $map = static::$_mapGettersColumns;
        if (empty($map[$propertyGetter])) {
            return null;
        }

        return $this->getPropertyResourceId($map[$propertyGetter]);
    }

    /**
     * Defined by EntityInterface; Set singular noun for entity (lowercase)
     *
     * @param  string $value
     * @return EntityInterface
     */
    public function setSingularNoun($value)
    {
        $this->_singularNoun = strtolower($value);
        return $this;
    }

    /**
     * Defined by EntityInterface; Get singular noun for entity (lowercase)
     *
     * @example 'person'
     * @return  string
     */
    public function getSingularNoun()
    {
        return $this->_singularNoun;
    }

    /**
     * Defined by EntityInterface; Set plural noun for entity (lowercase)
     *
     * @param  string $value
     * @return EntityInterface
     */
    public function setPluralNoun($value)
    {
        $this->_pluralNoun = strtolower($value);
        return $this;
    }

    /**
     * Defined by EntityInterface; Get plural noun for entity (lowercase)
     *
     * @example 'people'
     * @return  string
     */
    public function getPluralNoun()
    {
        return $this->_pluralNoun;
    }

    /**
     * Defined by EntityInterface; Set record id
     *
     * @param  null|int $value
     * @return EntityInterface
     */
    public function setId($value)
    {
        // Alternative: $this->set($value, 'int', 'id') where 'id' is the column name
        return $this->set($value, 'int');
    }

    /**
     * Defined by EntityInterface; Get record id
     *
     * @return null|int
     */
    public function getId()
    {
        // Alternative: $this->get('id') where 'id' is the column name
        return $this->get();
    }

    /**
     * Defined by EntityInterface; Set name
     *
     * @param  null|string $value
     * @return EntityInterface
     */
    public function setName($value)
    {
        return $this->set($value);
    }

    /**
     * Defined by EntityInterface; Get name
     *
     * @return null|string
     */
    public function getName()
    {
        return $this->get();
    }

    /**
     * Defined by EntityInterface; Set hidden status of entity
     *
     * @param  mixed $value Value is not cast to boolean to reflect actual value in database
     * @return EntityInterface
     */
    public function setHidden($value)
    {
        return $this->set($value);
    }

    /**
     * Defined by EntityInterface; Check whether entity is marked as hidden
     *
     * @return bool
     */
    public function isHidden()
    {
        return (bool) $this->get();
    }

    /**
     * Defined by EntityInterface; Set deleted status of entity
     *
     * @param  mixed $value Value is not cast to boolean to reflect actual value in database
     * @return EntityInterface
     */
    public function setDeleted($value)
    {
        return $this->set($value);
    }

    /**
     * Defined by EntityInterface; Check whether entity is marked as deleted
     *
     * Ideally, no records should ever be deleted from the database and
     * should have a field to mark it as deleted instead.
     *
     * @return bool
     */
    public function isDeleted()
    {
        return (bool) $this->get();
    }

    /**
     * Generic internal setter for entity properties
     *
     * @param  null|mixed  $value    Value to set
     * @param  null|string $type     Optional data type to cast to, if any. No casting is done
     *                               if value is null. If $type is lowercase, cast
     *                               to primitive type, eg. (string) $value, else cast to object,
     *                               eg. new DateTime($value).
     * @param  null|string $property Optional property to set $value to. If not specified,
     *                               $_mapGettersColumns is checked for the corresponding getter
     *                               of the calling function to get the mapped property.
     *                               In general, for setX(), the corresponding getter is either
     *                               getX() or isX().
     * @throws Exception\InvalidArgumentException Property does not exist
     * @return AbstractEntity For fluent interface
     */
    protected function set($value, $type = null, $property = null)
    {
        // Check if property exists
        if (null === $property) {
            $trace = debug_backtrace();
            $callerFunction = $trace[1]['function'];
            $map = static::$_mapGettersColumns;

            $getFunc = substr_replace($callerFunction, 'get', 0, 3);
            $isFunc = substr_replace($callerFunction, 'is', 0, 3);
            if (array_key_exists($getFunc, $map)) {
                $property = $map[$getFunc];
            } elseif (array_key_exists($isFunc, $map)) {
                $property = $map[$isFunc];
            }
        }

        if (!property_exists($this, $property)) {
            throw new Exception\InvalidArgumentException("Property \"{$property}\" does not exist.");
        }

        // Cast to specified type before setting - skip if value is null or no type specified
        if ($value !== null && $type !== null) {
            if ($type == strtolower($type)) { // primitive type
                settype($value, $type);
            } elseif ('DateTime' == $type && !$value instanceof DateTime) { // special handling for DateTime
                $value = (string) $value;
                $value = (false === strtotime($value)) ? null : new DateTime($value);
            } else { // object
                $value = new $type($value);
            }
        }

        $this->$property = $value;
        return $this;
    }

    /**
     * Generic internal getter for entity properties
     *
     * @param  null|string $property Optional property to retrieve. If not specified,
     *                               $_mapGettersColumns is checked for the name of the calling
     *                               function to get the mapped property.
     * @param  null|mixed  $default  Optional default value if key or property does not exist
     * @return mixed
     * @internal E_USER_NOTICE is triggered if property does not exist
     */
    protected function get($property = null, $default = null)
    {
        if (null === $property) {
            $trace = debug_backtrace();
            $callerFunction = $trace[1]['function'];
            $map = static::$_mapGettersColumns;
            if (array_key_exists($callerFunction, $map)) {
                $property = $map[$callerFunction];
            }
        }

        // Handle simple negation of property
        $negate = false;
        if ('!' == substr($property, 0, 1)) {
            $negate = true;
            $property = substr($property, 1);
        }
        if (property_exists($this, $property)) {
            return ($negate ? !$this->$property : $this->$property);
        }

        if (empty($trace)) {
            $trace = debug_backtrace();
        }
        trigger_error(
            sprintf(
                'Undefined property "%s" via %s::%s() in %s on line %s',
                $property,
                get_class($trace[1]['object']),
                $trace[1]['function'],
                $trace[1]['file'],
                $trace[1]['line']
            ),
            E_USER_NOTICE
        );

        return $default;
    }
}
