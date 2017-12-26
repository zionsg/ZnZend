<?php
/**
 * ZnZend
 *
 * @link https://github.com/zionsg/ZnZend for canonical source repository
 */

namespace ZnZend\Db;

use Zend\Db\ResultSet\HydratingResultSet;
use Zend\Db\ResultSet\ResultSetInterface;
use Zend\Db\Sql\Expression;
use Zend\Db\Sql\Select;
use Zend\Db\Sql\Where;
use Zend\Db\TableGateway\AbstractTableGateway;
use Zend\Db\TableGateway\Feature;
use Zend\Paginator\Paginator;
use Zend\Stdlib\ArraySerializableInterface;
use Zend\Stdlib\Hydrator\ArraySerializable as ArraySerializableHydrator;
use ZnZend\Db\EntityInterface;
use ZnZend\Db\Exception;
use ZnZend\Db\MapperInterface;
use ZnZend\Paginator\Adapter\DbSelect;

/**
 * Base class for mappers / table gateways
 *
 * Properties that must be set: $table, $resultSetClass
 * Properties that should be set if applicable: $primaryKey, $activeRowState, $deletedRowState
 *
 * Modifications to AbstractTableGateway:
 *   - Ability to use global/static db adapter
 *   - Custom class for result set objects set via property $resultSetClass
 *   - Paginator is returned for result sets
 *   - Row state (active, deleted, all) is taken into consideration when querying
 *   - markActive() and markDeleted() added for marking records
 *   - insert() and update() modified to filter out keys in user data that do not map to columns in table
 *   - allows populating of records from non-database source
 */
abstract class AbstractMapper extends AbstractTableGateway implements MapperInterface
{
    /**
     * Records populated via non-database source
     *
     * @var array
     */
    protected $records = [];

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
     * Name of primary key column(s) - set by user or populated when needed
     *
     * @var string|array
     */
    protected $primaryKey;

    /**
     * Column-value pair used to determine active row state - set by user
     *
     * @example array('usr_isdeleted' => 0)
     * @var     array
     */
    protected $activeRowState = [];

    /**
     * Column-value pair used to determine deleted row state - set by user
     *
     * @example array('usr_isdeleted' => 1)
     * @var     array
     */
    protected $deletedRowState = [];

    /**
     * Current row state
     *
     * @var string Options: AbstractMapper::ACTIVE_ROWS (default),
     *             AbstractMapper::DELETED_ROWS, AbstractMapper::ALL_ROWS
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
        $this->getFeatureSet()->addFeature(new Feature\GlobalAdapterFeature());

        // Set result set prototype
        $this->resultSetPrototype = $this->getResultSetPrototype();
        $this->resultSetPrototype->buffer();

        // Initialize
        $this->initialize();
    }

    /*** IMPORTANT FUNCTIONS ***/

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

        if (! $this->hasRowState()) {
            return $select;
        }

        // Any other value besides ACTIVE_ROWS and DELETED_ROWS will default to ALL_ROWS
        if (self::ACTIVE_ROWS == $this->rowState && ! empty($this->activeRowState)) {
            $select->where($this->activeRowState);
        } elseif (self::DELETED_ROWS == $this->rowState && ! empty($this->deletedRowState)) {
            $select->where($this->deletedRowState);
        }

