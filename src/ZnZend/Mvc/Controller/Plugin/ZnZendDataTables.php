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
use ZnZend\Mvc\Controller\Exception;

/**
 * Controller plugin to update Paginator (DbSelect) with params from jQuery DataTables
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
     *                                     a getSelect() method to return the Select object.
     * @param array     $dataTablesParams  Params passed to server by jQuery DataTables plugin
     *                                     (@link http://www.datatables.net/usage/server-side)
     *                                     The getters (for the result set prototype in $paginator) used for each
     *                                     column MUST BE SET via 'aoColumns' using 'sName'. Example as follows:
     *                                     $('#example').dataTable( {
     *                                         'bProcessing': true,
     *                                         'bServerSide': true,
     *                                         'sServerMethod': 'POST',
     *                                         'sAjaxSource': 'process.php',
     *                                         'aoColumns': [
     *                                             { 'sName': 'getFirstName' },
     *                                             { 'sName': 'getLastName' }
     *                                         ]
     *                                     });
     * @param array     $mapGettersColumns Key-value pairs mapping the getters for the result set prototype
     *                                     in $paginator to the database column names, which can be provided
     *                                     via a method in the result set prototype or entity class.
     *                                     Eg: array('getTimestamp' => 'log_timestamp', 'getDescription' => 'log_text')
     * @throws Exception\InvalidArgumentException
     * @return array Contains all parameters for returning to DataTables plugin
     */
    public function __invoke(Paginator $paginator, array $dataTablesParams, array $mapGettersColumns)
    {
        $adapter = $paginator->getAdapter();
        if (!$adapter instanceof DbSelect) {
            throw new Exception\InvalidArgumentException(
                'Paginator must use \ZnZend\Paginator\Adapter\DbSelect or an adapter that implements getSelect()'
                . ' method to to get Select object'
            );
        }
        $select = $adapter->getSelect();

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

            $getter = $columnGetters[$dataColumn];
            if (empty($mapGettersColumns[$getter])) {
                continue;
            }
            $column = $mapGettersColumns[$getter];

            if ('false' == $dataTablesParams['bRegex_' . $i]) {
                // Use LIKE
                $where->like($column, '%' . $searchText . '%');
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

        $aaData = array();
        foreach ($filteredPaginator as $row) {
            $rowRender = array();
            for ($i = 0; $i < $dataTablesParams['iColumns']; $i++) {
                $rowRender[] = $row->{$columnGetters[$i]}();
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
