<?php
/**
 * ZnZend
 *
 * @author Zion Ng <zion@intzone.com>
 * @link   [Source] http://github.com/zionsg/ZnZend
 * @since  2012-11-09T23:00+08:00
 */
namespace ZnZend\Model;

use BadMethodCallException;
use InvalidArgumentException;

/**
 * Base class for entities corresponding to rows in database tables
 *
 * Methods from EntityInterface are implemented as examples and should
 * be overwritten by concrete classes.
 */
abstract class AbstractEntity implements EntityInterface
{
    /**
     * Array of property-value pairs for entity
     *
     * Properties map exactly to columns.
     * Use array_key_exists() instead of isset() to check if a key exists.
     * If a key exists and its value is NULL, isset() will return false.
     *
     * @var array
     */
    protected $data = array();

    /**
     * Constructor
     *
     * Takes in an optional array to populate entity
     *
     * @param array $data
     */
    public function __construct(array $data = array())
    {
        $this->setFromArray($data);
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
     * @return AbstractEntity   For fluent interface
     * @throws Exception\InvalidArgumentException Property does not exist
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
     * Typical thumbnail size is about 150 x 100 pixels, usually used when
     * listing entities. Can refer to the logo of an establishment.
     *
     * @return null|string
     */
    public function getThumbnail()
    {
        return $this->get('thumbnail');
    }

    /**
     * Retrieve filename of photo for entity
     *
     * Typical photo size is about 600 x 400 pixels or larger, usually shown
     * on the entity details page.
     *
     * @return null|string
     */
    public function getPhoto()
    {
        return $this->get('photo');
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
