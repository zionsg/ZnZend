<?php
/**
 * ZnZend
 *
 * @author Zion Ng <zion@intzone.com>
 * @link   http://github.com/zionsg/ZnZend for canonical source repository
 */

namespace ZnZend\Db;

/**
 * Interface for entities corresponding to rows in database tables
 */
interface EntityInterface
{
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
     * @example array('getId' => 'person_id', 'getFullName' => "CONCAT(person_firstname, ' ', person_lastname)")
     * @return  array
     */
    public static function mapGettersColumns();

    /**
     * Value when entity is treated as a string
     *
     * This is vital if a getter such as getCreator() returns an EntityInterface (instead of string)
     * and it is used in log text or in a view script. Should default to getName().
     *
     * @return string
     */
    public function __toString();

    /**
     * Get record id
     *
     * @return null|int
     */
    public function getId();

    /**
     * Set record id
     *
     * @param  null|int $value
     * @return EntityInterface For fluent interface
     */
    public function setId($value);

    /**
     * Get name
     *
     * @return null|string
     */
    public function getName();

    /**
     * Set name
     *
     * @param  null|string $value
     * @return EntityInterface
     */
    public function setName($value);

    /**
     * Get description
     *
     * @return null|string
     */
    public function getDescription();

    /**
     * Set description
     *
     * @param  null|string $value
     * @return EntityInterface
     */
    public function setDescription($value);

    /**
     * Get filename of thumbnail image for entity
     *
     * Typical thumbnail fits in a box of 160 x 160 pixels, usually used when
     * listing entities. Can refer to the logo of an establishment.
     *
     * @return null|string
     */
    public function getThumbnail();

    /**
     * Set filename of thumbnail image for entity
     *
     * @param  null|string $value
     * @return EntityInterface
     */
    public function setThumbnail($value);

    /**
     * Get priority
     *
     * When listing entities, smaller numbers typically come first.
     *
     * @return null|int
     */
    public function getPriority();

    /**
     * Set priority
     *
     * When listing entities, smaller numbers typically come first.
     *
     * @param  null|int $value
     * @return EntityInterface
     */
    public function setPriority($value);

    /**
     * Get timestamp when entity was created
     *
     * Return null if value is default DATETIME value of '0000-00-00 00:00:00' in SQL.
     *
     * @return null|DateTime
     */
    public function getCreated();

    /**
     * Set timestamp when entity was created
     *
     * Set to null if value is default DATETIME value of '0000-00-00 00:00:00' in SQL.
     *
     * @param  null|string|DateTime $value String must be parsable by DateTime
     * @return EntityInterface
     */
    public function setCreated($value);

    /**
     * Get user who created the entity
     *
     * A simple string can be returned (eg. userid) or preferrably, an object
     * which implements EntityInterface.
     *
     * @return null|string|EntityInterface
     */
    public function getCreator();

    /**
     * Set user who created the entity
     *
     * @param  null|string|EntityInterface $value
     * @return EntityInterface
     */
    public function setCreator($value);

    /**
     * Get timestamp when entity was last updated
     *
     * Return null if value is default DATETIME value of '0000-00-00 00:00:00' in SQL.
     *
     * @return null|DateTime
     */
    public function getUpdated();

    /**
     * Set timestamp when entity was last updated
     *
     * Set to null if value is default DATETIME value of '0000-00-00 00:00:00' in SQL.
     *
     * @param  null|string|DateTime $value String must be parsable by DateTime
     * @return EntityInterface
     */
    public function setUpdated($value);

    /**
     * Get user who last updated the entity
     *
     * A simple string can be returned (eg. userid) or preferrably, an object
     * which implements EntityInterface.
     *
     * @return null|string|EntityInterface
     */
    public function getUpdator();

    /**
     * Set user who last updated the entity
     *
     * @param  null|string|EntityInterface $value
     * @return EntityInterface
     */
    public function setUpdator($value);

    /**
     * Check whether entity is marked as hidden
     *
     * @return bool
     */
    public function isHidden();

    /**
     * Set hidden status of entity
     *
     * @param  bool $value
     * @return EntityInterface
     */
    public function setHidden($value);

    /**
     * Check whether entity is marked as deleted
     *
     * Ideally, no records should ever be deleted from the database and
     * should have a field to mark it as deleted instead.
     *
     * @return bool
     */
    public function isDeleted();

    /**
     * Set deleted status of entity
     *
     * @param  bool $value
     * @return EntityInterface
     */
    public function setDeleted($value);
}
