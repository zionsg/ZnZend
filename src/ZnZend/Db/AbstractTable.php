<?php
/**
 * ZnZend
 *
 * @author Zion Ng <zion@intzone.com>
 * @link   http://github.com/zionsg/ZnZend for canonical source repository
 */

namespace ZnZend\Db;

use ReflectionClass;
use Zend\Db\ResultSet\HydratingResultSet;
use Zend\Db\ResultSet\ResultSetInterface;
use Zend\Db\Sql\Select;
use Zend\Db\Sql\Where;
use Zend\Db\TableGateway\AbstractTableGateway;
use Zend\Db\TableGateway\Feature;
use Zend\Paginator\Paginator;
use Zend\Stdlib\ArraySerializableInterface;
use Zend\Stdlib\Hydrator\ArraySerializable as ArraySerializableHydrator;
use ZnZend\Db\EntityInterface;
use ZnZend\Db\Exception;
use ZnZend\Paginator\Adapter\DbSelect;

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
     * Fully qualified name of class used for result set objects - set by user
     *
     * Class must implement EntityInterface.
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
     * Name of primary key column(s) - set by find() not user
     *
     * @var string|array
     */
    protected $primaryKey;

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
     */
    public function __construct()
    {
        // Add ability to use global/static adapter
        $this->featureSet = new Feature\FeatureSet();
        $this->featureSet->addFeature(new Feature\GlobalAdapterFeature());

        // Set result set prototype
        $this->resultSetPrototype = $this->getResultSetPrototype();
        $this->resultSetPrototype->buffer();

        // Initialize
        $this->initialize();
    }

    /*** IMPORTANT FUNCTIONS ***/

    /**
     * Set row state
     *
     * Rows returned from query results will conform to the current specified row state
     *
     * @param  string $rowState Options: AbstractTable::ACTIVE_ROWS, AbstractTable::DELETED_ROWS,
     *                          AbstractTable::ALL_ROWS
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
     * Extending classes should call parent::getBaseSelect() and add on to the returned Select
     * as this takes into account the row state.
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
     * All query functions should use this as a common return point, even extending classes.
     * The returned Paginator is set to page 1 with item count set to -1 so that the full result
     * is returned by default when iterated over without use of pagination.
     *
     * @param  Select $select
     * @param  bool   $fetchAll Default = true. Whether to return all rows (as Paginator)
     *                          or only the 1st row (as result set protoype).
     * @param  ResultSetInterface $resultSetPrototype Optional alternate result set prototype to use.
     * @return Paginator|object
     */
    protected function getResultSet(Select $select, $fetchAll = true, ResultSetInterface $resultSetPrototype = null)
    {
        if (false === $fetchAll) { // $fetchAll defaults to true if null, hence ===
            return $this->executeSelect($select)->current();
        }

        if (null === $resultSetPrototype) {
            $resultSetPrototype = $this->getResultSetPrototype();
        }

        $paginator = new Paginator(
            new DbSelect($select, $this->adapter, $resultSetPrototype)
        );
        $paginator->setItemCountPerPage(-1)->setCurrentPageNumber(1);

        return $paginator;
    }

    /**
     * Defined in AbstractTableGateway; Get select result prototype
     *
     * Method signature modified to create ad hoc result set prototype different from $resultSetClass.
     * Useful when returning result sets from junction tables which fall under composite entities.
     *
     * Example: A CompanyTable returns result sets of Company entities. findMapCompanyEmployee()
     * which returns records mapping all companies to all employees should return CompanyEmployee entities,
     * which in turn should have getCompany() and getEmployee() methods. As such, findMapCompanyEmployee()
     * should call $this->getResultSet($select, null, $this->getResultSetPrototype('CompanyEmployee')).
     *
     * @used-by AbstractTable::getResultSet()
     * @param  string $resultSetClass Fully qualified name of class to be used for result set prototype.
     *                                Class must implement EntityInterface.
     * @throws Exception\InvalidArgumentException If class does not implement EntityInterface.
     * @return ResultSet
     */
    public function getResultSetPrototype($resultSetClass = '')
    {
        if (empty($resultSetClass)) {
            $resultSetClass = $this->resultSetClass;
        }

        if (   $resultSetClass == $this->resultSetClass
            && $this->resultSetPrototype instanceof ResultSetInterface
        ) {
            return $this->resultSetPrototype;
        }

        // Create prototype
        $resultSetInstance = new $resultSetClass();
        if (!$resultSetInstance instanceof EntityInterface) {
            throw new Exception\InvalidArgumentException('Result set class does not implement EntityInterface');
        }
        $resultSetPrototype = new HydratingResultSet(
            new ArraySerializableHydrator(),
            $resultSetInstance
        );

        // Ad hoc prototype
        if ($resultSetClass != $this->resultSetClass) {
            return $resultSetPrototype;
        }

        $this->resultSetPrototype = $resultSetPrototype;

        return $this->resultSetPrototype;
    }

    /**
     * Get list of class constants which can be used to populate a dropdown list
     *
     * @return array
     */
    public static function getConstants()
    {
        $reflection = new ReflectionClass(__CLASS__);
        return $reflection->getConstants();
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
        $select->where(array($this->getPrimaryKey() => $key));
        return $this->getResultSet($select, false);
    }

    /**
     * Get columns
     *
     * @return array
     */
    public function getColumns()
    {
        // Feature\MetadataFeature is not used as it is very slow
        if (empty($this->columns)) {
            $columns = $this->adapter->query(
                'SELECT column_name FROM information_schema.columns '
                . 'WHERE table_schema = ? AND table_name = ?',
                array($this->adapter->getCurrentSchema(), $this->table)
            );
            $this->columns = array();
            foreach ($columns as $column) {
                $this->columns[] = $column->column_name;
            }
        }

        return $this->columns;
    }

    /**
     * Get primary key
     *
     * @return string|array
     */
    public function getPrimaryKey()
    {
        if (empty($this->primaryKey)) {
            $columns = $this->adapter->query(
                'SELECT column_name FROM information_schema.columns '
                . "WHERE table_schema = ? AND table_name = ? AND column_key = 'PRI'",
                array($this->adapter->getCurrentSchema(), $this->table)
            );
            $keys = array();
            foreach ($columns as $column) {
                $keys[] = $column->column_name;
            }
            $this->primaryKey = (1 == count($keys)) ? $keys[0] : $keys;
        }

        return $this->primaryKey;
    }

    /*** CRUD OPERATIONS ***/

    /**
     * Filter out invalid columns in array
     *
     * Used for sanitizing data passed in from forms
     * Care needs to be taken for columns already prefixed with the table name
     * (so as to prevent ambiguity error when table is self-joined in select())
     *
     * @param  array|ArraySerializableInterface $data Column-value pairs
     * @throws Exception\InvalidArgumentException
     * @return array
     */
    public function filterColumns($data)
    {
        if ($data instanceof ArraySerializableInterface) {
            $data = $data->getArrayCopy();
        }

        if (!is_array($data)) {
            throw new Exception\InvalidArgumentException(
                'Array or object implementing Zend\Stdlib\ArraySerializableInterface expected'
            );
        }

        if (empty($data)) {
            return $data;
        }

        // remove invalid keys from $data
        // array_intersect() not used as keys with empty strings or boolean false are removed
        $tableCols = array_flip($this->getColumns());  // need to flip
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

    /**
     * Insert a new row and update if a duplicate record exists
     *
     * This is an implementation of "INSERT ... ON DUPLICATE KEY UPDATE" in MySQL.
     * The equivalent from SQL-99, "MERGE INTO ...", is not supported in MySQL.
     *
     * @param  array $set
     * @return int
     */
    public function insertOnDuplicate($set)
    {
        $set = $this->filterColumns($set);

        $dbAdapter = $this->adapter;
        $qi = function ($name) use ($dbAdapter) { return $dbAdapter->platform->quoteIdentifier($name); };
        $fp = function ($name) use ($dbAdapter) { return $dbAdapter->driver->formatParameterName($name); };

        $keys = array_keys($set);
        $columns = array_map($qi, $keys);
        $parameters = array_map($fp, array_values($keys));

        $primaryKey = $this->getPrimaryKey();
        $updateValues = array();
        foreach ($keys as $index => $key) {
            $column = $columns[$index];
            if ($primaryKey == $key) {
                $updateValues[] = $column . ' = LAST_INSERT_ID(' . $column . ')';
                continue;
            }

            $updateValues[] = $column . ' = VALUES(' . $column . ')';
        }

        $sql = sprintf(
            'INSERT INTO %s (%s) VALUES (%s) ON DUPLICATE KEY UPDATE %s',
            $qi($this->table),
            implode(',', $columns),
            implode(',', $parameters),
            implode(',', $updateValues)
        );
        $result = $dbAdapter->query($sql, $set);

        return (int) $result->getGeneratedValue();
    }
}
