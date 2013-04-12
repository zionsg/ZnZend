<?php
/**
 * ZnZend
 *
 * @author Zion Ng <zion@intzone.com>
 * @link   http://github.com/zionsg/ZnZend for canonical source repository
 */

namespace ZnZend\Model;

use ZnZend\Model\Exception;

/**
 * Base class for entities corresponding to rows in database tables
 *
 * Methods from EntityInterface are implemented as examples and should
 * be overwritten by concrete classes.
 */
abstract class AbstractEntity implements EntityInterface
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
     * Array mapping getters to columns - set by extending class
     *
     * @example array('getTimestamp' => 'log_timestamp', 'getDescription' => 'log_text')
     * @var array
     */
    protected $mapGettersColumns = array();

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
     * Set entity properties from array
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
     * Get entity properties as an array
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
     * Map getters to column names in table
     *
     * Getters should be used in view scripts to retrieve information instead of properties
     * which in this case would be named after database columns, which the view should not know about.
     * This method can be used by a controller plugin (eg. \ZnZend\Controller\Plugin\DataTables) to
     * work with pagination filtering/sorting params submitted from the view script together with
     * the names of the getters used for each <table> column in the view script and update the Select
     * object accordingly.
     *
     * @return array Example: array('getTimestamp' => 'log_timestamp', 'getDescription' => 'log_text')
     */
    public function mapGettersColumns()
    {
        return $this->mapGettersColumns;
    }

    /**
     * Generic internal getter for entity properties
     *
     * @param  string     $property Property to retrieve
     * @param  null|mixed $default  Optional default value if key or property does not exist
     * @return mixed
     * @internal E_USER_NOTICE is triggered if property does not exist
     */
    protected function get($property, $default = null)
    {
        if (array_key_exists($property, $this->data)) {
            return $this->data[$property];
        }

        $trace = debug_backtrace();
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
     * Retrieve record id of entity
     *
     * @return null|int
     */
    public function getId()
    {
        return (int) $this->get('id');
    }

    /**
     * Retrieve name of entity
     *
     * @return null|string
     */
    public function getName()
    {
        return $this->get('name');
    }

    /**
     * Retrieve description of entity
     *
     * @return null|string
     */
    public function getDescription()
    {
        return $this->get('description');
    }

    /**
     * Retrieve filename of thumbnail image for entity
     *
     * Typical thumbnail fits in a box of 160 x 160 pixels, usually used when
     * listing entities. Can refer to the logo of an establishment.
     *
     * @return null|string
     */
    public function getThumbnail()
    {
        return $this->get('thumbnail');
    }

    /**
     * Retrieve priority of entity
     *
     * When listing entities, smaller numbers typically come first.
     *
     * @return null|int
     */
    public function getPriority()
    {
        return (int) $this->get('priority');
    }

    /**
     * Retrieve timestamp when entity was created
     *
     * @return null|DateTime
     */
    public function getCreated()
    {
        return new DateTime($this->get('created'));
    }

    /**
     * Retrieve user who created the entity
     *
     * A simple string can be returned (eg. userid) or preferrably, an object
     * which implements EntityInterface.
     *
     * @return null|string|EntityInterface
     */
    public function getCreator()
    {
        return $this->get('creator');
    }

    /**
     * Retrieve timestamp when entity was last updated
     *
     * @return null|DateTime
     */
    public function getUpdated()
    {
        return new DateTime($this->get('updated'));
    }

    /**
     * Retrieve user who last updated the entity
     *
     * A simple string can be returned (eg. userid) or preferrably, an object
     * which implements EntityInterface.
     *
     * @return null|string|EntityInterface
     */
    public function getUpdator()
    {
        return $this->get('updator');
    }

    /**
     * Check whether entity is marked as hidden
     *
     * @return boolean
     */
    public function isHidden()
    {
        return (bool) $this->get('ishidden');
    }

    /**
     * Check whether entity is marked as deleted
     *
     * Ideally, no records should ever be deleted from the database and
     * should have a field to mark it as deleted instead.
     *
     * @return boolean
     */
    public function isDeleted()
    {
        return (bool) $this->get('isdeleted');
    }
}
