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
 * Base class for entities
 */
abstract class AbstractEntity
{
    /**
     * Array of property-value pairs for entity
     *
     * Properties MUST include $prefix as they may be mapping to columns
     * in a database table and it would be too cumbersome to remove any prefix
     *
     * Use array_key_exists() instead of isset() to check if a key exists
     * If a key exists and its value is NULL, isset() will return false
     *
     * @var array
     */
    protected $data = array();

    /**
     * Common prefix for properties (used internally)
     *
     * Entities may model rows in a database table where columns are prefixed
     * with the table name for unique naming. The prefix should not be shown
     * in public properties, methods, errors or exceptions.
     *
     * Eg: $entity->id and NOT $entity->adm_id,
     *     $entity->getId() and NOT $entity->getAdm_Id()
     *
     * @var string
     */
    protected $prefix = '';

    /**
     * Constructor
     *
     * Takes in an optional array to populate entity
     *
     * @param array $data
     */
    public function __construct(array $data = null)
    {
        $this->setFromArray($data);
    }

    /**
     * Magic method to get or set properties
     *
     * This is only for convenience and fallback. Explicit getters and setters
     * should be coded for each property for reflection and performance reasons
     *
     * Assuming $prefix is 'adm_', calling $entity->getDisplayName() will return
     * the property 'adm_displayName' while $entity->setDisplayName() will set its value
     *
     * @param  string $methodName       'getProperty' or 'setProperty'. Property
     *                                  must NOT have $prefix added
     * @param  array  $arguments        Exactly 1 argument for 'set' methods.
     *                                  Ignored for 'get' methods
     * @throws InvalidArgumentException Thrown if setProperty() is not called with
     *                                  exactly one argument, which is the value to set
     * @throws BadMethodCallException   Thrown if method name does not start with 'get' or 'set'
     */
    public function __call($methodName, $arguments)
    {
        $type = substr($methodName, 0, 3);
        $property = strtolower(substr($methodName, 3, 1)) . substr($methodName, 4);

        if ($type == 'get') {
            return $this->get($property);
        } elseif ($type == 'set') {
            if (!is_array($arguments) || count($arguments) != 1) {
                throw new InvalidArgumentException(
                    "Method {$methodName} must be called with exactly one argument"
                );
            }
            return $this->set($property, $arguments[0]);
        } else {
            throw new BadMethodCallException("Method {$methodName} does not exist");
        }
    }

    /**
     * Generic internal getter for properties
     *
     * @internal E_USER_NOTICE is triggered if the property does not exist
     * @param    string $property Name of property WITHOUT $prefix
     * @return   mixed|null NULL is returned if property does not exist
     */
    protected function get($property)
    {
        $actualProperty = $this->prefix . $property;
        if (array_key_exists($actualProperty, $this->data)) {
            return $this->data[$actualProperty];
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
        return null;
    }

    /**
     * Generic internal setter for properties
     *
     * @param  string $property Name of property WITHOUT $prefix
     * @param  mixed  $value    Value to set
     * @return AbstractEntity For fluent interface
     * @throws InvalidArgumentException Thrown if property does not exist
     */
    protected function set($property, $value)
    {
        $actualProperty = $this->prefix . $property;
        if (!array_key_exists($actualProperty, $this->data)) {
            throw new InvalidArgumentException("Property {$property} does not exist");
        } else {
            $this->data[$actualProperty] = $value;
            return $this;
        }
    }

    /**
     * Populate entity from array
     *
     * Only values whose keys exist in the entity are stored
     *
     * @param  array   $data
     * @param  boolean $usePrefix DEFAULT=false. Whether to add prefix to keys
     *                            in $data for checking and setting
     * @return AbstractEntity For fluent interface
     */
    public function setFromArray(array $data = null, $usePrefix = false)
    {
        $prefix = $usePrefix ? $this->prefix : '';
        foreach (($data ?: array()) as $key => $value) {
            if (array_key_exists($prefix . $key, $this->data)) {
                $this->data[$prefix. $key] = $value;
            }
        }

        return $this;
    }

    /**
     * Return entity properties as an array
     *
     * @return array
     */
    public function toArray()
    {
        return $this->data;
    }

}
