<?php
/**
 * ZnZend
 *
 * @author Zion Ng <zion@intzone.com>
 * @link   http://github.com/zionsg/ZnZend for canonical source repository
 */

namespace ZnZend\Db\Generator;

use Zend\Code\Generator\FileGenerator;
use Zend\Code\Generator\ClassGenerator;
use Zend\Code\Generator\DocBlockGenerator;
use Zend\Code\Generator\PropertyGenerator;
use ZnZend\Db\AbstractTable;
use ZnZend\Db\Exception;

/**
 * Generate table gateway classes for tables in the database based on AbstractTable
 *
 * This only generates the initial classes and does not do all the work for you.
 * Simple naming is used for the class name. Eg: For a `map_company_employee` table,
 * the generated class will be named 'Map_company_employee' and not 'MapCompanyEmployee'
 * nor 'Map_Company_Employee'.
 */
class TableGenerator
{
    /**
     * Generate table gateway classes for all tables in active database based on AbstractTable
     *
     * The params are applied to all the tables.
     *
     * @param string   $filePath            Path to write generated files
     * @param string   $namespace           Namespace for entity and table gateway classes
     * @param callable $activeRowStateFunc  Optional callback that takes in (string $tableName, array $columnNames)
     *                                      and returns array('activeColumn' => 'activeValue') for
     *                                      AbstractTable::$activeRowState
     * @param callable $deletedRowStateFunc Optional callback that takes in (string $tableName, array $columnNames)
     *                                      and returns array('deletedColumn' => 'deletedValue') for
     *                                      AbstractTable::$deletedRowState
     * @param DocBlockGenerator $fileDocBlock  Optional docblock for all files
     * @param DocBlockGenerator $tableDocBlock Optional docblock for all table classes
     * @throws Exception\InvalidArgumentException When path is not writable
     * @return void
     */
    public static function generate(
        $filePath,
        \Zend\Db\Adapter\Adapter $dbAdapter,
        $namespace,
        $activeRowStateFunc = null,
        $deletedRowStateFunc = null,
        DocBlockGenerator $fileDocBlock = null,
        DocBlockGenerator $tableDocBlock = null
    ) {
        if (!is_writable($filePath)) {
            throw new Exception\InvalidArgumentException("{$filePath} is not writable");
        }

        // Default callbacks for getting active/deleted row state column-value pairs if BOTH are undefined
        // If a table has a column ending in 'isdeleted', it is assumed that the column indicates the row state
        // Eg: `person_isdeleted` column is found, so $activeRowState = array('person_isdeleted' => 0)
        // and $deletedRowState = array('person_isdeleted' => 1)
        if (!is_callable($activeRowStateFunc) && !is_callable($deletedRowStateFunc)) {
            $rowStateFunc = function ($stateValue) {
                return function ($tableName, $columnNames) use ($stateValue) {
                    foreach ($columnNames as $columnName) {
                        if (preg_match('/^.*[^a-zA-Z0-9]+isdeleted$/', $columnName)) {
                            return array($columnName => $stateValue);
                        }
                    }
                    return false;
                };
            };
            $activeRowStateFunc  = $rowStateFunc(0);
            $deletedRowStateFunc = $rowStateFunc(1);
        }

        $qi = function ($name) use ($dbAdapter) { return $dbAdapter->platform->quoteIdentifier($name); };
        $fp = function ($name) use ($dbAdapter) { return $dbAdapter->driver->formatParameterName($name); };

        // Iterate thru tables
        $databaseName = $dbAdapter->getCurrentSchema();
        $tables = $dbAdapter->query(
            'SELECT table_name FROM information_schema.tables WHERE table_schema = ?',
            array($databaseName)
        );
        foreach ($tables as $table) {
            $tableName  = $table->table_name;
            $entityName = ucfirst($tableName);

            // Get column names for each table
            $columns = $dbAdapter->query(
                'SELECT column_name FROM information_schema.columns '
                . 'WHERE table_schema = ? AND table_name = ?',
                array($databaseName, $tableName)
            );
            $columnNames = array();
            foreach ($columns as $column) {
                $columnNames[] = $column->column_name;
            }

            // Generate class
            $tableClass = new ClassGenerator();
            if ($tableDocBlock !== null) {
                $tableClass->setDocBlock($tableDocBlock);
            }
            $properties = array(
                PropertyGenerator::fromArray(array(
                    'name'         => 'table',
                    'visibility'   => 'protected',
                    'defaultValue' => $tableName,
                )),
                PropertyGenerator::fromArray(array(
                    'name'         => 'resultSetClass',
                    'visibility'   => 'protected',
                    'defaultValue' => "\\{$namespace}\\{$entityName}",
                )),
            );
            if (is_callable($activeRowStateFunc)) {
                $activeColumnValue = $activeRowStateFunc($tableName, $columnNames);
                if ($activeColumnValue) {
                    $properties[] = PropertyGenerator::fromArray(array(
                        'name'         => 'activeRowState',
                        'visibility'   => 'protected',
                        'defaultValue' => $activeColumnValue,
                    ));
                }
            }
            if (is_callable($deletedRowStateFunc)) {
                $deletedColumnValue = $deletedRowStateFunc($tableName, $columnNames);
                if ($deletedColumnValue) {
                    $properties[] = PropertyGenerator::fromArray(array(
                        'name'         => 'deletedRowState',
                        'visibility'   => 'protected',
                        'defaultValue' => $deletedColumnValue,
                    ));
                }
            }
            $tableClass->setName($entityName . 'Table')
                       ->setNamespaceName($namespace)
                       ->addUse("{$namespace}\\{$entityName}")
                       ->addUse('ZnZend\Db\AbstractTable')
                       ->setExtendedClass('AbstractTable')
                       ->addProperties($properties);

            // Generate file
            $tableFile = new FileGenerator();
            if ($fileDocBlock !== null) {
                $tableFile->setDocBlock($fileDocBlock);
            }
            $tableFile->setFilename("{$entityName}Table.php")
                      ->setClass($tableClass);

            // Adjust whitespace
            // 1) Only 1 blank line between file docblock and namespace
            // 2) No blank line between opening brace for class and first property
            // 3) No blank line between last method and closing brace for class
            $output = $tableFile->generate();
            $output = str_replace("\nnamespace", "namespace", $output);
            $output = str_replace("{\n\n", "{\n", $output);
            $output = str_replace("\n\n}\n\n", "}\n", $output);
            file_put_contents("{$filePath}/{$entityName}Table.php", $output);
        }
    }
}
