### Example for `znZendDataTables` Controller Plugin

```php
// Result set prototype used in Paginator
namespace Web\Model;

use ZnZend\Model\AbstractEntity;

class Person extends AbstractEntity
{
    protected static $mapGettersColumns = array(
        'getId' => 'person_id',
        'getFullName' => "CONCAT(person_firstname, ' ', person_lastname)",
    );

    public function getId()
    {
        return $this->get('person_id');
    }

    public function getFullName()
    {
        return $this->get('person_firstname') . ' ' . $this->get('person_lastname');
    }
}
```

```php
// Table gateway for retrieving Persons
namespace Web\Model;

use ZnZend\Model\AbstractTable;

class PersonTable extends AbstractTable
{
    protected $table = 'person';
    protected $resultSetClass = '\Application\Model\Person';
    protected $activeRowState = array('per_isdeleted' => 0);
    protected $deletedRowState = array('per_isdeleted' => 1);
}
```

```php
// In controller
namespace Web\Controller;

use Web\Model\Person;
use Web\Model\PersonTable;
use Zend\Mvc\Controller\AbstractActionController;
use Zend\View\Model\ViewModel;
use Zend\View\Model\JsonModel;

class IndexController extends AbstractActionController
{
    public function indexAction()
    {
        if (!$this->getRequest()->isPost()) {
            return new ViewModel();
        }

        $personTable = new PersonTable();
        $persons = $personTable->fetchAll(); // returns Paginator

        $postData = $this->params()->fromPost();
        $result = $this->znZendDataTables(
            $persons,
            $postData,
            Person::mapGettersColumns()
        );

        // ViewJsonStrategy must be added in module.config.php for JsonModel to work
        return new JsonModel($result);
    }
}
```

```php
<!-- In view script for indexAction() -->
<link href="//ajax.aspnetcdn.com/ajax/jquery.dataTables/1.9.4/css/jquery.dataTables.css" media="screen" rel="stylesheet" type="text/css">
<style>
  /* Hide global search field for 'example' table */
  #example_filter { display: none; }
</style>

<div style="margin:auto; width:50%;">
  <table id="example" border="0" cellpadding="0" cellspacing="0" width="100%">
    <thead>
      <tr>
        <th>ID</th>
        <th>Full Name</th>
        <th>Actions</th>
      </tr>
    </thead>

    <tbody>
    </tbody>

    <tfoot>
      <tr>
        <td align="center" colspan="2"><small><em>Press ENTER to filter after keying in search text</em></small></td>
      </tr>
      <tr>
        <th><input type="text" name="search_id" placeholder="Search ID" /></th>
        <th><input type="text" name="search_fullname" placeholder="Search full name" /></th>
        <th></th>
      </tr>
    </tfoot>
  </table>
</div>

<script src="//ajax.googleapis.com/ajax/libs/jquery/1.8.3/jquery.min.js"></script>
<script src="//ajax.aspnetcdn.com/ajax/jquery.dataTables/1.9.4/jquery.dataTables.min.js"></script>
<script>
  var dataTablesScript = function() {
      $(document).ready(function() {
          var oTable = $('#example').dataTable({
              'iDisplayLength': 25,
              'aLengthMenu': [[25, 50, 100, -1], [25, 50, 100, 'All']],
              'sPaginationType': 'full_numbers',
              'bProcessing': false,
              'bDeferRender': true,
              'bServerSide': true,
              'sServerMethod': 'POST',
              'sAjaxSource': '<?php echo $this->url('web/wildcard', array('action' => 'index')); ?>',
              'fnServerParams': function (aoData) {
                  aoData.push({'submit': 'DataTables'});
              },
              'aoColumnDefs': [
                  { 'aTargets': [0], 'sName': 'getId' }, // getter for Person to get value for 1st column
                  { 'aTargets': [1], 'sName': 'getFullName' },
                  {
                    'aTargets': [2],
                    'sName': null, // no data to be fetched
                    'bSortable': false,
                    'mRender': function (data, type, full) {
                        // use data from other columns to create link
                        url = 'edit.php?id=' + full[0]; // from aTarget[0], getId
                        title = 'Edit person named: ' + full[1]; // from aTarget[1], getFullName
                        return '<a href="' + url + '" title="' + title + '">Edit</a>';
                    }
                  }
              ]
          });

          $('tfoot input').keyup(function (event) {
              // Filter only when ENTER is pressed, not for every keystroke
              if (event.which != 13) {
                  return;
              }

              // Filter on all columns where text is entered
              $('tfoot input').each(function (index) {
                  oTable.fnFilter(this.value, index);
              });
          });
      });
  }();
</script>
```

_BECOMES_

![Screenshot of result](https://raw.github.com/zionsg/ZnZend/master/examples/controller_plugin/znZendDataTables_screenshot.png)
