<?php
/**
 * ZnZend
 *
 * @author Zion Ng <zion@intzone.com>
 * @link   http://github.com/zionsg/ZnZend for canonical source repository
 */

namespace ZnZend\Model;

use DateTime;
use Zend\Stdlib\ArraySerializableInterface;
use ZnZend\Model\Exception;

/**
 * Base class for entities corresponding to rows in database tables
 *
 * Methods from EntityInterface are implemented as examples and should
 * be overwritten by concrete classes.
 */
abstract class AbstractEntity implements ArraySerializableInterface, EntityInterface
{
    /**
     * Array of property-value pairs for entity - set by constructor and exchangeArray(), not extending class
     *
     * Properties map exactly to columns.
     * Use array_key_exists() instead of isset() to check if a key exists.
     * If a key exists and its value is NULL, isset() will return false.
     *
     * @var array
     */
    protected $data = array();

    /**
     * Array mapping getters to columns - to be set by extending class
     *
     * @example array('getId' => 'person_id', 'getFullName' => "CONCAT(person_firstname, ' ', person_lastname)")
     * @var array
     */
    protected static $mapGettersColumns = array(
        // The mappings below are for the getters defined in EntityInterface
        // and are provided for easy copying when coding extending classes
        'getId'          => 'id',
        'getName'        => 'name',
        'getDescription' => 'description',
        'getThumbnail'   => 'thumbnail',
        'getPriority'    => 'priority',
        'getCreated'     => 'created',
        'getCreator'     => 'creator',
        'getUpdated'     => 'updated',
        'getUpdator'     => 'updator',
        'isHidden'       => 'ishidden',
        'isDeleted'      => 'isdeleted',
    );

    /**
     * Constructor
     *
     * Takes in an optional array to populate entity
     *
     * @param array $data
     */
    public function __construct(array $data = array())
    {
        $this->exchangeArray($data);
    }

    /**
     * Defined by ArraySerializableInterface; Set entity properties from array
     *
     * This method is used by \Zend\Stdlib\Hydrator\ArraySerializable::hydrate()
     * to populate an object.
     *
     * @param  array $data
     * @return void
     */
    public function exchangeArray(array $data)
    {
        $this->data = $data;
    }

    /**
     * Defined by ArraySerializableInterface; Get entity properties as an array
     *
     * This method is used by \Zend\Stdlib\Hydrator\ArraySerializable::extract()
     * to extract values from an object.
     *
     * @return array
     */
    public function getArrayCopy()
    {
        return $this->data;
    }

    /**
     * Defined by EntityInterface; Map getters to column names in table
     *
     * Getters should be used in view scripts to retrieve information instead of properties
     * which in this case would be named after database columns, which the view should not know about.
     * This method can be used by a controller plugin (eg. \ZnZend\Controller\Plugin\DataTables) to
     * work with pagination filtering/sorting params submitted from the view script together with
     * the names of the getters used for each <table> column in the view script and update the Select
     * object accordingly.
     *
     * @example array('getId' => 'person_id', 'getFullName' => "CONCAT(person_firstname, ' ', person_lastname)")
     * @return  array
     */
    public static function mapGettersColumns()
    {
        $caller = get_called_class();
        return $caller::$mapGettersColumns;
    }

    /**
     * Generic internal getter for entity properties
     *
     * @param  null|string $property Optional property to retrieve. If not specified,
     *                               $mapGettersColumns is checked for the name of the calling
     *                               function.
     * @param  null|mixed  $default  Optional default value if key or property does not exist
     * @return mixed
     * @internal E_USER_NOTICE is triggered if property does not exist
     */
    protected function get($property = null, $default = null)
    {
        if (null === $property) {
            $trace = debug_backtrace();
            $callerFunction = $trace[1]['function'];
            $callerClass = get_called_class(); // 'self::' will point to AbstractEntity, hence this
            if (array_key_exists($callerFunction, $callerClass::$mapGettersColumns)) {
                $property = $callerClass::$mapGettersColumns[$callerFunction];
            }
        }

        if (array_key_exists($property, $this->data)) {
            return $this->data[$property];
        }

        if (empty($trace)) {
            $trace = debug_backtrace();
        }
        trigger_error(
            sprintf(
                'Undefined property via get(): %s in %s on line %s',
                $property,
                $trace[0]['file'],
                $trace[0]['line']
            ),
            E_USER_NOTICE
        );

        return $default;
    }

