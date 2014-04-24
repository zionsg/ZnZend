<?php
/**
 * ZnZend
 *
 * @author Zion Ng <zion@intzone.com>
 * @link   http://github.com/zionsg/ZnZend for canonical source repository
 */

namespace ZnZend\Mvc\Controller\Plugin;

use DateTime;
use Zend\Db\Sql\Select;
use Zend\Db\Sql\Where;
use Zend\Mvc\Controller\Plugin\AbstractPlugin;
use Zend\Paginator\Paginator;
use ZnZend\Paginator\Adapter\DbSelect;
use ZnZend\Mvc\Exception;

/**
 * Controller plugin to update Paginator (that uses DbSelect adapter) with params from jQuery DataTables
 *
 * Params are based on version 1.9.4 (23 Sep 2012) of the DataTables plugin.
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
     * @param Paginator $paginator         Must use \ZnZend\Paginator\Adapter\DbSelect which has getSelect() to retrieve
     *                                     Select object and updateSelect() to update Select object
     * @param array     $dataTablesParams  Parameters sent to server by jQuery DataTables plugin
     *                                     (@link http://www.datatables.net/usage/server-side)
     *                                     The getters (for the result set prototype in $paginator) used for each
     *                                     column MUST BE SET via 'aoColumns' or 'aoColumnDefs' using 'sName'.
     *                                     'sName' can be set to null if no data is to be fetched for that column.
     *                                     Example as follows:
     *                                       $('#myTable').dataTable({
     *                                           'bProcessing': true,
     *                                           'bServerSide': true,
     *                                           'sServerMethod': 'POST',
     *                                           'sAjaxSource': 'process.php',
     *                                           'aoColumnDefs': [
     *                                               { 'aTargets': [0], 'sName': 'getId' },
     *                                               { 'aTargets': [1], 'sName': 'getFullName' },
     *                                               {
     *                                                 'aTargets': [2],
     *                                                 'sName': null, // no data to be fetched
     *                                                 'bSortable': false,
     *                                                 'mRender': function (data, type, full) {
     *                                                     // use data from other columns to create link
     *                                                     url = 'edit.php?id=' + full[0];
     *                                                     title = 'Edit person named: ' + full[1];
     *                                                     return '<a href="' + url
     *                                                          + '" title="' + title + '">Edit</a>';
     *                                                 }
     *                                               }
     *                                           ]
     *                                       });
     * @param array     $mapGettersColumns Key-value pairs mapping the getters (for the result set prototype
     *                                     in $paginator) to the database column names, to be used to modify Select.
     *                                     The array should ideally be provided via a method in the entity rather
     *                                     than being exposed/hardcoded in the controller action.
     *                                     Non-string values such as the boolean values which may be returned by
     *                                     ZnZend\Db\AbstractEntity::mapGettersColumns() will be ignored.
     *                                     Example as follows:
     *                                     array(
     *                                         // property $p->person_id => `person_id` column in database table
     *                                         'person_id' => 'person_id',
     *                                         // method $p->getId() => `person_id` column in database table
     *                                         'getId' => 'person_id',
     *                                         // method $p->getFullName() => SQL expression
     *                                         'getFullName' => "CONCAT('person_firstname, ' ', person_lastname)",
     *                                     )
     * @param  bool     $returnPaginator   Default = false. If true, updated Paginator is returned under 'paginator'
     *                                     key in the array. This allows the controller action to fully control the
     *                                     rendering of the HTML table using view scripts, as opposed to customising
     *                                     mRender for each column without access to the actual PHP object.
     *                                     Example in controller ($result being the returned array):
     *                                       $viewModel = new ViewModel();
     *                                       $viewModel->setTerminal(true)
     *                                                 ->setTemplate('module/controller/action')
     *                                                 ->setVariables(array('paginator' => $result['paginator']));
     *                                       $result['html'] =
     *                                           $this->getServiceLocator()->get('ViewRenderer')->render($viewModel);
     *                                       unset($result['paginator']); // do not send paginator to the jQuery plugin
     *                                       return new \Zend\View\Model\JsonModel(array('result' => $result));
     *                                     Corresponding example for DataTables plugin:
     *                                       $('#myTable').dataTable({
     *                                           'bProcessing': true,
     *                                           'bServerSide': true,
     *                                           'sServerMethod': 'POST',
     *                                           'sAjaxSource': 'process.php',
     *                                           'fnServerData': function (sSource, aoData, fnCallback, oSettings) {
     *                                               oSettings.jqXHR = $.ajax({
     *                                                   'type': 'POST',
     *                                                   'url': sSource,
     *                                                   'data': aoData,
     *                                                   'dataType': 'json',
     *                                                   'success': function (json) {
     *                                                       fnCallback(json); // default behaviour
     *                                                       $('#myTable').html(json.html); // update entire table
     *                                                   }
     *                                               });
     *                                           }
     *                                       });
     * @throws Exception\InvalidArgumentException
     * @return array Contains parameters for returning to DataTables plugin
     *               (@link http://www.datatables.net/usage/server-side)
     */
    public function __invoke(Paginator $paginator,
                             array $dataTablesParams,
                             array $mapGettersColumns,
                             $returnPaginator = false
    ) {
        // The adapter and Select must be cloned to prevent modification of the original
        $adapter = clone ($paginator->getAdapter());

        if ($adapter instanceof DbSelect) {
            $select = $adapter->getSelect();
        } else {
            throw new Exception\InvalidArgumentException(
                get_class($adapter) . ' is not an instance of ZnZend\Paginator\Adapter\DbSelect'
            );
        }

        // 'sColumns' is used to pass in the names of the getters used for each column
        $columnGetters = explode(',', $dataTablesParams['sColumns']);

        // Column sorting - must precede the existing ORDER BY clause
        $orders = $select->getRawState(Select::ORDER);
        $select->reset(Select::ORDER);
        for ($i = 0; $i < (int) $dataTablesParams['iSortingCols']; $i++) {
            $dataColumn = (int) $dataTablesParams['iSortCol_' . $i];
            if ('false' == $dataTablesParams['bSortable_' . $dataColumn]) {
                continue;
            }

            $getter = $columnGetters[$dataColumn];
            if (empty($mapGettersColumns[$getter])) {
                continue;
            }

            $column = $mapGettersColumns[$getter];
            if (is_string($column)) {
                $select->order($column . ' ' . strtoupper($dataTablesParams['sSortDir_' . $i]));
            }
        }
        // Append original order by iteration so that the keys will not upset precedence
        foreach ($orders as $order) {
            $select->order($order);
        }

        // Column filtering
        $where = $select->where; // build upon existing Where clause
        for ($i = 0; $i < (int) $dataTablesParams['iColumns']; $i++) {
            $searchText = $dataTablesParams['sSearch_' . $i];
            if ('' == $searchText || 'false' == $dataTablesParams['bSearchable_' . $i]) {
                continue;
            }

            $getter = $columnGetters[$i];
            if (empty($mapGettersColumns[$getter])) {
                continue;
            }
            $column = $mapGettersColumns[$getter];

            if (!is_string($column)) {
                continue;
            }

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

        // Paging
        $itemCountPerPage = (int) $dataTablesParams['iDisplayLength'];
        $itemStart = (int) $dataTablesParams['iDisplayStart'];
        $page = (int) ceil(($itemStart + 1) / $itemCountPerPage);
        $filteredPaginator->setItemCountPerPage($itemCountPerPage)
                          ->setCurrentPageNumber($page);

        // Construct data for each row and column for current page
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
                    $value = $row->$getter();
                } elseif (isset($row->$getter)) {
                    // Property
                    $value = $row->$getter;
                } else {
                    $value = null;
                }

                // Value has to be a string as Javascript would not know how to handle PHP object
                if ($value instanceof DateTime) {
                    $value = $value->format('c');
                }
                $rowRender[] = nl2br((string) $value); // convert newlines to br for viewing in HTML table
            }
            $aaData[] = $rowRender;
        }

        // Params to return to DataTables plugin
        $returnParams = array(
            'sEcho' => (int) $dataTablesParams['sEcho'],
            'iTotalRecords' => $paginator->getTotalItemCount(),
            'iTotalDisplayRecords' => $filteredPaginator->getTotalItemCount(),
            'aaData' => $aaData,
        );
        if ($returnPaginator) {
            $returnParams['paginator'] = $filteredPaginator;
        }

        return $returnParams;
    } // end function __invoke
}
