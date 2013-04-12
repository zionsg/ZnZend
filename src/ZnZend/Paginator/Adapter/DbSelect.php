<?php
/**
 * ZnZend
 *
 * @author Zion Ng <zion@intzone.com>
 * @link   http://github.com/zionsg/ZnZend for canonical source repository
 */

namespace ZnZend\Paginator\Adapter;

use Zend\Db\Sql\Select;
use Zend\Paginator\Adapter\DbSelect as ZendDbSelect;

/**
 * Custom adapter which allows modification of Select
 *
 * Scenario:
 *   HTML <table> in view script iterates over items in a Paginator populated from a model's database query method.
 *   Getters are used to retrieve item information instead of public properties (most likely
 *   named exactly after the actual table column names, which the designer doing the view script
 *   should not need to know).
 *   jQuery DataTables plugin in view script allows user to filter and sort records and passes parameters to
 *   controller, which then needs to be passed to the model to get an updated Paginator result set.
 *
 * Issues with above scenario:
 *   - Model would not know how to handle the DataTables params unless hardcoded.
 *   - All database query methods would need to allow passing in of params in order to facilitate filtering/sorting.
 *   - A controller plugin tailored to DataTables can be used to counter the above by applying WHERE and ORDER BY
 *     to the Select but ZendDbSelect does not allow retrieving of the Select object
 *
 * Additions to ZendDbSelect:
 *   - getSelect() added to allow retrieving of Select
 *   - updateSelect() added to allow updating of Select
 */
class DbSelect extends ZendDbSelect
{
    /**
     * Retrieve Select object
     *
     * @return Select
     */
    public function getSelect()
    {
        return $this->select;
    }

    /**
     * Update Select object
     *
     * Removes need for class consuming DbSelect to know the other arguments in
     * the constructor ($adapterOrSqlObject, $resultSetPrototype) in order to recreate the
     * instance with the updated Select object.
     *
     * @param  Select Updated Select object, eg. with addition WHERE or ORDER BY clauses applied.
     * @return DbSelect For fluent interface
     */
    public function updateSelect(Select $select)
    {
        $this->select = $select;
        return $this;
    }
}
