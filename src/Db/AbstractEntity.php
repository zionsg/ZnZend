<?php
/**
 * ZnZend
 *
 * @link https://github.com/zionsg/ZnZend for canonical source repository
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
 * Values passed to setters and from getters should be the actual value as stored in the database,
 * eg. getEmails() would return a comma-delimited list of emails. If an array is expected, a separate
 * function, eg. getEmailsAs[] should be created to prevent confusion.
 *
 * @Annotation\Name("Entity")
 * @Annotation\Type("ZnZend\Form\Form")
 * @Annotation\Hydrator("Zend\Hydrator\ArraySerializable")
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
     * For AbstractEntity, database columns are mapped directly to entity properties,
     * eg. $person_id corresponds to the `person_id` column in the database table.
     *
     * The primary key column is usually set to null with Annotation\Exclude() as the value is generated
     * by the database.
     *
     * @Annotation\Exclude()
     * @var int
     */
    protected $id;

    /**
     * List of columns which were modified, using columns as keys and empty strings as values
     *
     * This is used for getModifiedArrayCopy() and gets populated every time set() is used.
     * Setters of extending classes should populate this if not using set().
     * This should be cleared after the initial populating of the entity, usually during exchange[].
     *
     * @Annotation\Exclude()
     * @example array('id' => '', ...)
     * @var array
     */
    protected $_modifiedColumns = [];

    /**
     * Authenticated identity (current logged in user)
     *
     * @Annotation\Exclude()
     * @var string
     */
    protected $_authIdentity;

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
     * This class assumes that for a property X, its getter is getX() and setter is setX().
     * This variable is used by mapGettersColumns() which is defined by EntityInterface.
     * The usage here greatly expands the scope defined in EntityInterface to reduce boilerplate code, seen
     * in the use of set() and get().
     *
     * Special note on getDeleted and isDeleted in the example below:
     *   isDeleted() is required in EntityInterface. Ideally, it would refer to a numeric column & cast 0/1 to boolean.
     *   In this case, mapping them here saves work on rewriting them for every entity class BUT separate
     *   setters/getters must still be written for the columns in order to store/return the actual numeric value.
     *   Eg: 'yes'/'no' is stored in the database for person_isdeleted - isDeleted() cannot simply cast to boolean here.
     *       getDeleted() returns 'yes', but isDeleted() returns true for ('yes' == $this->person_isdeleted).
     *
     * Additional notes on getters that map to SQL expression instead of a property and return computed information:
     *   An example is that of the database storing a duration in minutes but it has to be shown in hours in DataTables.
     *   A getter (setter not needed) must be written as such:
     *     public function getDurationHrs() { return round($this->duration_mins / 60, 2); }
     *   The mapping serves to register the getter as well as provide the SQL expression for use in column filtering:
     *     array(
     *         'getDurationMins' => 'duration_mins', // original mapping for database column & property `duration_mins`
     *         'getDurationHrs'  => 'ROUND(duration_mins / 60, 2)', // SQL expression used in column filtering
     *     )
     *
     * @example array(
     *              'getId'       => 'person_id', // maps directly to column (maps to property $person_id here)
     *              'getFullName' => "CONCAT(person_firstname, ' ', person_lastname)", // maps to SQL expression
     *              'isSuspended' => '! enabled',  // simple negation of properties is allowed (in this case $enabled)
     *              'isHidden'    => false,       // boolean values are allowed (in this case all records are visible)
     *              'getDeleted'  => 'person_isdeleted',
     *              'isDeleted'   => 'person_isdeleted',
     *          )
     * @Annotation\Exclude()
     * @var array
     */
    protected static $_mapGettersColumns = [
        // The mappings below are for the getters defined in EntityInterface
        // and are provided for easy copying when coding extending classes
        'getId'     => 'id',
        'getName'   => 'name',
        'isDeleted' => false,
    ];

    /**
     * Array mapping columns to getters - computed by mapColumnsGetters()
     *
     * Cannot use static - if an entity class's mapColumnsGetters() was called and this was initialized,
     * the next entity will see this as not null and return the previous entity's mapping.
     *
     * @Annotation\Exclude()
     * @var null|array Initialize to null as computed result might be an empty array
     */
    protected $_mapColumnsGetters = null;

    /**
     * Constructor
     *
     * @param array $data Optional array to populate entity
     */
    public function __construct(array $data = [])
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
     * This uses $_mapGettersColumns - a column must be mapped and have a getter
     * for the corresponding key in $data. The name of the setter is then inferred - for getX(),
     * the corresponding setter is assumed to be setX().
     * Extending classes should override this if this is not desired.
     *
     * This method is used by \Zend\Hydrator\ArraySerializable::hydrate()
     * typically in forms to populate an object.
     *
     * @param  array $data
     * @return void
     */
    public function exchangeArray(array $data)
    {
        if (empty($data)) {
            return;
        }

        $map = $this->mapColumnsGetters();
        foreach ($data as $key => $value) {
            if (! array_key_exists($key, $map)) {
                continue;
            }
            $getter = $map[$key];
            $setter = substr_replace($getter, 'set', 0, 3);
            if (is_callable([$this, $setter])) {
                $this->$setter($value);
            }
        }

        // Clear modified flags
        $this->_modifiedColumns = [];
    }

    /**
     * Defined by ArraySerializableInterface via EntityInterface; Get entity properties as an array
     *
     * This uses $_mapGettersColumns and calls all the getters to populate the array.
     * By default, properties prefixed with an underscore will be omitted.
     *
     * All values are cast to string for use in forms and database queries.
     * If the value is DateTime, $value->format('c') is used to return the ISO 8601 timestamp.
     * If the value is an array, it will be imploded into a comma-delimited list.
     * If the value is an object, $value->__toString() must be defined.
     * Extending classes should override this if any of the above is not desired.
     *
     * This method is used by \Zend\Hydrator\ArraySerializable::extract()
     * typically in forms to extract values from an object.
     *
     * @return array
     */
    public function getArrayCopy()
    {
        $result = [];
        $map = $this->mapColumnsGetters();
        foreach ($map as $column => $getter) {
            // Skip if column is not a property (eg. an SQL expression) or is prefixed with an underscore
            if (! property_exists($this, $column) || '_' == substr($column, 0, 1)) {
                continue; // in case the column is an SQL expression
            }
            $value = $this->$getter();
            if ($value instanceof DateTime) {
                $value = $value->format('c');
            } elseif (is_array($value)) {
                $value = implode(',', $value);
            }
            $result[$column] = (string) $value;
        }

        return $result;
    }

    /**
     * Defined by EntityInterface; Get modified entity properties as an array
     *
     * This only works if every setter adds the column name to $_modifiedColumns.
     * This is automatically done if the setter uses set().
     *
     * @param  array|EntityInterface $modifiedData If this is passed in, the values which are different
     *                                             from getArrayCopy() are returned
     * @return array
     */
    public function getModifiedArrayCopy($modifiedData = null)
    {
        if (null === $modifiedData) {
            return array_intersect_key($this->getArrayCopy(), $this->_modifiedColumns);
        }

        if ($modifiedData instanceof EntityInterface) {
            $modifiedData = $modifiedData->getArrayCopy();
        }
        if (! is_array($modifiedData)) {
            return [];
        }

        // A new array must be used as $modifiedData may contain additional keys, eg. from the form
        $result = [];
        foreach ($this->getArrayCopy() as $key => $value) {
            if (! isset($modifiedData[$key])) {
                continue;
            }
            if ($value != $modifiedData[$key]) {
                $result[$key] = $modifiedData[$key];
            }
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
        return strtolower(str_replace(['\\', '/', '_'], '.', get_called_class()));
    }

    /**
     * Defined by EntityInterface; Map getters to column names in table
     *
     * @return array
     */
    public static function mapGettersColumns()
    {
        return static::$_mapGettersColumns;
    }

    /**
     * Defined by EntityInterface; Get name of getter mapped to property
     *
     * @param  string $property Name of property
     * @return null|string Return null if getter not found
     */
    public function getPropertyGetter($property)
    {
        $map = $this->mapColumnsGetters();
        if (empty($map[$property])) {
            return null;
        }
        return $map[$property];
    }

    /**
     * Defined by EntityInterface; Get resource id for entity property
     *
     * @example getPropertyResourceIdFromGetter('id') returns znzend.db.abstractentity.id
     * @example getPropertyResourceIdFromGetter($idFormElement->getName()) returns znzend.db.abstractentity.id
     * @param   string $property Name of property
     * @return  null|string Return null if property does not exist
     */
    public function getPropertyResourceId($property)
    {
        if (! property_exists($this, $property)) {
            return null;
        }

        return $this->getResourceId() . '.' . $property;
    }

    /**
     * Defined by EntityInterface; Store authenticated identity (current logged in user)
     *
     * @param  string $identity
     * @return EntityInterface
     */
    public function setAuthIdentity($identity)
    {
        $this->_authIdentity = (string) $identity; // objects must implement __toString()
        return $this;
    }

    /**
     * Defined by EntityInterface; Retrieve stored authenticated identity
     *
     * @return string
     */
    public function getAuthIdentity()
    {
        return $this->_authIdentity;
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
     * Compute array for mapping columns to getters
     *
     * array_flip was used previously but would run into errors if null or boolean values
     * were set in $_mapGettersColumns - array_flip can only flip strings and integers.
     *
     * Only columns mapping to actual getters such as getX() will be returned,
     * not those mapping to isX(), SQL expressions, negated properties or boolean values.
     * This behaviour is depended upon when used in exchange[] to infer names of setters and
     * in getArrayCopy() to ensure only actual getters are used (eg. ensure isDeleted() does not override
     * getDeleted() with boolean value).
     *
     * @return array
     */
    protected function mapColumnsGetters()
    {
        if (null === $this->_mapColumnsGetters) {
            $map = [];
            foreach (static::$_mapGettersColumns as $getter => $column) {
                // Only include if mapped function is of getX() format
                if (is_string($column)
                    && 'get' === substr($getter, 0, 3)
                    && strtoupper(substr($getter, 3, 1)) === substr($getter, 3, 1)
                ) {
                    $map[$column] = $getter;
                }
            }
            $this->_mapColumnsGetters = $map;
        }
        return $this->_mapColumnsGetters;
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
     *                               In general, for setX(), the corresponding getter is getX().
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
            if (array_key_exists($getFunc, $map)) {
                $property = $map[$getFunc];
            }
        }

        if (! property_exists($this, $property)) {
            throw new Exception\InvalidArgumentException("Property \"{$property}\" does not exist.");
        }

        // Cast to specified type before setting - skip if value is null or no type specified
        if ($value !== null && $type !== null) {
            if ($type == strtolower($type)) { // primitive type
                settype($value, $type);
            } elseif ('DateTime' == $type && ! $value instanceof DateTime) { // special handling for DateTime
                $value = (string) $value;
                $intValue = (int) $value; // for checking default value of "0000-00-00 00:00:00.00000" from database
                $value = (! $intValue || false === strtotime($value)) ? null : new DateTime($value);
            } else { // object
                $value = new $type($value);
            }
        }

        // Set value and indicate that property has been modified
        $this->$property = $value;
        $this->_modifiedColumns[$property] = ''; // empty value is just a placeholder
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

        // Handle boolean values
        if (true === $property || false === $property) {
            return $property;
        }

        // Handle simple negation of property
        $negate = false;
        if ('!' == substr($property, 0, 1)) {
            $negate = true;
            $property = substr($property, 1);
        }
        if (property_exists($this, $property)) {
            return ($negate ? ! $this->$property : $this->$property);
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

    /**
     * Compute bitflag from array of bits to set using multiples of 2
     *
     * @example array array(1, 2, 5) results in 7
     * @param   array $bitsToSet Array of bits to set
     * @return  int   Computed bitflag
     */
    protected function arrayToBitflag(array $setBits)
    {
        $bitflag = 0;
        foreach ($setBits as $bit) {
            $bitflag |= (int) $bit; // '1' and 1 will yield different results hence cast to int
        }
        return $bitflag;
    }

    /**
     * Extract set bits in a bit flag into an array using multiples of 2
     *
     * @example 7 results in array(1, 2, 4)
     * @param   int   $bitflag Bitflag - max value is PHP_INT_MAX
     * @return  array Array of values for set bits
     */
    protected function bitflagToArray($bitflag)
    {
        $bitflag = (int) $bitflag; // '1' and 1 will yield different results hence cast to int
        $highestBitSet = 1 << floor(log10($bitflag) / log10(2));

        $setBits = [];
        for ($bit = 1; $bit <= $highestBitSet; $bit *= 2) {
            if (($bit & $bitflag) != 0) {
                $setBits[] = $bit;
            }
        }

        return $setBits;
    }
}
