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
use Zend\Code\Generator\MethodGenerator;
use Zend\Code\Generator\DocBlock\Tag\ReturnTag;
use ZnZend\Db\AbstractEntity;
use ZnZend\Db\Exception;

/**
 * Generate entity classes for tables in a database based on AbstractEntity
 *
 * This only generates the initial classes and does not do all the work for you.
 * The entity name, $mapGettersColumns and getters for each column are generated
 * using simplistic naming rules. Eg: For a `useragent` column, the generated getter
 * will be named 'getUseragent()' and not 'getUserAgent()'.
 */
class EntityGenerator
{
    /**
     * Map SQL data types to PHP
     *
     * Uppercase PHP types imply classes, lowercase imply primitive types
     * String types are not mapped
     *
     * @var array
     */
    protected static $mapTypes = array(
        'int' => 'int',
        'tinyint' => 'int',
        'smallint' => 'int',
        'mediumint' => 'int',
        'bigint' => 'int',
        'decimal' => 'real',
        'float' => 'float',
        'double' => 'double',
        'real' => 'real',
        'datetime' => 'DateTime',
        'timestamp' => 'DateTime',
    );


    /**
     * Generate entity classes for all tables in active database
     *
     * The params are applied to all the entities.
     *
     * @param string   $filePath            Path to write generated files
     * @param string   $namespace           Namespace for entity and table gateway classes
     * @param callable $columnToGetterFunc  Optional callback that takes in (string $tableName, string $columnName)
     *                                      and returns getter name
     * @param DocBlockGenerator $fileDocBlock   Optional docblock for all files
     * @param DocBlockGenerator $entityDocBlock Optional docblock for all entity classes.
     * @throws Exception\InvalidArgumentException When path is not writable
     * @return void
     */
    public static function generate(
        $filePath,
        \Zend\Db\Adapter\Adapter $dbAdapter,
        $namespace,
        $columnToGetterFunc = null,
        DocBlockGenerator $fileDocBlock = null,
        DocBlockGenerator $entityDocBlock = null
    ) {
        if (!is_writable($filePath)) {
            throw new Exception\InvalidArgumentException("{$filePath} is not writable");
        }

        // Default callback for generating getter name from column name
        // Eg: 'name' and 'person_name' becomes 'getName'
        // Eg: 'isdeleted' and 'person_isdeleted' becomes 'isDeleted'
        if (!is_callable($columnToGetterFunc)) {
            $columnToGetterFunc = function ($tableName, $columnName) {
                $pattern = '/^.*[^a-zA-Z0-9]+(.+)$/';
                $matches = array();
                $normalizedName = (preg_match($pattern, $columnName, $matches) ? $matches[1] : $columnName);
                if (0 === stripos($normalizedName, 'is')) {
                    return 'is' . ucfirst(substr($normalizedName, 2));
                }
                return 'get' . ucfirst($normalizedName);
            };
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

            // Get column info for each table
            $columns = $dbAdapter->query(
                'SELECT column_name, data_type FROM information_schema.columns '
                . 'WHERE table_schema = ? AND table_name = ?',
                array($databaseName, $tableName)
            );

            // Create getter methods for each column and map them
            $mapGettersColumns = array();
            $methods = array();
            $types = array();
            foreach ($columns as $column) {
                $columnName = $column->column_name;
                $dataType   = $column->data_type;
                $getterName = $columnToGetterFunc($tableName, $columnName);
                if (!$getterName) {
                    continue;
                }
                $mapGettersColumns[$getterName] = $columnName;

                $returnType = self::getReturnType($dataType);
                $desc = 'Get ' . lcfirst(substr($getterName, strcspn($getterName, 'ABCDEFGHJIJKLMNOPQRSTUVWXYZ')));
                $methods[] = MethodGenerator::fromArray(array(
                    'name'       => $getterName,
                    'body'       => 'return ' . self::getTypeCast('$this->get()', $dataType),
                    'docblock'   => DocBlockGenerator::fromArray(array(
                        'shortDescription' => $desc,
                        'longDescription'  => null,
                        'tags'             => array(
                            new ReturnTag(array(
                                'datatype'  => $returnType,
                            )),
                        ),
                    )),
                ));
            }

            // Generate class
            $entityClass = new ClassGenerator();
            $entityClass->setName($entityName)
                        ->setNamespaceName($namespace)
                        ->addUse('ZnZend\Db\AbstractEntity')
                        ->setExtendedClass('AbstractEntity');
            if ($entityDocBlock !== null) {
                $entityClass->setDocBlock($entityDocBlock);
            }
            if ($mapGettersColumns) {
                $entityClass->addProperties(array(
                    PropertyGenerator::fromArray(array(
                        'name'         => 'mapGettersColumns',
                        'visibility'   => 'protected',
                        'static'       => true,
                        'defaultValue' => $mapGettersColumns,
                    ))
                ));
            }
            if ($methods) {
                $entityClass->addMethods($methods);
            }

            // Generate file
            $entityFile = new FileGenerator();
            if ($fileDocBlock !== null) {
                $entityFile->setDocBlock($fileDocBlock);
            }
            $entityFile->setFilename("{$entityName}.php")
                       ->setClass($entityClass);

            // Adjust whitespace
            // 1) Only 1 blank line between file docblock and namespace
            // 2) No blank line between opening brace for class and first property
            // 3) No blank line between last method and closing brace for class
            $output = $entityFile->generate();
            $output = str_replace("\nnamespace", "namespace", $output);
            $output = str_replace("{\n\n", "{\n", $output);
            $output = str_replace("\n\n}\n\n", "}\n", $output);
            file_put_contents("{$filePath}/{$entityName}.php", $output);
        }
    }

    /**
     * Get return type for use in docblock
     *
     * @param  string $sqlType SQL data type
     * @return string
     */
    protected static function getReturnType($sqlType)
    {
        return (isset(self::$mapTypes[$sqlType]) ? self::$mapTypes[$sqlType] : 'string');
    }

    /**
     * Get type cast for use in method body
     *
     * @example getTypeCast('$this->get()', 'int') will return '(int) $this->get();'
     * @example getTypeCast('$this->get()', 'timestamp') will return 'new DateTime($this->get());'
     * @param   string $expr    Expression
     * @param   string $sqlType SQL data type
     * @return  string
     */
    protected static function getTypeCast($expr, $sqlType)
    {
        $returnType = self::getReturnType($sqlType);
        if ($returnType == strtolower($returnType)) {
            return "({$returnType}) {$expr};";
        }

        // Cast to object
        return "new {$returnType}({$expr});";
    }
}
