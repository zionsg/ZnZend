<?php
/**
 * ZnZend
 *
 * @author Zion Ng <zion@intzone.com>
 * @link   http://github.com/zionsg/ZnZend for canonical source repository
 */

namespace ZnZend\Db;

use Zend\Db\TableGateway\TableGatewayInterface;
use Zend\Paginator\Paginator;

/**
 * Interface for entity mappers corresponding to database tables
 */
interface MapperInterface extends TableGatewayInterface
{
    /**
     * Check whether the mapper and its entity support row states (active, deleted, all)
     *
     * Ideally, no records should ever be deleted from the database and should have
     * a field to mark it as deleted instead - this is what row state refers to.
     *
     * @return bool
     */
    public function hasRowState();

    /**
     * Set row state
     *
     * Rows returned from query results will conform to the current specified row state
     *
     * @param  mixed $rowState Flags to indicate 1 of 3 states: active, deleted, all
     * @return MapperInterface
     */
    public function setRowState($rowState);

    /**
     * Mark records as active
     *
     * If an entity is passed in for $where, it is assumed that the
     * entity is to be marked as active. This is useful, eg. in the controller,
     * where the user does not and should not know the column name or how to
     * construct a where clause.
     *
     * @param  string|array|closure|EntityInterface $where
     * @return bool|int Return false if row state not supported
     */
    public function markActive($where);

    /**
     * Mark records as deleted
     *
     * If an entity is passed in for $where, it is assumed that the
     * entity is to be marked as deleted. This is useful, eg. in the controller,
     * where the user does not and should not know the column name or how to
     * construct a where clause.
     *
     * @param  string|array|closure|EntityInterface $where
     * @return bool|int Return false if row state not supported
     */
    public function markDeleted($where);

    /**
     * Fetch all rows
     *
     * @return Paginator
     */
    public function fetchAll();

    /**
     * Find row by primary key
     *
     * @param  mixed $key The value for the primary key
     * @return EntityInterface
     */
    public function find($key);
}
