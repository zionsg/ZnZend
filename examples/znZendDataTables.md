### Example for `znZendDataTables` Controller Plugin

Note: The return from processAction() has not been tested to work yet.

```php
<!-- In result set prototype used in Paginator -->
namespace Web\Model;

use ZnZend\Model\AbstractEntity;

class Person extends AbstractEntity
{
    protected static $mapGettersColumns = array(
        'getFirstName' => 'per_firstname',
        'getLastName' => 'per_lastname',
    );

    public function getFirstName()
    {
        return $this->get('per_firstname');
    }

    public function getLastName()
    {
        return $this->get('per_lastname');
    }
}
```

```php
<!-- In controller -->
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
<link href="http://ajax.aspnetcdn.com/ajax/jquery.dataTables/1.9.4/css/jquery.dataTables.css" media="screen" rel="stylesheet" type="text/css">
<style>
  /* Hide global search field for 'example' table */
  #example_filter { display: none; }
</style>

<div style="margin:auto; width:50%;">
  <table id="example" border="0" cellpadding="0" cellspacing="0" width="100%">
    <thead>
      <tr>
        <th>First Name</th>
        <th>Last Name</th>
      </tr>
    </thead>

    <tbody>
    </tbody>

    <tfoot>
      <tr>
        <th><input type="text" name="search_firstname" value="Search first name" class="search_init"></th>
        <th><input type="text" name="search_lastname" value="Search last name" class="search_init"></th>
      </tr>
    </tfoot>
  </table>
</div>

<script src="//ajax.googleapis.com/ajax/libs/jquery/1.8.3/jquery.min.js"></script>
<script src="http://ajax.aspnetcdn.com/ajax/jquery.dataTables/1.9.4/jquery.dataTables.min.js"></script>
<script>
  $(document).ready(function() {
      var searchInitVals = new Array();

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
          'aoColumns': [
               { 'sName': 'getFirstName' }, // Getter used on Person to get value for first column
               { 'sName': 'getLastName' }
          ]
      });

      $('tfoot input').keyup(function () {
          // Filter on the column (the index) of this element
          oTable.fnFilter(this.value, $('tfoot input').index(this));
      });

      // Support functions to provide a little bit of 'user friendlyness' to the textboxes in the footer
      $('tfoot input').each(function (i) {
          searchInitVals[i] = this.value;
      });

      $('tfoot input').focus(function () {
          if ('search_init' == this.className) {
              this.className = '';
              this.value = '';
          }
      });

      $('tfoot input').blur(function (i) {
          if ('' == this.value) {
              this.className = 'search_init';
              this.value = searchInitVals[$('tfoot input').index(this)];
          }
      });
  });
</script>
```

_BECOMES_

![Screenshot of result](https://raw.github.com/zionsg/ZnZend/master/examples/znZendDataTables_screenshot.png)
