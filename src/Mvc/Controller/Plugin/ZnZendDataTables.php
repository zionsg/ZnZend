<?php
/**
 * ZnZend
 *
 * @link https://github.com/zionsg/ZnZend for canonical source repository
 */

namespace ZnZend\Mvc\Controller\Plugin;

use DateTime;
use Zend\Db\Sql\Expression;
use Zend\Db\Sql\Predicate;
use Zend\Db\Sql\Select;
use Zend\Db\Sql\Where;
use Zend\Mvc\Controller\Plugin\AbstractPlugin;
use Zend\Paginator\Paginator;
use ZnZend\Paginator\Adapter\DbSelect;
use ZnZend\Mvc\Exception;

/**
 * Controller plugin to update Paginator (that uses DbSelect adapter) with params from jQuery DataTables plugin
 *
 * Compatible with versions 1.10.7 (30 Apr 2015) and 1.9.4 (23 Sep 2012) of DataTables.
 *
 * @link http://www.datatables.net/ for info on DataTables
 * @link http://datatables.net/upgrade/1.10-convert for converting of parameter names from 1.9 to 1.10
 */
class ZnZendDataTables extends AbstractPlugin
{
    /**
     * @var Paginator
     */
    protected $paginator = null;

    /**
     * @var DbSelect
     */
    protected $adapter = null;

    /**
     * @var Select
     */
    protected $select = null;

    /**
     * Params sent by DataTables
     *
     * @var array
     */
    protected $params = [];

    /**
     * Name of property for specifying search operator in params sent by DataTables (for 1.10 only)
     *
     * DataTables has yet to implement this hence the default value may change.
     * Assuming the property name is 'operator', it will be found in $params as such:
     *     array(
     *        'search' => array('value' => '', 'regex' => true, 'operator' => 'REGEXP'),
     *        'columns' => array(
     *            array('search' => array('value' => '', 'regex' => false, 'operator' => 'LIKE'),
     *        ),
     *        ...
     *     )
     *
     * @var array
     */
    protected $searchOperatorName = 'operator';

    /**
     * Mapping of columns to all getters
     *
     * @var array
     */
    protected $map = [];

    /**
     * Mapping of columns to getters used in global search
     *
     * @var array
     */
    protected $searchMap = [];

    /**
     * Whether to return updated Paginator
     *
     * @var bool
     */
    protected $returnPaginator = false;

