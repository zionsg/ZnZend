<?php
/**
 * ZnZend
 *
 * @author Zion Ng <zion@intzone.com>
 * @link   http://github.com/zionsg/ZnZend for canonical source repository
 */

namespace ZnZend\Mvc\Controller\Plugin;

use Zend\Db\Sql\Select;
use Zend\Db\Sql\Where;
use Zend\Mvc\Controller\Plugin\AbstractPlugin;
use Zend\Paginator\Paginator;
use ZnZend\Paginator\Adapter\DbSelect;
use ZnZend\Mvc\Exception;

/**
 * Controller plugin to update Paginator (DbSelect adapter) with params from jQuery DataTables
 *
 * Params is based on version 1.9.4 (23 Sep 2012) of the DataTables plugin.
 *
 * @link http://www.datatables.net/ for info on jQuery DataTables plugin
 */
class ZnZendDataTables extends AbstractPlugin
{
    /**
     * Updates the Select object in a Paginator's DbSelect adapter with params from jQuery DataTables plugin
     *
     * Note that the global search filter is not processed, only those for the individual columns.
     *
     * @param Paginator $paginator         Must use \ZnZend\Paginator\Adapter\DbSelect or an adapter that implements
     *                                     a getSelect() method to retrieve the Select object.
     * @param array     $dataTablesParams  Params passed to server by jQuery DataTables plugin
     *                                     (@link http://www.datatables.net/usage/server-side)
     *                                     The getters (for the result set prototype in $paginator) used for each
     *                                     column MUST BE SET via 'aoColumns' or 'aoColumnDefs' using 'sName'.
     *                                     'sName' can be set to null for non-data columns (eg. 'Edit Record').
     *                                     Example as follows:
     *                                         $('#example').dataTable({
     *                                             'bProcessing': true,
     *                                             'bServerSide': true,
     *                                             'sServerMethod': 'POST',
     *                                             'sAjaxSource': 'process.php',
     *                                             'aoColumnDefs': [
     *                                                 { 'aTargets': [0], 'sName': 'getId' },
     *                                                 { 'aTargets': [1], 'sName': 'getFullName' },
     *                                                 {
     *                                                   'aTargets': [2],
     *                                                   'sName': null,
     *                                                   'sDefaultContent': '<a href="" class="editrec">Edit</a>'
     *                                                 }
     *                                             ]
     *                                         });
     * @param array     $mapGettersColumns Key-value pairs mapping the getters (for the result set prototype
     *                                     in $paginator) to the database column names, to be used to modify Select.
     *                                     The array should ideally be provided via a method in the entity rather
     *                                     than being exposed/hardcoded in the controller action.
     *                                     Example as follows:
     *                                     array(
     *                                         // property $p->person_id => `person_id` column in database table
     *                                         'person_id' => 'person_id',
     *                                         // method $p->getId() => `person_id` column in database table
     *                                         'getId' => 'person_id',
     *                                         // method $p->getFullName() => SQL expression
     *                                         'getFullName' => "CONCAT('person_firstname, ' ', person_lastname)",
     *                                     )
     * @throws Exception\InvalidArgumentException
     * @return array Contains all parameters for returning to DataTables plugin
     */
    public function __invoke(Paginator $paginator, array $dataTablesParams, array $mapGettersColumns)
    {
        $adapter = $paginator->getAdapter();

        if (!method_exists($adapter, 'getSelect') || !(($select = $adapter->getSelect()) instanceof Select)) {
            throw new Exception\InvalidArgumentException(
                get_class($adapter) . ' does not implement getSelect() method to retrieve \Zend\Db\Sql\Select object'
            );
        }

        // 'sColumns' is used to pass in the names of the getters used for each column
        $columnGetters = explode(',', $dataTablesParams['sColumns']);

        // Column sorting
        for ($i = 0; $i < (int) $dataTablesParams['iSortingCols']; $i++) {
            $dataColumn = $dataTablesParams['iSortCol_' . $i];
            if ('false' == $dataTablesParams['bSortable_' . $i]) {
                continue;
            }

            $getter = $columnGetters[$dataColumn];
            if (empty($mapGettersColumns[$getter])) {
                continue;
            }

            $column = $mapGettersColumns[$getter];
            $select->order($column . ' ' . strtoupper($dataTablesParams['sSortDir_' . $i]));
        }

        // Column filtering
        $where = new Where();
        for ($i = 0; $i < (int) $dataTablesParams['iColumns']; $i++) {
            $searchText = $dataTablesParams['sSearch_' . $i];
            if (empty($searchText) || 'false' == $dataTablesParams['bSearchable_' . $i]) {
                continue;
            }

            $getter = $columnGetters[$i];
            if (empty($mapGettersColumns[$getter])) {
                continue;
            }
            $column = $mapGettersColumns[$getter];

            if ('false' == $dataTablesParams['bRegex_' . $i]) {
                // Use LIKE
                // like() not used in case $column is an expression and everything gets quoted
                $where->expression("{$column} LIKE ?", "%{$searchText}%");
            } else {
                // Use REGEXP
                $where->expression("{$column} REGEXP ?", $searchText);
            }
        }
        $select->where($where);

        $adapter->updateSelect($select);
        $filteredPaginator = new Paginator($adapter);

        $itemCountPerPage = (int) $dataTablesParams['iDisplayLength'];
        $itemStart = (int) $dataTablesParams['iDisplayStart'];
        $page = (int) ceil(($itemStart + 1) / $itemCountPerPage);
        $filteredPaginator->setItemCountPerPage($itemCountPerPage)
                          ->setCurrentPageNumber($page);

        // Construct data for each row and column
        $aaData = array();
        foreach ($filteredPaginator as $row) {
            if (false === $row) {
                break;
            }
            $rowRender = array();
            for ($i = 0; $i < $dataTablesParams['iColumns']; $i++) {
                // Getter may be null, empty, a method of $row or property of $row
                $getter = $columnGetters[$i];
                if ('null' == $getter || empty($getter)){
                    $value = null;
                } elseif (is_callable(array($row, $getter))) {
                    // method_exists() not used as it returns true for private methods which are not callable
                    $value = $row->$getter();
                } elseif (isset($row->$getter)) {
                    // Property
                    $value = $row->$getter;
                } else {
                    $value = null;
                }

                $rowRender[] = $value;
            }
            $aaData[] = $rowRender;
        }

        // Return expected parameters for DataTables plugin
        return array(
            'sEcho' => (int) $dataTablesParams['sEcho'],
            'iTotalRecords' => $paginator->getTotalItemCount(),
            'iTotalDisplayRecords' => $filteredPaginator->getTotalItemCount(),
            'aaData' => $aaData,
        );

    } // end function __invoke
}