        return $select;
    }

    /**
     * Return result set as Paginator or ResultSetInterface for select query
     *
     * All query functions should use this as a common return point, even extending classes.
     * The returned Paginator is set to page 1 with item count set to -1 so that the full result
     * is returned by default when iterated over without use of pagination.
     *
     * @param  Select $select
     * @param  bool   $fetchAll Default = true. Whether to return all rows (as Paginator using ResultSetInterface)
     *                          or only the 1st row (as ResultSetInterface).
     * @param  ResultSetInterface $resultSetPrototype Optional alternate result set prototype to use.
     * @return null|Paginator|ResultSetInterface
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

    /*** ADDITIONAL PUBLIC FUNCTIONS ***/

    /**
     * Insert a new row and update if a duplicate record exists
     *
     * This is an implementation of "INSERT ... ON DUPLICATE KEY UPDATE" in MySQL.
     * The equivalent from SQL-99, "MERGE INTO ...", is not supported in MySQL.
     *
     * @param  array|EntityInterface $set
     * @param  array $updateSet Optional column-value pairs to use for update instead of insert values from $set
     * @return int
     */
    public function insertOnDuplicate($set, array $updateSet = [])
    {
        $set = $this->filterColumns($set);

        $dbAdapter = $this->adapter;
        $qi = function ($name) use ($dbAdapter) {
            return $dbAdapter->platform->quoteIdentifier($name);
        };
        $fp = function ($name) use ($dbAdapter) {
            return $dbAdapter->driver->formatParameterName($name);
        };

        $columns = array_keys($set);
        $quotedColumns = array_map($qi, $columns);
        $parameters = array_map($fp, array_values($columns));

        $primaryKey = $this->getPrimaryKey();
        $updateValues = [];
        foreach ($columns as $index => $column) {
            $quotedColumn = $quotedColumns[$index];
            if ($primaryKey == $column) {
                $updateValues[] = $quotedColumn . ' = LAST_INSERT_ID(' . $quotedColumn . ')';
                continue;
            }

            $value = (array_key_exists($column, $updateSet) ? $updateSet[$column] : "VALUES({$quotedColumn})");
            $updateValues[] = $quotedColumn . ' = ' . $value;
        }

        $sql = sprintf(
            'INSERT INTO %s (%s) VALUES (%s) ON DUPLICATE KEY UPDATE %s',
            $qi($this->table),
            implode(',', $quotedColumns),
            implode(',', $parameters),
            implode(',', $updateValues)
        );
        $result = $dbAdapter->query($sql, $set);

        return (int) $result->getGeneratedValue();
    }

    /*** AUXILIARY FUNCTIONS ***/

    /**
     * Get primary key column(s)
     *
     * @return string|array
     */
    protected function getPrimaryKey()
    {
        if (empty($this->primaryKey)) {
            $columns = $this->adapter->query(
                'SELECT column_name FROM information_schema.columns '
                . "WHERE table_schema = ? AND table_name = ? AND column_key = 'PRI'",
                [$this->adapter->getCurrentSchema(), $this->table]
            );
            $keys = [];
            foreach ($columns as $column) {
                $keys[] = $column->column_name;
            }
            $this->primaryKey = (1 == count($keys)) ? $keys[0] : $keys;
        }

        return $this->primaryKey;
    }

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
    protected function filterColumns($data)
    {
        if ($data instanceof ArraySerializableInterface) {
            $data = $data->getArrayCopy();
        }

        if (! is_array($data)) {
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
            // isset() faster than in_[] and array_key_exists()
            $column = str_replace("{$this->table}.", '', $key);
            if (! isset($tableCols[$column])) {
                unset($data[$key]);
            }
        }

        return $data;
    }

    /*** FUNCTIONS DEFINED BY AbstractTableGateway ***/

    /**
     * Defined by AbstractTableGateway; Get Feature\FeatureSet
     *
     * Modified to instantiate feature on first call
     *
     * @return Feature\FeatureSet
     */
    public function getFeatureSet()
    {
        if (! $this->featureSet instanceof Feature\FeatureSet) {
            $this->featureSet = new Feature\FeatureSet();
        }
        return $this->featureSet;
    }

    /**
     * Defined in AbstractTableGateway; Get columns
     *
     * Fetch columns if not populated. Feature\MetadataFeature is not used as it is very slow.
     *
     * @return array
     */
    public function getColumns()
    {
        if (empty($this->columns)) {
            $columns = $this->adapter->query(
                'SELECT column_name FROM information_schema.columns '
                . 'WHERE table_schema = ? AND table_name = ?',
                [$this->adapter->getCurrentSchema(), $this->table]
            );
            $this->columns = [];
            foreach ($columns as $column) {
                $this->columns[] = $column->column_name;
            }
        }

        return $this->columns;
    }

    /**
     * Defined by AbstractTableGateway; Insert
     *
     * Modified to handle EntityInterface.
     *
     * @param  array|EntityInterface $set
     * @return int Affected rows, not last insert id as in ZF1
     */
    public function insert($set)
    {
        if ($set instanceof EntityInterface) {
            // $set->setCreator($set->getAuthIdentity())->setCreated(date('c')); // eg. of how getAuthIdentity() is used
            $set = $set->getArrayCopy();
        }
        return parent::insert($this->filterColumns($set));
    }

    /**
     * Defined by AbstractTableGateway; Delete
     *
     * Modified to handle EntityInterface.
     *
     * If an entity is passed in for $where, it is assumed that the
     * entity is to be deleted. This is useful, eg. in the controller,
     * where the user does not and should not know the column name or how to
     * construct a where clause.
     *
     * @param  string|array|closure|EntityInterface $where
     * @return int Affected rows
     */
    public function delete($where)
    {
        if ($where instanceof EntityInterface) {
            $where = [$this->getPrimaryKey() . ' = ?' => $where->getId()];
        }
        return parent::delete($where);
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

        if ($resultSetClass == $this->resultSetClass && $this->resultSetPrototype instanceof ResultSetInterface) {
            return $this->resultSetPrototype;
        }

        // Create prototype
        $resultSetInstance = new $resultSetClass();
        if (! $resultSetInstance instanceof EntityInterface) {
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

    /*** FUNCTIONS DEFINED BY MapperInterface ***/

    /**
     * Defined by MapperInterface; Populate mapper with records from non-database source
     *
     * @param  array $records
     * @return MapperInterface
     */
    public function setRecords($records)
    {
        if (! is_array($records)) {
            throw new Exception\InvalidArgumentException('Array expected');
        }

        $this->records = $records;
    }

    /**
     * Defined by MapperInterface; Get available row states as value-option pairs
     * which can be used to populate a dropdown list
     *
     * @return array
     */
    public static function getRowStates()
    {
        $values = [static::ACTIVE_ROWS, static::DELETED_ROWS, static::ALL_ROWS];
        return array_combine($values, $values);
    }

    /**
     * Defined by MapperInterface; Check whether the mapper and its entity support
     * row states (active, deleted, all)
     *
     * Ideally, no records should ever be deleted from the database and should have
     * a field to mark it as deleted instead - this is what row state refers to.
     *
     * @return bool
     */
    public function hasRowState()
    {
        $hasActiveRowState  = is_array($this->activeRowState) && ! empty($this->activeRowState);
        $hasDeletedRowState = is_array($this->deletedRowState) && ! empty($this->deletedRowState);
        return ($hasActiveRowState && $hasDeletedRowState);
    }

    /**
     * Defined by MapperInterface; Set row state
     *
     * Rows returned from query results will conform to the current specified row state
     *
     * @param  string $rowState Options: self::ACTIVE_ROWS, self::DELETED_ROWS,
     *                          self::ALL_ROWS
     * @return MapperInterface
     */
    public function setRowState($rowState)
    {
        $this->rowState = $rowState;
        return $this;
    }

    /**
     * Defined by MapperInterface; Mark records as active
     *
     * @param  string|array|closure|EntityInterface $where
     * @return int|bool Affected rows. Return false if row state not supported
     */
    public function markActive($where)
    {
        if (! $this->hasRowState()) {
            return false;
        }

        if ($where instanceof EntityInterface) {
            $where = [$this->getPrimaryKey() . ' = ?' => $where->getId()];
        }
        return parent::update($this->activeRowState, $where);
    }

    /**
     * Defined by MapperInterface; Mark records as deleted
     *
     * @param  string|array|closure|EntityInterface $where
     * @return int|bool Affected rows. Return false if row state not supported
     */
    public function markDeleted($where)
    {
        if (! $this->hasRowState()) {
            return false;
        }

        if ($where instanceof EntityInterface) {
            $where = [$this->getPrimaryKey() . ' = ?' => $where->getId()];
        }
        return parent::update($this->deletedRowState, $where);
    }

    /**
     * Defined by MapperInterface; Fetch row by primary key
     *
     * @param  string $key The value for the primary key
     * @return null|EntityInterface
     */
    public function fetch($key)
    {
        if (null === $key) {
            return null;
        }

        $select = $this->getBaseSelect();
        $select->where([$this->getPrimaryKey() => $key]);
        return $this->getResultSet($select, false);
    }

    /**
     * Defined by MapperInterface; Fetch all rows
     *
     * @return null|Paginator
     */
    public function fetchAll()
    {
        $select = $this->getBaseSelect();
        return $this->getResultSet($select);
    }

    /**
     * Defined by MapperInterface; Fetch rows where the primary key matches a list of values
     *
     * @param  array       $values
     * @param  null|string $column Optional column to use instead of primary key column
     * @return null|Paginator
     */
    public function fetchIn($values, $column = null)
    {
        if (! $values || ! is_array($values)) {
            return null;
        }

        if (null === $column) {
            $column = $this->getPrimaryKey();
        }

        $dbAdapter = $this->adapter;
        $qi = function ($name) use ($dbAdapter) {
            return $dbAdapter->platform->quoteIdentifier($name);
        };
        $qv = function ($value) use ($dbAdapter) {
            return $dbAdapter->platform->quoteValue($value);
        };

        $select = $this->getBaseSelect();
        $select->where->in($column, $values);
        $select->order([new Expression(sprintf( // sort according to the order of the given values
            'FIELD (%s, %s)',
            $qi($column),
            implode(',', array_map($qv, $values))
        ))]);

        return $this->getResultSet($select);
    }

    /**
     * Defined by MapperInterface; Create
     *
     * @param  array|EntityInterface $set
     * @return null|EntityInterface Return null if unable to create
     */
    public function create($set)
    {
        $affectedRows = $this->insert($set);
        if (! $affectedRows) {
            return null;
        }

        if (! $set instanceof EntityInterface) {
            $set = new $this->resultSetClass($set);
        }

        $set->setId($this->getLastInsertValue()); // this is important!
        return $set;
    }

    /**
     * Defined by MapperInterface and AbstractTableGateway; Update
     *
     * If an entity is passed in for $where OR an entity is passed with null $where,
     * it is assumed that the update is for that entity. This is useful, eg. in the controller,
     * where the user does not and should not know the column name or how to
     * construct a where clause.
     *
     * @param  array $set
     * @param  string|array|closure|EntityInterface $where
     * @return int No. of affected rows. Not practical to return EntityInterface
     *             as the update could be for multiple rows.
     */
    public function update($set, $where = null)
    {
        if ($set instanceof EntityInterface) {
            if (null === $where) {
                $where = $set;
            }
            // $set->setUpdator($set->getAuthIdentity())->setUpdated(date('c')); // eg. of how getAuthIdentity() is used
            $set = $set->getArrayCopy();
        }
        if ($where instanceof EntityInterface) {
            $where = [$this->getPrimaryKey() . ' = ?' => $where->getId()];
        }

        return parent::update($this->filterColumns($set), $where);
    }
}