    /**
     * Invoke the appropriate handler based on the version of DataTables plugin
     *
     * Version check is based on the counter name in the params sent by DataTables, 'sEcho' for 1.9, 'draw' for 1.10.
     * The handler will update the Select object in a Paginator's DbSelect adapter with params from DataTables.
     *
     * @param Paginator $paginator         Must use \ZnZend\Paginator\Adapter\DbSelect which has getSelect() to
     *                                     retrieve the Select object and updateSelect() to update Select object
     * @param array     $dataTablesParams  Parameters sent to server by DataTables
     *                                     (@link http://legacy.datatables.net/usage/server-side for 1.9)
     *                                     (@link http://datatables.net/manual/server-side for 1.10)
     *                                     The getters (for the result set prototype in $paginator) used for each
     *                                     column MUST BE SET via the name property in the column definitions.
     *                                     Getters are used instead of accessing public properties in the entity.
     *                                     Set the name property to null if no data is to be fetched for that column.
     *                                     Search operator will be used if set (@see $searchOperatorName) - 1.10 only
     *                                     Example for DataTables 1.9:
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
     *                                     Example for DataTables 1.10:
     *                                       $('#myTable').DataTable({
     *                                           'processing': true,
     *                                           'serverSide': true,
     *                                           'ajax': {
     *                                               'url': 'process.php',
     *                                               'type': 'POST',
     *                                               'data': function (data) { // modify request data before sending
     *                                                   data.search.operator = 'REGEXP'; // add global search operator
     *                                                   // Add search operator for columns, eg. LIKE, =, >
     *                                                   $.each(data.columns, function (index, value) {
     *                                                       data.columns[index].search.operator = 'LIKE';
     *                                                   });
     *                                               }
     *                                           },
     *                                           'columnDefs': [
     *                                               { 'targets': 0, 'name': 'getId' },
     *                                               { 'targets': 1, 'name': 'getFullName' },
     *                                               {
     *                                                 'targets': 2,
     *                                                 'name': null, // no data to be fetched
     *                                                 'orderable': false,
     *                                                 'render': function (data, type, row, meta) {
     *                                                     // use data from other columns to create link
     *                                                     url = 'edit.php?id=' + row[0];
     *                                                     title = 'Edit person named: ' + row[1];
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
     *                                     \ZnZend\Db\AbstractEntity::mapGettersColumns() will be ignored.
     *                                     Example as follows:
     *                                     array(
     *                                         // property $p->person_id => `person_id` column in database table
     *                                         'person_id' => 'person_id',
     *                                         // method $p->getId() => `person_id` column in database table
     *                                         'getId' => 'person_id',
     *                                         // method $p->getFullName() => SQL expression
     *                                         'getFullName' => "CONCAT('person_firstname, ' ', person_lastname)",
     *                                     )
     * @param  array    $searchGetters     Default = []. List of getters whose columns (as mapped in
     *                                     $mapGettersColumns) will be used in the global search. If not provided,
     *                                     the global search filter is applied on the entire $mapGettersColumns.
     * @param  bool     $returnPaginator   Default = false. If true, updated Paginator is returned under 'paginator'
     *                                     key in the array. This allows the controller action to fully control the
     *                                     rendering of the HTML table using view scripts, as opposed to customising
     *                                     the rendering for each column without access to the actual PHP object.
     *                                     Example in controller ($result being the returned array):
     *                                       $viewModel = new ViewModel();
     *                                       $viewModel->setTerminal(true)
     *                                                 ->setTemplate('module/controller/action')
     *                                                 ->setVariables(array('paginator' => $result['paginator']));
     *                                       $result['html'] =
     *                                           $this->getServiceLocator()->get('ViewRenderer')->render($viewModel);
     *                                       unset($result['paginator']); // do not send paginator to the jQuery plugin
     *                                       // return $result as JSON - $result['html'] will become json.html
     *                                       return new \Zend\View\Model\JsonModel(array('result' => $result));
     *                                     Corresponding example for DataTables 1.9:
     *                                       $('#myTable').DataTable({
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
     *                                     Corresponding example for DataTables 1.10:
     *                                       $('#myTable').dataTable({
     *                                           'processing': true,
     *                                           'serverSide': true,
     *                                           'ajax': {
     *                                               'url': 'process.php',
     *                                               'type': 'POST',
     *                                               'dataSrc': function (json) {
     *                                                   $('#myTable').html(json.html); // update entire table
     *                                               }
     *                                           }
     *                                       });
     * @throws Exception\InvalidArgumentException
     * @return array Contains parameters for returning to DataTables
     *               (@link http://legacy.datatables.net/usage/server-side for 1.9)
     *               (@link http://datatables.net/manual/server-side for 1.10)
     */
    public function __invoke(
        Paginator $paginator,
        array $dataTablesParams,
        array $mapGettersColumns,
        array $searchGetters = [],
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

        if (isset($dataTablesParams['sEcho'])) {
            $handler = 'legacyHandler';
        } elseif (isset($dataTablesParams['draw'])) {
            $handler = 'handler';
        } else {
            throw new Exception\InvalidArgumentException('Invalid parameters from jQuery DataTables plugin');
        }

        $this->paginator = $paginator;
        $this->adapter   = $adapter;
        $this->select    = $select;
        $this->params    = $dataTablesParams;
        $this->map       = $mapGettersColumns;
        $this->returnPaginator = $returnPaginator;

        if (! $searchGetters) {
            $this->searchMap = $this->map;
        } else {
            foreach ($searchGetters as $getter) {
                if (isset($this->map[$getter])) {
                    $this->searchMap[$getter] = $this->map[$getter];
                }
            }
        }

        return $this->$handler();
    }

