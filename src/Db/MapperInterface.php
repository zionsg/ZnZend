<?php
/**
 * ZnZend
 *
 * @link https://github.com/zionsg/ZnZend for canonical source repository
 */

namespace ZnZend\Db;

use Zend\Paginator\Paginator;
use ZnZend\Db\EntityInterface;

/**
 * Interface for entity mappers corresponding to database tables
 *
 * Some methods are brought in from \Zend\Db\TableGatewayInterface to ensure availability
 * while reducing bloat at the same time. Setters return MapperInterface to provide fluent interface.
 */
interface MapperInterface
{
    /**
     * Constants for referring to row state
     *
     * Generally it is not a good idea to have constants in interfaces as values have
     * to be set and cannot be overridden in implementing classes.
     * These are placed here to enforce availability in implementing classes, especially
     * for use in setRowState().
     */
    const ACTIVE_ROWS  = 'active';
    const DELETED_ROWS = 'deleted';
    const ALL_ROWS     = 'all';

    /**
     * Populate mapper with records from non-database source
     *
     * This is used when the mapper is not linked to a database table,
     * eg. a UserMapper which reads from a config file (containing an array of user records)
     * rather than querying a database, after which all query functions will query
     * this set of records.
     *
     * @param  array $records
     * @return MapperInterface
     */
    public function setRecords($records);

    /**
     * Get available row states as value-option pairs which can be used to populate a dropdown list
     *
     * @return array
     */
    public static function getRowStates();

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
     * @param  mixed $rowState Options: MapperInterface::ACTIVE_ROWS, MapperInterface::DELETED_ROWS,
     *                         MapperInterface::ALL_ROWS
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
     * @return int|bool Affected rows. Return false if row state not supported
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
     * @return int|bool Affected rows. Return false if row state not supported
     */
    public function markDeleted($where);

    /**
     * Fetch row by primary key
     *
     * @param  mixed $key The value for the primary key
     * @return null|EntityInterface
     */
    public function fetch($key);

    /**
     * Fetch all rows
     *
     * @return null|Paginator
     */
    public function fetchAll();

    /**
     * Fetch rows where the primary key matches a list of values
     *
     * @param  array       $values
     * @param  null|string $column Optional column to use instead of primary key column
     * @return null|Paginator
     */
    public function fetchIn($values, $column = null);

    /**
     * Create
     *
     * @param  array|EntityInterface $set
     * @return null|EntityInterface Return null if unable to create
     */
    public function create($set);

    /**
     * Update
     *
     * If an entity is passed in for $where OR an entity is passed with null $where,
     * it is assumed that the update is for that entity. This is useful, eg. in the controller,
     * where the user does not and should not know the column name or how to
     * construct a where clause.
     *
     * @param  array|EntityInterface $set
     * @param  null|string|array|closure|EntityInterface $where
     * @return int No. of affected rows. Not practical to return EntityInterface
     *             as the update could be for multiple rows.
     */
    public function update($set, $where = null);
}
