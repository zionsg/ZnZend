<?php
/**
 * ZnZend
 *
 * @author Zion Ng <zion@intzone.com>
 * @link   http://github.com/zionsg/ZnZend for canonical source repository
 */

namespace ZnZend\Mvc\Controller\Plugin;

use Zend\Db\Adapter\AdapterInterface;
use Zend\Mvc\Controller\Plugin\AbstractPlugin;

/**
 * Controller plugin to calculate row size in bytes for each table in specified database
 */
class ZnZendDatabaseRowSize extends AbstractPlugin
{
    /**
     * Data type storage requirements as per MySQL
     *
     * @var array array(<data type> => <size in bytes>, <data type> => <callback to calculate size in bytes>)
     */
    protected $dataTypeBytes = array(
        'tinyint' => 1,
        'smallint' => 2,
        'mediumint' => 3,
        'int' => 4,
        'bigint' => 8,
        'double' => 8,
        'date' => 3,
        'time' => 3,
        'datetime' => 8,
        'timestamp' => 4,
        'year' => 1,
        'tinytext' => 255,
        'text' => 65535,
        'mediumtext' => 16777215,
        'longtext' => 4294967295,
        'tinyblob' => 255,
        'blob' => 65535,
        'mediumblob' => 16777215,
        'longblob' => 4294967295,
    );

    /**
     * Constructor
     *
     * Add callbacks for dynamically calculated data types
     */
    public function __construct()
    {
        $decimalNumericCallback = function ($column) {
            $integerDigits    = $column->numeric_precision - $column->numeric_scale;
            $fractionalDigits = $column->numeric_scale;
            $integerBytes     = (floor($integerDigits / 9) * 4) + ceil(($integerDigits % 9) / 2);
            $fractionalBytes  = (floor($fractionalDigits / 9) * 4) + ceil(($fractionalDigits % 9) / 2);
            return ($integerBytes + $fractionalBytes);
        };

        $this->dataTypeBytes = array_merge($this->dataTypeBytes, array(
            'float'   => function ($column) {
                $precision = ($column->numeric_precision ?: 0);
                return ($precision <= 24 ? 4 : 8);
            },
            'decimal' => $decimalNumericCallback,
            'numeric' => $decimalNumericCallback,
            'bit' => function ($column) {
                return ceil(($column->numeric_precision + 7) / 8);
            },
        ));
    }

    /**
     * Calculate row size in bytes for each table in specified database
     *
     * @param  AdapterInterface $dbAdapter Database adapter
     * @param  string           $dbName    Database name
     * @return array            array(<table 1> => <row size>, <table 2> => <row size>, ...)
     */
    public function __invoke(AdapterInterface $dbAdapter, $dbName)
    {
        $columns = $dbAdapter->query(
            'SELECT table_name, column_name, data_type, '
            . 'character_maximum_length, numeric_precision, numeric_scale '
            . 'FROM information_schema.columns '
            . 'WHERE table_schema = ?',
            array($dbName)
        );

        $tableBytes = array();
        foreach ($columns as $column) {
            $columnBytes = ($column->character_maximum_length ?: 0);
            if (!$columnBytes && isset($this->dataTypeBytes[$column->data_type])) {
                $value = $this->dataTypeBytes[$column->data_type];
                $columnBytes = is_callable($value) ? $value($column) : $value;
            }
            if (!isset($tableBytes[$column->table_name])) {
                $tableBytes[$column->table_name] = 0;
            }
            $tableBytes[$column->table_name] += $columnBytes;
        }

        return $tableBytes;
    }
}