    /**
     * Handler for DataTables 1.9
     *
     * @return array @link http://legacy.datatables.net/usage/server-side
     */
    protected function legacyHandler()
    {
        // 'sColumns' is used to pass in the names of the getters used for each column
        $columnGetters = explode(',', $this->params['sColumns']);

        // Column sorting - must precede the existing ORDER BY clause
        $orders = $this->select->getRawState(Select::ORDER);
        $this->select->reset(Select::ORDER);
        for ($i = 0; $i < (int) $this->params['iSortingCols']; $i++) {
            $dataColumn = (int) $this->params['iSortCol_' . $i];
            if ('false' == $this->params['bSortable_' . $dataColumn]) {
                continue;
            }

            $getter = $columnGetters[$dataColumn];
            if (empty($this->map[$getter])) {
                continue;
            }

            $column = $this->map[$getter];
            if (is_string($column)) {
                // Use Expression in case $column is an expression and gets quoted
                $this->select->order(new Expression($column . ' ' . strtoupper($this->params['sSortDir_' . $i])));
            }
        }
        // Append original order by iteration so that the keys will not upset precedence
        foreach ($orders as $order) {
            $this->select->order($order);
        }

        // Build upon existing HAVING clause (not WHERE clause as column aliases cannot be used)
        $having = $this->select->having;

        // Global search
        $searchText = $this->params['sSearch'];
        $searchRegex = $this->params['bRegex'];
        if ($searchText) {
            $globalHaving = new Where();
            foreach ($this->searchMap as $getter => $column) {
                if (is_string($column)) {
                    $globalHaving->orPredicate(new Predicate\Expression(
                        $column . ($searchRegex ? ' REGEXP ?' : ' LIKE %?%'),
                        $searchText
                    ));
                }
            }
            $having->andPredicate($globalHaving);
        }

        // Column filtering
        for ($i = 0; $i < (int) $this->params['iColumns']; $i++) {
            $searchText = $this->params['sSearch_' . $i];
            if ('' == $searchText || 'false' == $this->params['bSearchable_' . $i]) {
                continue;
            }

            $getter = $columnGetters[$i];
            if (empty($this->map[$getter])) {
                continue;
            }
            $column = $this->map[$getter];

            if (! is_string($column)) {
                continue;
            }

            if ('false' == $this->params['bRegex_' . $i]) {
                // Use LIKE
                // like() not used in case $column is an expression and everything gets quoted
                $having->expression("{$column} LIKE ?", "%{$searchText}%");
            } else {
                // Use REGEXP
                $having->expression("{$column} REGEXP ?", $searchText);
            }
        }
        $this->select->having($having);

        // Create new Paginator with updated Select
        $this->adapter->updateSelect($this->select);
        $filteredPaginator = new Paginator($this->adapter);

        // Paging
        $itemCountPerPage = (int) $this->params['iDisplayLength']; // -1 for all
        $itemStart = (int) $this->params['iDisplayStart'];
        $page = (int) ceil(($itemStart + 1) / $itemCountPerPage);
        $filteredPaginator->setItemCountPerPage($itemCountPerPage)
                          ->setCurrentPageNumber($page);

        // Construct data for each row and column for current page
        $aaData = [];
        foreach ($filteredPaginator as $row) {
            if (false === $row) {
                break;
            }
            $rowRender = [];
            for ($i = 0; $i < $this->params['iColumns']; $i++) {
                // Getter may be null, empty, a method of $row or property of $row
                $getter = $columnGetters[$i];
                if ('null' == $getter || empty($getter)) {
                    $value = null;
                } elseif (is_callable([$row, $getter])) {
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
        $returnParams = [
            'sEcho' => (int) $this->params['sEcho'],
            'iTotalRecords' => $this->paginator->getTotalItemCount(),
            'iTotalDisplayRecords' => $filteredPaginator->getTotalItemCount(),
            'aaData' => $aaData,
        ];
        if ($this->returnPaginator) {
            $returnParams['paginator'] = $filteredPaginator;
        }

        return $returnParams;
    }

    /**
     * Handler for DataTables 1.10
     *
     * Uses search operators if set (@see $searchOperatorName).
     *
     * @return array @link http://datatables.net/manual/server-side
     */
    protected function handler()
    {
        // 'name' key in 'columns' used to pass in the names of the getters used for each column
        $columnGetters = [];//explode(',', $this->params['columns']);
        $columnCnt = count($this->params['columns']);
        for ($i = 0; $i < $columnCnt; $i++) {
            $columnGetters[$i] = $this->params['columns'][$i]['name'];
        }

        // Column sorting - must precede the existing ORDER BY clause
        $orders = $this->select->getRawState(Select::ORDER);
        $this->select->reset(Select::ORDER);
        foreach ($this->params['order'] as $i => $orderParams) {
            $dataColumn = (int) $orderParams['column'];
            $getter = $columnGetters[$dataColumn];
            if (empty($this->map[$getter])) {
                continue;
            }

            $column = $this->map[$getter];
            if (is_string($column)) {
                // Use Expression in case $column is an expression and gets quoted
                $this->select->order(new Expression($column . ' ' . strtoupper($orderParams['dir'])));
            }
        }
        // Append original order by iteration so that the keys will not upset precedence
        foreach ($orders as $order) {
            $this->select->order($order);
        }

        // Build upon existing HAVING clause (not WHERE clause as column aliases cannot be used)
        $having = $this->select->having;

        // Global search
        $searchText = $this->params['search']['value'];
        $searchRegex = $this->params['search']['regex'];
        $searchOperator = empty($this->params['search'][$this->searchOperatorName])
                        ? ($searchRegex != 'false' ? 'REGEXP' : 'LIKE')
                        : $this->params['search'][$this->searchOperatorName];
        if ($searchText) {
            $globalHaving = new Where();
            $searchTextFormatted = ('LIKE' == $searchOperator ? "%{$searchText}%" : $searchText);

            foreach ($this->searchMap as $getter => $column) {
                if (is_string($column)) {
                    $globalHaving->orPredicate(new Predicate\Expression(
                        "{$column} {$searchOperator} ?",
                        $searchTextFormatted
                    ));
                }
            }
            $having->andPredicate($globalHaving);
        }

        // Column filtering
        for ($i = 0; $i < $columnCnt; $i++) {
            $searchText = $this->params['columns'][$i]['search']['value'];
            if ('' == $searchText || 'false' == $this->params['columns'][$i]['searchable']) {
                continue;
            }

            $getter = $columnGetters[$i];
            if (empty($this->map[$getter])) {
                continue;
            }
            $column = $this->map[$getter];

            if (! is_string($column)) {
                continue;
            }

            $searchRegex = $this->params['columns'][$i]['search']['regex'];
            $searchOperator = empty($this->params['columns'][$i]['search'][$this->searchOperatorName])
                            ? ($searchRegex != 'false' ? 'REGEXP' : 'LIKE')
                            : $this->params['columns'][$i]['search'][$this->searchOperatorName];

            // like() not used for LIKE operator in case $column is an expression and everything gets quoted
            $having->expression(
                "{$column} {$searchOperator} ?",
                ('LIKE' == $searchOperator ? "%{$searchText}%" : $searchText)
            );
        }
        $this->select->having($having);

        // Create new Paginator with updated Select
        $this->adapter->updateSelect($this->select);
        $filteredPaginator = new Paginator($this->adapter);

        // Paging
        $itemCountPerPage = (int) $this->params['length']; // -1 for all
        $itemStart = (int) $this->params['start'];
        $page = (int) ceil(($itemStart + 1) / $itemCountPerPage);
        $filteredPaginator->setItemCountPerPage($itemCountPerPage)
                          ->setCurrentPageNumber($page);

        // Construct data for each row and column for current page
        $data = [];
        foreach ($filteredPaginator as $row) {
            if (false === $row) {
                break;
            }
            $rowRender = [];
            for ($i = 0; $i < $columnCnt; $i++) {
                // Getter may be null, empty, a method of $row or property of $row
                $getter = $columnGetters[$i];
                if ('null' == $getter || empty($getter)) {
                    $value = null;
                } elseif (is_callable([$row, $getter])) {
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
            $data[] = $rowRender;
        }

        // Params to return to DataTables plugin
        $returnParams = [
            'draw' => (int) $this->params['draw'],
            'recordsTotal' => $this->paginator->getTotalItemCount(),
            'recordsFiltered' => $filteredPaginator->getTotalItemCount(),
            'data' => $data,
        ];
        if ($this->returnPaginator) {
            $returnParams['paginator'] = $filteredPaginator;
        }

        return $returnParams;
    }
}
