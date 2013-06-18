<?php
/**
 * ZnZend
 *
 * @author Zion Ng <zion@intzone.com>
 * @link   http://github.com/zionsg/ZnZend for canonical source repository
 */

namespace ZnZend\Model;

/**
 * Interface for entities corresponding to rows in database tables
 */
interface EntityInterface
{
    /**
     * Map getters to column names in table
     *
     * Getters should be used in view scripts to retrieve information instead of properties
     * which in this case would be named after the database columns, which the view should not know about.
     * This method can be used by a controller plugin (eg. \ZnZend\Controller\Plugin\DataTables) to
     * work with pagination filtering/sorting params submitted from the view script together with
     * the names of the getters used for each <table> column in the view script and update the Select
     * object accordingly.
     *
     * @return array Example: array('getTimestamp' => 'log_timestamp', 'getDescription' => 'log_text')
     */
    public static function mapGettersColumns();

    /**
     * Retrieve record id of entity
     *
     * @return null|int
     */
    public function getId();

    /**
     * Retrieve name of entity
     *
     * @return null|string
     */
    public function getName();

    /**
     * Retrieve description of entity
     *
     * @return null|string
     */
    public function getDescription();

    /**
     * Retrieve filename of thumbnail image for entity
     *
     * Typical thumbnail fits in a box of 160 x 160 pixels, usually used when
     * listing entities. Can refer to the logo of an establishment.
     *
     * @return null|string
     */
    public function getThumbnail();

    /**
     * Retrieve priority of entity
     *
     * When listing entities, smaller numbers typically come first.
     *
     * @return null|int
     */
    public function getPriority();

    /**
     * Retrieve timestamp when entity was created
     *
     * @return null|DateTime
     */
    public function getCreated();

    /**
     * Retrieve user who created the entity
     *
     * A simple string can be returned (eg. userid) or preferrably, an object
     * which implements EntityInterface.
     *
     * @return null|string|EntityInterface
     */
    public function getCreator();

    /**
     * Retrieve timestamp when entity was last updated
     *
     * @return null|DateTime
     */
    public function getUpdated();

    /**
     * Retrieve user who last updated the entity
     *
     * A simple string can be returned (eg. userid) or preferrably, an object
     * which implements EntityInterface.
     *
     * @return null|string|EntityInterface
     */
    public function getUpdator();

    /**
     * Check whether entity is marked as hidden
     *
     * @return boolean
     */
    public function isHidden();

    /**
     * Check whether entity is marked as deleted
     *
     * Ideally, no records should ever be deleted from the database and
     * should have a field to mark it as deleted instead.
     *
     * @return boolean
     */
    public function isDeleted();
}
