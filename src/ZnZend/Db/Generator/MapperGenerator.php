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
use Zend\Db\Adapter\Adapter;
use ZnZend\Db\AbstractMapper;
use ZnZend\Db\Exception;

/**
 * Generate entity mapper classes for tables in the database based on AbstractMapper
 *
 * This only generates the initial classes and does not do all the work for you.
 *
 * Simple naming is used for the class name. Eg: For a `map_company_employee` table,
 * the generated mapper class will be named 'MapCompanyEmployeeMapper'.
 */
class MapperGenerator
{
    /**
     * Generate entity mapper for all tables in active database based on AbstractMapper
     *
     * The params are applied to all the tables.
     *
     * @param  string            $filePath            Path to write generated files
     * @param  Adapter           $dbAdapter           Database adapter
     * @param  string            $namespace           Namespace for entity and table gateway classes
     * @param  DocBlockGenerator $fileDocBlock        Optional docblock for all files
     * @param  callable          $activeRowStateFunc  Optional callback that takes in
     *                                                (string $tableName, array $columnNames)
     *                                                and returns array('activeColumn' => 'activeValue') for
     *                                                AbstractMapper::$activeRowState
     * @param  callable          $deletedRowStateFunc Optional callback that takes in
     *                                                (string $tableName, array $columnNames)
     *                                                and returns array('deletedColumn' => 'deletedValue') for
     *                                                AbstractMapper::$deletedRowState
     * @throws Exception\InvalidArgumentException When path is not writable
     * @return void
     */
    public static function generate(
        $filePath,
        Adapter $dbAdapter,
        $namespace,
        DocBlockGenerator $fileDocBlock = null,
        $activeRowStateFunc = null,
        $deletedRowStateFunc = null
    ) {
        if (!is_writable($filePath)) {
            throw new Exception\InvalidArgumentException("{$filePath} is not writable");
        }

        // Default callbacks for getting active/deleted row state column-value pairs if BOTH are undefined
        // If a table has a column ending in '_is_deleted' or '_isdeleted', it is assumed that the column
        // indicates the row state.
        // Eg: `person_is_deleted` column is found, so $activeRowState = array('person_is_deleted' => 0)
        // and $deletedRowState = array('person_is_deleted' => 1)
        if (!is_callable($activeRowStateFunc) && !is_callable($deletedRowStateFunc)) {
            $rowStateFunc = function ($stateValue) {
                return function ($tableName, $columnNames) use ($stateValue) {
                    foreach ($columnNames as $columnName) {
                        if (preg_match('/.+_is_?deleted$/i', $columnName)) {
                            return array($columnName => $stateValue);
                        }
                    }
                    return false;
                };
            };
            $activeRowStateFunc  = $rowStateFunc(0);
            $deletedRowStateFunc = $rowStateFunc(1);
        }

        // Iterate thru tables
        $databaseName = $dbAdapter->getCurrentSchema();
        $tables = $dbAdapter->query(
            'SELECT table_name FROM information_schema.tables WHERE table_schema = ?',
            array($databaseName)
        );
        foreach ($tables as $table) {
            // If table name is `map_ab_cd`, entity name will be MapAbCd
            $tableName  = $table->table_name;
            $entityName = str_replace(' ', '', ucwords(str_replace('_', ' ', $tableName)));

            // Get column names for each table
            $columns = $dbAdapter->query(
                'SELECT column_name, column_key FROM information_schema.columns '
                . 'WHERE table_schema = ? AND table_name = ?',
                array($databaseName, $tableName)
            );
            $columnNames = array();
            $primaryKeys = array();
            foreach ($columns as $column) {
                $columnNames[] = $column->column_name;
                if ('PRI' == $column->column_key) {
                    $primaryKeys[] = $column->column_name;
                }
            }

            // Generate class
            $mapperClass = new ClassGenerator();
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
            if ($primaryKeys) {
                $value = (1 == count($primaryKeys))
                       ? $primaryKeys[0]
                       : 'array(' . implode(',', array_map(function ($v) { return "'$v'"; }, $primaryKeys)) . ')';
                $properties[] = PropertyGenerator::fromArray(array(
                    'name'         => 'primaryKey',
                    'visibility'   => 'protected',
                    'defaultValue' => $value,
                ));
            }
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
            $mapperClass->setName($entityName . 'Mapper')
                        ->setNamespaceName($namespace)
                        ->addUse("{$namespace}\\{$entityName}")
                        ->addUse('ZnZend\Db\AbstractMapper')
                        ->setExtendedClass('AbstractMapper')
                        ->addProperties($properties);

            // Generate file
            $mapperFile = new FileGenerator();
            if ($fileDocBlock !== null) {
                $mapperFile->setDocBlock($fileDocBlock);
            }
            $mapperFile->setFilename("{$entityName}Mapper.php")
                       ->setClass($mapperClass);

            // Adjust whitespace
            // 1) Only 1 blank line between file docblock and namespace
            // 2) No blank line between opening brace for class and first property
            // 3) No blank line between last method and closing brace for class
            $output = $mapperFile->generate();
            $output = str_replace("\nnamespace", "namespace", $output);
            $output = str_replace("{\n\n", "{\n", $output);
            $output = str_replace("\n\n}\n\n", "}\n", $output);
            file_put_contents("{$filePath}/{$entityName}Mapper.php", $output);
        }
    }
}
