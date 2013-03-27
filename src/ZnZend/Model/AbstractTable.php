<?php
/**
 * ZnZend
 *
 * @author Zion Ng <zion@intzone.com>
 * @link   [Source] http://github.com/zionsg/ZnZend
 * @since  2013-03-27T16:00+08:00
 */
namespace ZnZend\Model;

use SplObjectStorage;
use Zend\Db\Adapter\Adapter;
use Zend\Db\ResultSet\HydratingResultSet;
use Zend\Db\ResultSet\ResultSet;
use Zend\Db\Sql\Expression;
use Zend\Db\Sql\Where;
use Zend\Db\TableGateway\AbstractTableGateway;
use Zend\Paginator\Adapter\DbSelect;
use Zend\Paginator\Paginator;
use Zend\Stdlib\Hydrator\ArraySerializable as ArraySerializableHydrator;

/**
 * Base class for table gateways
 *
 * Modifications to AbstractTableGateway:
 *   - Custom class for result set objects set via property $resultSetClass
 *   - Paginator is returned for result sets
 *   - Row state (active, deleted, all) is taken into consideration when querying
 *   - delete() only marks records as deleted and does not delete them
 *   - undelete() added
 *   - insert() and update() modified to filter out keys in user data that do not map to columns in table
 */
abstract class AbstractTable extends AbstractTableGateway
{
    /**
     * Constants for referring to row state
     */
    const ACTIVE_ROWS  = 'active';
    const DELETED_ROWS = 'deleted';
    const ALL_ROWS     = 'all';

    /**
     * Name of primary key column(s)
     *
     * @var string|array
     */
    protected $primaryKey;

    /**
     * Name of class used for result set objects
     *
     * @var string
     */
    protected $resultSetClass;

    /**
     * Column-value pair used to determine active row state
     *
     * @example array('usr_isdeleted' => 0)
     * @var     array
     */
    protected $activeRowState = array();

    /**
     * Column-value pair used to determine deleted row state
     *
     * @example array('usr_isdeleted' => 1)
     * @var     array
     */
    protected $deletedRowState = array();

    /**
     * Current row state
     *
     * @var string Options: AbstractTable::ACTIVE_ROWS (default), AbstractTable::DELETED_ROWS, AbstractTable::ALL_ROWS
     */
    protected $rowState = self::ACTIVE_ROWS;

    /**
     * Constructor
     *
     * Sets up result set prototype using custom entity class
     *
     * @param Adapter $adapter
     */
    public function __construct(Adapter $adapter)
    {
        $this->adapter = $adapter;

        $resultSetClass = $this->resultSetClass;
        $this->resultSetPrototype = new HydratingResultSet(
            new ArraySerializableHydrator(),
            new $resultSetClass()
        );
        $this->resultSetPrototype->buffer();
        $this->initialize();
    }

    /*** IMPORTANT FUNCTIONS ***/

    /**
     * Set row state
     *
     * Rows returned from query results will conform to the current specified row state
     *
     * @param  string $rowState Options: AbstractTable::ACTIVE_ROWS, AbstractTable::DELETED_ROWS, AbstractTable::ALL_ROWS
     * @return AbstractTable For fluent interface
     */
    public function setRowState($rowState)
    {
        $this->rowState = $rowState;
    }

    /**
     * Select
     *
     * Modified to take row state into consideration when querying.
     * By default, only active records are selected.
     * Use setRowState() to change behaviour before calling query function.
     *
     * As this gives the base select, any joins or expression columns should be put in here
     * so that all query functions will return the same columns.
     *
     * @param Where|\Closure|string|array $where
     * @return ResultSet
     */
    public function select($where = null)
    {
        $select = parent::select($where);

        // Any other value besides ACTIVE_ROWS and DELETED_ROWS will default to ALL_ROWS
        if (self::ACTIVE_ROWS == $this->rowState && !empty($this->activeRowState)) {
            $select->where($this->activeRowState);
        } elseif (self::DELETED_ROWS == $this->rowState && !empty($this->deletedRowState)) {
            $select->where($this->deletedRowState);
        }

        return $select;
    }

    /**
     * Return result set as Paginator for select query
     *
     * Common return point for query functions
     *
     * @param  Select $select
     * @param  bool   $fetchAll Default = true. Whether to return all rows (as Paginator)
     *                          or only the 1st row (as result set protoype).
     * @return Paginator|object
     */
    protected function getResultSet(Select $select, $fetchAll = true)
    {
        if (!$fetchAll) {
            return $select->current();
        }

        return new Paginator(
            new DbSelect($select, $this->adapter, $this->resultSetPrototype)
        );
    }

    /*** QUERY FUNCTIONS ***/

    /**
     * Fetch all rows
     *
     * @return Paginator
     */
    public function fetchAll()
    {
        $select = $this->select();
        return $this->getResultSet($select);
    }

    /**
     * Find row by primary key
     *
     * @param  string $key The value for the primary key
     * @return object
     */
    public function find($key)
    {
        $select = $this->select();
        $select->where(array($this->primaryKey => $key));
        return $this->getResultSet($select, false);
    }

    /*** CRUD OPERATIONS ***/

    /**
     * Filter out invalid columns in array
     *
     * Used for sanitizing data passed in from forms
     * Care needs to be taken for columns already prefixed with the table name
     * (so as to prevent ambiguity error when table is self-joined in select())
     *
     * @param  array $data Column-value pairs
     * @return array
     */
    public function filterColumns(array $data)
    {
        // remove invalid keys from $data
        // array_intersect() not used as keys with empty strings or boolean false are removed
        $tableCols = array_flip($this->columns);  // need to flip
        foreach ($data as $key => $value) {
            // isset() faster than array_key_exists()
            $column = str_replace("{$this->table}.", '', $key);
            if (!isset($tableCols[$column])) {
                unset($data[$key]);
            }
        }

        return $data;
    }

    /**
     * Insert
     *
     * @param  array $set
     * @return int
     */
    public function insert($set)
    {
        return parent::insert($this->filterColumns($set));
    }

    /**
     * Update
     *
     * @param  array $set
     * @param  string|array|closure $where
     * @return int
     */
    public function update($set, $where = null)
    {
        return parent::update($this->filterColumns($set), $where);
    }

    /**
     * Delete
     *
     * Modified to mark records as deleted instead of actually deleting them
     *
     * @param  Where|\Closure|string|array $where
     * @return int
     */
    public function delete($where)
    {
        return parent::update($this->deletedRowState, $where);
    }

    /**
     * Undelete
     *
     * Mark records as active
     *
     * @param  Where|\Closure|string|array $where
     * @return int
     */
    public function undelete($where)
    {
        return parent::update($this->activeRowState, $where);
    }
}