    /**
     * Generic internal setter for entity properties
     *
     * @param  string $property Property to set
     * @param  mixed  $value    Value to set
     * @throws Exception\InvalidArgumentException Property does not exist
     * @return AbstractEntity   For fluent interface
     */
    protected function set($property, $value)
    {
        if (!array_key_exists($property, $this->data)) {
            throw new Exception\InvalidArgumentException("Property \"{$property}\" does not exist.");
        }

        $this->data[$property] = $value;
        return $this;
    }

    /**
     * Defined by EntityInterface; Retrieve record id of entity
     *
     * @return null|int
     */
    public function getId()
    {
        // Alternative: $this->get('id') where 'id' is the column name
        return (int) $this->get();
    }

    /**
     * Defined by EntityInterface; Retrieve name of entity
     *
     * @return null|string
     */
    public function getName()
    {
        return $this->get();
    }

    /**
     * Defined by EntityInterface; Retrieve description of entity
     *
     * @return null|string
     */
    public function getDescription()
    {
        return $this->get();
    }

    /**
     * Defined by EntityInterface; Retrieve filename of thumbnail image for entity
     *
     * Typical thumbnail fits in a box of 160 x 160 pixels, usually used when
     * listing entities. Can refer to the logo of an establishment.
     *
     * @return null|string
     */
    public function getThumbnail()
    {
        return $this->get();
    }

    /**
     * Defined by EntityInterface; Retrieve priority of entity
     *
     * When listing entities, smaller numbers typically come first.
     *
     * @return null|int
     */
    public function getPriority()
    {
        return (int) $this->get();
    }

    /**
     * Defined by EntityInterface; Retrieve timestamp when entity was created
     *
     * Need to handle default DATETIME value of '0000-00-00 00:00:00' in SQL
     *
     * @return null|DateTime
     */
    public function getCreated()
    {
        $timestamp = $this->get();
        if (false === strtotime($timestamp)) {
            return null;
        }

        return new DateTime($timestamp);
    }

    /**
     * Defined by EntityInterface; Retrieve user who created the entity
     *
     * A simple string can be returned (eg. userid) or preferrably, an object
     * which implements EntityInterface.
     *
     * @return null|string|EntityInterface
     */
    public function getCreator()
    {
        return $this->get();
    }

    /**
     * Defined by EntityInterface; Retrieve timestamp when entity was last updated
     *
     * Need to handle default DATETIME value of '0000-00-00 00:00:00' in SQL
     *
     * @return null|DateTime
     */
    public function getUpdated()
    {
        $timestamp = $this->get();
        if (false === strtotime($timestamp)) {
            return null;
        }

        return new DateTime($timestamp);
    }

    /**
     * Defined by EntityInterface; Retrieve user who last updated the entity
     *
     * A simple string can be returned (eg. userid) or preferrably, an object
     * which implements EntityInterface.
     *
     * @return null|string|EntityInterface
     */
    public function getUpdator()
    {
        return $this->get();
    }

    /**
     * Defined by EntityInterface; Check whether entity is marked as hidden
     *
     * @return boolean
     */
    public function isHidden()
    {
        return (bool) $this->get();
    }

    /**
     * Defined by EntityInterface; Check whether entity is marked as deleted
     *
     * Ideally, no records should ever be deleted from the database and
     * should have a field to mark it as deleted instead.
     *
     * @return boolean
     */
    public function isDeleted()
    {
        return (bool) $this->get();
    }
}
