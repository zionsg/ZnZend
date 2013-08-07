<?php
/**
 * ZnZend
 *
 * @author Zion Ng <zion@intzone.com>
 * @link   http://github.com/zionsg/ZnZend for canonical source repository
 */

namespace ZnZend\Db;

use Zend\Permissions\Acl\Resource\ResourceInterface;
use Zend\Stdlib\ArraySerializableInterface;

/**
 * Interface for entities corresponding to rows in database tables
 *
 * This facilitates generic treatment of entities in a Content Management System,
 * logging and generic view scripts.
 */
interface EntityInterface extends ArraySerializableInterface, ResourceInterface
{
    /**
     * Value when entity is treated as a string
     *
     * This is vital if a getter such as getUpdator() returns an EntityInterface (instead of string)
     * and it is used in log text or in a view script. Should default to getName().
     *
     * @return string
     */
    public function __toString();

    /**
     * Map getters to column names in table
     *
     * Getters are preferred over public properties as the latter would likely be named
     * after the actual database columns, which the user should not know about.
     *
     * This method can be used by a controller plugin (eg. ZnZend\Controller\Plugin\DataTables) to
     * work with pagination filtering/sorting params submitted from the view script together with
     * the names of the getters used for each <td> column in the view script and update the Select
     * object accordingly, without having to know the actual column names.
     *
     * As the array may be flipped to get the reverse mappings, both key and value should be unique
     * and must be strings.
     *
     * @example array(
     *              'getId' => 'person_id', // maps to property
     *              'getFullName' => "CONCAT(person_firstname, ' ', person_lastname)"), // maps to SQL expression
     *              'isDeleted' => '!enabled', // simple negation is allowed
     *          )
     * @return  array
     */
    public static function mapGettersColumns();

    /**
     * Get resource id for entity property
     *
     * This allows a view script to get the specific resource id for the property
     * without having to know the actual property name, which is likely to be named
     * after the database column.
     *
     * @param  string $propertyGetter Name of getter used to retrieve property
     * @return null|string Return null if property does not exist
     */
    public function getPropertyResourceId($propertyGetter);

    /**
     * Set singular noun for entity (lowercase)
     *
     * @param  string $value
     * @return EntityInterface
     */
    public function setSingularNoun($value);

    /**
     * Get singular noun for entity (lowercase)
     *
     * @example 'person'
     * @return  string
     */
    public function getSingularNoun();

    /**
     * Set plural noun for entity (lowercase)
     *
     * @param  string $value
     * @return EntityInterface
     */
    public function setPluralNoun($value);

    /**
     * Get plural noun for entity (lowercase)
     *
     * @example 'people'
     * @return  string
     */
    public function getPluralNoun();

    /**
     * Set record id
     *
     * @param  null|int $value
     * @return EntityInterface For fluent interface
     */
    public function setId($value);

    /**
     * Get record id
     *
     * @return null|int
     */
    public function getId();

    /**
     * Set name
     *
     * @param  null|string $value
     * @return EntityInterface
     */
    public function setName($value);

    /**
     * Get name
     *
     * @return null|string
     */
    public function getName();

    /**
     * Set hidden status of entity
     *
     * @param  mixed $value Value is not cast to boolean to reflect actual value in database
     * @return EntityInterface
     */
    public function setHidden($value);

    /**
     * Check whether entity is marked as hidden
     *
     * @return bool
     */
    public function isHidden();

    /**
     * Set deleted status of entity
     *
     * @param  mixed $value Value is not cast to boolean to reflect actual value in database
     * @return EntityInterface
     */
    public function setDeleted($value);

    /**
     * Check whether entity is marked as deleted
     *
     * Ideally, no records should ever be deleted from the database and
     * should have a field to mark it as deleted instead.
     *
     * @return bool
     */
    public function isDeleted();
}
