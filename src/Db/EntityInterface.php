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
 * logging and generic view scripts. Setters return EntityInterface to provide a fluent interface.
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
     * Get modified entity properties as an array
     *
     * getArrayCopy() is used when updating an entity using a mapper or table gateway but that
     * returns the unmodified values as well. This method can be used to reduce data sent and
     * in logs to audit changes.
     * One way of keeping track would be to set a flag whenever a setter is called.
     *
     * The optional argument is to cater to Zend\Form\Form's bind() and isValid(), which
     * uses exchangeArray() to update the object, hence clearing any modified flags.
     * In this case, the original copy of the entity should be kept with a clone passed to bind()
     * and using $original->getModifiedArrayCopy($form->getData()).
     *
     * @param  array|EntityInterface $modifiedData If this is passed in, the values which are different
     *                                             from getArrayCopy() are returned
     * @return array
     */
    public function getModifiedArrayCopy($modifiedData = null);

    /**
     * Map getters to columns in table
     *
     * Getters are preferred over public properties as the latter would likely be named
     * after the actual database columns, which the user should not know about.
     *
     * This method can be used by a controller plugin (eg. ZnZend\Controller\Plugin\DataTables) to
     * work with pagination filtering/sorting params submitted from the view script together with
     * the names of the getters used for each <td> column in the view script and update the Select
     * object accordingly, without having to know the actual column names.
     *
     * The mapped columns will be used in the WHERE and ORDER BY clauses of an SQL SELECT statement,
     * hence the provision for SQL expressions.
     *
     * @example array(
     *              'getId'       => 'person_id', // maps directly to column
     *              'getFullName' => "CONCAT(person_firstname, ' ', person_lastname)", // maps to SQL expression
     *          )
     * @return  array
     */
    public static function mapGettersColumns();

    /**
     * Get name of getter mapped to property
     *
     * Scenario: Property name of credit card number retrieved from $element->getName() in the form,
     * but user only has permission to view part of the number. The form view helper has no way
     * of knowing how to return the value. The getter allows a boolean flag to mask or unmask
     * the value but the getter name is not known, hence this function.
     *
     * @param  string $property Name of property
     * @return null|string Return null if getter not found
     */
    public function getPropertyGetter($property);

    /**
     * Get resource id for entity property
     *
     * If the name of the property is unknown, it may be retrieved indirectly via
     * $element->getName() in the form provided the form fields are named after the properties.
     *
     * @param  string $property Name of property
     * @return null|string Return null if property does not exist
     */
    public function getPropertyResourceId($property);

    /**
     * Store authenticated identity (current logged in user)
     *
     * This is to facilitate populating of auditing columns such as the user who
     * created or last modified the record.
     *
     * @param  string $identity
     * @return EntityInterface
     */
    public function setAuthIdentity($identity);

    /**
     * Retrieve stored authenticated identity
     *
     * @return string
     */
    public function getAuthIdentity();

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
     * @return EntityInterface
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
     * Check whether entity is marked as deleted
     *
     * Ideally, no records should ever be deleted from the database and
     * should have a field to mark it as deleted instead.
     *
     * @return bool
     */
    public function isDeleted();
}
