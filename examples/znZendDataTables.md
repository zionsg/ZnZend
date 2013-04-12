### Example for `znZendDataTables` Controller Plugin

Note: The return from processAction() has not been tested to work yet.

```php
<!-- In controller -->
namespace Web\Controller;

use Zend\Mvc\Controller\AbstractActionController;
use Zend\View\Model\JsonModel;

class IndexController extends AbstractActionController
{
    public function indexAction()
    {
        // ...
    }

    public function processAction()
    {
        // Assume $rows is a Paginator result set with Person objects
        $postParams = $this->params()->fromPost();
        $paginator = $this->znZendDataTables(
            $postParams,
            $rows,
            array(
                'getFirstName' => 'per_firstname',
                'getLastName' => 'per_lastname',
            )
        );

        return new JsonModel(array(
            'iTotalRecords' => 0,
            'iTotalDisplayRecords' => 0,
            'sEcho' => 0,
            'aaData' => $paginator,
        ));
    }
}
```

```php
<!-- In view script for indexAction() -->
<link href="http://ajax.aspnetcdn.com/ajax/jquery.dataTables/1.9.4/css/jquery.dataTables.css" media="screen" rel="stylesheet" type="text/css">
<style>
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
      <tr align="center">
        <td>Alpha</td>
        <td>Delta</th>
      </tr>

      <tr align="center">
        <td>Alpha</td>
        <td>Omega</th>
      </tr>

      <tr align="center">
        <td>Beta</td>
        <td>Psi</td>
      </tr>
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
          "bProcessing": false,
          "bServerSide": true,
          "sServerMethod": 'POST',
          "sAjaxSource": '<?php echo $this->url('web/wildcard', array('action' => 'process')); ?>',
          "aoColumns": [
            { "mData": 'getFirstName' },
            { "mData": 'getLastName' },
          ],
          'iDisplayLength': 25,
          'aLengthMenu': [[25, 50, 100, -1], [25, 50, 100, 'All']],
          "sPaginationType": 'full_numbers'
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
