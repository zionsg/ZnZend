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
use Zend\Code\Generator\ParameterGenerator;
use Zend\Code\Generator\DocBlock\Tag;
use Zend\Code\Generator\DocBlock\Tag\ParamTag;
use Zend\Code\Generator\DocBlock\Tag\ReturnTag;
use Zend\Db\Adapter\Adapter;
use ZnZend\Db\AbstractEntity;
use ZnZend\Db\Exception;

/**
 * Generate entity classes for tables in a database based on AbstractEntity
 *
 * This only generates the initial classes and does not do all the work for you.
 * The entity name, $_mapGettersColumns, setters and getters for each column are generated
 * using simplistic naming rules.
 *
 * The default column naming convention (see columnToSetterFunc() and columnToGetterFunc()) is assumed as:
 *   <table prefix>_<first word>_<second word if any, if none, no trailing underscore>
 * If the first word is "is" or "has", the column is assumed to be a BOOLEAN flag column and a boolean method
 * will be generated as well which casts the actual string/numeric value to boolean by default.
 * The user should modify the boolean method after generating if the values do not cast easily to boolean,
 * eg. "yes" and "no".
 *
 * Examples for default naming rules:
 *   `name` will generate setName() and getName(), assuming there is no table prefix
 *   `is_deleted` will generated setDeleted() and getDeleted() as "is_" is considered as the table prefix
 *   `person_first_name` will generate setFirstName() and getFirstName()
 *   `person_firstname` will generate setFirstname() and getFirstname()
 *   `person_is_deleted` will generate setIsDeleted(), getIsDeleted() and isDeleted()
 *   `person_has_visa` will generate setHasVisa(), getHasVisa() and hasVisa()
 *
 * Note: If a table has 2 columns with the same '_*' ending, eg. user_id and role_id,
 * an error will occur as the default columnToSetterFunc() and columnToGetterFunc()
 * will create setId() and getId() for both columns, resulting in a conflict.
 * In this case, custom naming callbacks should be passed to generate().
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
     * @param  string            $filePath            Path to write generated files
     * @param  Adapter           $dbAdapter           Database adapter
     * @param  string            $namespace           Namespace for entity and table gateway classes
     * @param  DocBlockGenerator $fileDocBlock        Optional docblock for all files
     * @param  callable          $columnToSetterFunc  Optional callback that takes in
     *                                                (string $tableName, string $columnName) and returns setter name
     * @param  callable          $columnToGetterFunc  Optional callback that takes in
     *                                                (string $tableName, string $columnName) and returns getter name
     * @param  callable          $columnToBooleanFunc Optional callback that takes in
     *                                                (string $tableName, string $columnName) and returns
     *                                                boolean function name if it is considered a BOOLEAN column
     *                                                or false if it is not considered a BOOLEAN column
     * @throws Exception\InvalidArgumentException When path is not writable
     * @return void
     */
    public static function generate(
        $filePath,
        Adapter $dbAdapter,
        $namespace,
        DocBlockGenerator $fileDocBlock = null,
        $columnToSetterFunc = null,
        $columnToGetterFunc = null,
        $columnToBooleanFunc = null
    ) {
        if (!is_writable($filePath)) {
            throw new Exception\InvalidArgumentException("{$filePath} is not writable");
        }
        if (!is_callable($columnToSetterFunc)) {
            $columnToSetterFunc = self::columnToSetterFunc();
        }
        if (!is_callable($columnToGetterFunc)) {
            $columnToGetterFunc = self::columnToGetterFunc();
        }
        if (!is_callable($columnToBooleanFunc)) {
            $columnToBooleanFunc = self::columnToBooleanFunc();
        }

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
                'SELECT column_name, column_key, data_type, '
                . 'character_maximum_length, numeric_precision, column_default '
                . 'FROM information_schema.columns '
                . 'WHERE table_schema = ? AND table_name = ?',
                array($databaseName, $tableName)
            );

            // Create getter and setters for each column and map them
            $mapGettersColumns = array('getId' => null, 'getName' => null, 'isDeleted' => false);
            $properties = array();
            $methods = array();
            $types = array();
            foreach ($columns as $column) {
                $columnName = $column->column_name;
                $isPrimary  = ('PRI' == $column->column_key);
                $isNumeric  = ($column->numeric_precision !== null);
                $defaultValue = ($isPrimary ? null : $column->column_default);
                $sqlType      = $column->data_type;
                $phpType      = self::getPhpType($sqlType);
                $setterName   = $columnToSetterFunc($tableName, $columnName);
                $getterName   = $columnToGetterFunc($tableName, $columnName);
                $booleanName  = $columnToBooleanFunc($tableName, $columnName);
                $columnWords  = explode('_', $columnName);
                if (count($columnWords) > 1) {
                    array_shift($columnWords);
                }
                $label = strtolower(implode(' ', $columnWords));

                $tags = array(new Tag(array(
                    'name' => '@Annotation\Exclude()', // no form input needed for primary keys
                )));
                if (!$isPrimary) {
                    $tags = array(
                        new Tag(array(
                            'name' => '@Annotation\Name("' . $columnName . '")',
                        )),
                        new Tag(array(
                            'name' => '@Annotation\Required(false)',
                        )),
                        new Tag(array(
                            'name' => sprintf(
                                '@Annotation\Type("Zend\Form\Element\%s")',
                                ('text' == substr($sqlType, -4) ? 'Textarea' : 'Text')
                            ),
                        )),
                        new Tag(array(
                            'name' => sprintf(
                                '@Annotation\Attributes({"placeholder":"' . $label . '"%s})',
                                ($sqlType != 'varchar' ? '' : ', "maxlength":' . $column->character_maximum_length)
                            ),
                        )),
                        new Tag(array(
                            'name' => '@Annotation\Options({"label":"' . $label . '"})',
                        )),
                        new Tag(array(
                            'name' => '@Annotation\Filter({"name":"StringTrim"})',
                        )),
                    );
                    if ($isNumeric) { // numeric field
                        $tags[] = new Tag(array(
                            'name' => '@Annotation\Validator({"name":"Digits"})',
                        ));
                    }
                }
                if ($isNumeric && !$isPrimary) {
                    $defaultValue = ('int' == substr($sqlType, -3)) ? (int) $defaultValue : (float) $defaultValue;
                }
                $properties[] = PropertyGenerator::fromArray(array(
                    'name'         => $columnName,
                    'visibility'   => 'protected',
                    'defaultValue' => $defaultValue,
                    'docblock'     => DocBlockGenerator::fromArray(array(
                        'shortDescription' => null,
                        'longDescription'  => null,
                        'tags'             => $tags,
                    )),
                ));

                // Setter
                if ($setterName) {
                    if (!in_array($setterName, array('setId', 'setName'))) { // skip methods defined in AbstractEntity
                        $desc = 'Set ' . $label;
                        $methods[] = MethodGenerator::fromArray(array(
                            'name'       => $setterName,
                            'parameters' => array(
                                ParameterGenerator::fromArray(array('name' => 'value')),
                            ),
                            'body'       =>   ('string' == $phpType)
                                            ? 'return $this->set($value);'
                                            : "return \$this->set(\$value, '{$phpType}');",
                            'docblock'   => DocBlockGenerator::fromArray(array(
                                'shortDescription' => $desc,
                                'longDescription'  => null,
                                'tags'             => array(
                                    new ParamTag(array(
                                        'paramName' => 'value',
                                        'datatype'  => 'null|' . $phpType,
                                    )),
                                    new ReturnTag(array(
                                        'datatype'  => $entityName,
                                    )),
                                ),
                            )),
                        ));
                    }
                }

                // Getter
                if ($getterName) {
                    $mapGettersColumns[$getterName] = $columnName;
                    if (!in_array($getterName, array('getId', 'getName'))) { // skip methods defined in AbstractEntity
                        $desc = 'Get ' . $label;
                        $methods[] = MethodGenerator::fromArray(array(
                            'name'       => $getterName,
                            'body'       => 'return $this->get();',
                            'docblock'   => DocBlockGenerator::fromArray(array(
                                'shortDescription' => $desc,
                                'longDescription'  => null,
                                'tags'             => array(
                                    new ReturnTag(array(
                                        'datatype'  => self::getPhpType($sqlType),
                                    )),
                                ),
                            )),
                        ));
                    }
                }

                // Boolean method
                if ($booleanName) {
                    $mapGettersColumns[$booleanName] = $columnName;
                    if ($booleanName != 'isDeleted') { // skip methods defined in AbstractEntity
                        $desc = 'Check if ' . $label;
                        $methods[] = MethodGenerator::fromArray(array(
                            'name'       => $booleanName,
                            'body'       => 'return (bool) $this->get();',
                            'docblock'   => DocBlockGenerator::fromArray(array(
                                'shortDescription' => $desc,
                                'longDescription'  => null,
                                'tags'             => array(
                                    new ReturnTag(array(
                                        'datatype'  => 'bool',
                                    )),
                                ),
                            )),
                        ));
                    }
                }
            }

            // Generate class
            $entityClass = new ClassGenerator();
            $entityClass->setName($entityName)
                        ->setNamespaceName($namespace)
                        ->addUse('DateTime')
                        ->addUse('Zend\Form\Annotation')
                        ->addUse('ZnZend\Db\AbstractEntity')
                        ->setExtendedClass('AbstractEntity')
                        ->setDocBlock(DocBlockGenerator::fromArray(array(
                              'shortDescription' => null,
                              'longDescription'  => null,
                              'tags'             => array(
                                  new Tag(array(
                                      'name' => '@Annotation\Name("' . $entityName . '")',
                                  )),
                                  new Tag(array(
                                      'name' => '@Annotation\Type("ZnZend\Form\Form")',
                                  )),
                                  new Tag(array(
                                      'name' => '@Annotation\Hydrator("Zend\Stdlib\Hydrator\ArraySerializable")',
                                  )),
                              ),
                          )));

            if ($mapGettersColumns) {
                array_unshift($properties, PropertyGenerator::fromArray(array(
                    'name'         => '_mapGettersColumns',
                    'visibility'   => 'protected',
                    'static'       => true,
                    'defaultValue' => $mapGettersColumns,
                    'docblock'     => DocBlockGenerator::fromArray(array(
                        'shortDescription' => null,
                        'longDescription'  => null,
                        'tags'             => array(
                            new Tag(array(
                                'name' => '@Annotation\Exclude()',
                            )),
                            new Tag(array(
                                'name' => '@var',
                                'description' => 'array',
                            )),
                        ),
                    ))
                )));
            }

            array_unshift(
                $properties,
                PropertyGenerator::fromArray(array(
                    'name'         => '_singularNoun',
                    'visibility'   => 'protected',
                    'defaultValue' => strtolower($entityName),
                    'docblock'     => DocBlockGenerator::fromArray(array(
                        'shortDescription' => null,
                        'longDescription'  => null,
                        'tags'             => array(
                            new Tag(array(
                                'name' => '@Annotation\Exclude()',
                            )),
                            new Tag(array(
                                'name' => '@var',
                                'description' => 'string',
                            )),
                        ),
                    ))
                )),
                PropertyGenerator::fromArray(array(
                    'name'         => '_pluralNoun',
                    'visibility'   => 'protected',
                    'defaultValue' => strtolower($entityName) . 's',
                    'docblock'     => DocBlockGenerator::fromArray(array(
                        'shortDescription' => null,
                        'longDescription'  => null,
                        'tags'             => array(
                            new Tag(array(
                                'name' => '@Annotation\Exclude()',
                            )),
                            new Tag(array(
                                'name' => '@var',
                                'description' => 'string',
                            )),
                        ),
                    ))
                ))
            );

            if ($properties) {
                $entityClass->addProperties($properties);
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
            // 4) Closing bracket for $_mapGettersColumns array property should not be indented
            $output = $entityFile->generate();
            $output = str_replace("\nnamespace", "namespace", $output);
            $output = str_replace("{\n\n", "{\n", $output);
            $output = str_replace("\n\n}\n\n", "}\n", $output);
            $output = preg_replace('/[ ]{4}([ ]{4}\);)/', '\1', $output);
            file_put_contents("{$filePath}/{$entityName}.php", $output);
        }
    }

    /**
     * Get default callback for generating setter name from column name
     *
     * @see    class docblock for examples
     * @return callable
     */
    protected static function columnToSetterFunc()
    {
        return function ($tableName, $columnName) {
            $words = explode('_', $columnName);

            if (count($words) <= 1) { // no table prefix
                $name = 'set' . ucfirst($columnName);
            } else {
                $tablePrefix = array_shift($words);
                $name = 'set' . implode('', array_map('ucfirst', $words));
            }

            return $name;
        };
    }

    /**
     * Get default callback for generating getter name from column name
     *
     * @see    class docblock for examples
     * @return callable
     */
    protected static function columnToGetterFunc()
    {
        return function ($tableName, $columnName) {
            $words = explode('_', $columnName);

            if (count($words) <= 1) { // no table prefix
                $name = 'get' . ucfirst($columnName);
            } else {
                $tablePrefix = array_shift($words);
                $name = 'get' . implode('', array_map('ucfirst', $words));
            }

            return $name;
        };
    }

    /**
     * Get default callback for generating boolean method name from BOOLEAN column
     *
     * Format of BOOLEAN column: <table prefix>_is_<...> or <table prefix>_has_<...>
     *
     * @see    class docblock for examples
     * @return callable
     */
    protected static function columnToBooleanFunc()
    {
        return function ($tableName, $columnName) {
            $words = explode('_', $columnName);

            if (count($words) < 3) { // not considered a BOOLEAN column
                $name = false;
            } else {
                $tablePrefix = array_shift($words);
                $verb = strtolower(array_shift($words));

                if ($verb != 'is' && $verb != 'has') { // not considered a BOOLEAN column
                    $name = false;
                } else {
                    $name = $verb . implode('', array_map('ucfirst', $words));
                }
            }

            return $name;
        };
    }

    /**
     * Get PHP data type for use in docblock
     *
     * @param  string $sqlType SQL data type
     * @return string
     */
    protected static function getPhpType($sqlType)
    {
        return (isset(self::$mapTypes[$sqlType]) ? self::$mapTypes[$sqlType] : 'string');
    }
}
