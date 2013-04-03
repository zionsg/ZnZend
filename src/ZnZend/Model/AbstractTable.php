<?php
/**
 * ZnZend
 *
 * @author Zion Ng <zion@intzone.com>
 * @link   [Source] http://github.com/zionsg/ZnZend
 */
namespace ZnZend\Model;

use Zend\Db\ResultSet\HydratingResultSet;
use Zend\Db\Sql\Select;
use Zend\Db\TableGateway\AbstractTableGateway;
use Zend\Db\TableGateway\Feature;
use Zend\Paginator\Adapter\DbSelect;
use Zend\Paginator\Paginator;
use Zend\Stdlib\Hydrator\ArraySerializable as ArraySerializableHydrator;

/**
 * Base class for table gateways
 *
 * Properties that must be set: $table, $resultSetClass
 * Properties that should be set if applicable: $activeRowState, $deletedRowState
 *
 * Modifications to AbstractTableGateway:
 *   - Ability to use global/static db adapter
 *   - Column and primary key information populated during instantiation
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
     * Name of primary key column(s) - set by constructor not user
     *
     * @var string|array
     */
    protected $primaryKey;

    /**
     * Fully qualified name of class used for result set objects - set by user
     *
     * @example \Application\Model\User
     * @var string
     */
    protected $resultSetClass;

    /**
     * Column-value pair used to determine active row state - set by user
     *
     * @example array('usr_isdeleted' => 0)
     * @var     array
     */
    protected $activeRowState = array();

    /**
     * Column-value pair used to determine deleted row state - set by user
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
     * Add ability to use a global/static adapter without having to inject it into a TableGateway instance.
     *   Just add the following line to a bootstrap, eg. onBootstrap() in Module.php:
     *       Zend\Db\TableGateway\Feature\GlobalAdapterFeature::setStaticAdapter($adapter);
     *   The adapter is statically loaded when instantiating in a controller/model, eg: $table = new MyTableGateway();
     *
     * Populate TableGateway with column and primary key information.
     *   Without this, $this->columns will be empty and cannot be used for filterColumns().
     *   Feature\MetadataFeature is not used as it is very slow.
     */
    public function __construct()
    {
        // Add ability to use global/static adapter
        $this->featureSet = new Feature\FeatureSet();
        $this->featureSet->addFeature(new Feature\GlobalAdapterFeature());

        // Set result set prototype
        $resultSetClass = $this->resultSetClass;
        $this->resultSetPrototype = new HydratingResultSet(
            new ArraySerializableHydrator(),
            new $resultSetClass()
        );
        $this->resultSetPrototype->buffer();

        // Initialize
        $this->initialize();

        // Populate $columns - adapter is only available after initialize()
        $columns = $this->adapter->query(
            'SELECT COLUMN_NAME, COLUMN_KEY FROM information_schema.columns WHERE table_schema = ? and table_name = ?',
            array($this->adapter->getCurrentSchema(), $this->table)
        );
        $keys = array();
        foreach ($columns as $column) {
            $this->columns[] = $column->COLUMN_NAME;
            if ('PRI' == $column->COLUMN_KEY) {
                $keys[] = $column->COLUMN_NAME;
            }
        }
        $this->primaryKey = (1 == count($keys)) ? $keys[0] : $keys;
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
        return $this;
    }

    /*
     * Get base Select with from, joins, columns added
     *
     * All other queries should build upon this so that the columns selected and joins are standardised
     * Extending classes may override this with their own implementation as this only
     * provides the most basic 'SELECT * FROM table'.
     *
     * By default, only active records are selected.
     * Use setRowState() to change behaviour before calling query function.
     *
     * @return Select
     */
    public function getBaseSelect()
    {
        $select = $this->sql->select();

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
     * The returned Paginator is set to page 1 with item count set to PHP_INT_MAX
     * so that the full result set is returned when iterated over.
     *
     * @param  Select $select
     * @param  bool   $fetchAll Default = true. Whether to return all rows (as Paginator)
     *                          or only the 1st row (as result set protoype).
     * @return Paginator|object
     */
    protected function getResultSet(Select $select, $fetchAll = true)
    {
        if (!$fetchAll) {
            return $this->executeSelect($select)->current();
        }

        $paginator = new Paginator(
            new DbSelect($select, $this->adapter, $this->resultSetPrototype)
        );
        $paginator->setItemCountPerPage(PHP_INT_MAX)->setCurrentPageNumber(1);
        return $paginator;
    }

    /*** QUERY FUNCTIONS ***/

    /**
     * Fetch all rows
     *
     * @return Paginator
     */
    public function fetchAll()
    {
        $select = $this->getBaseSelect();
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
        $select = $this->getBaseSelect();
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
