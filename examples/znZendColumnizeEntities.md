### Example for znZendColumnizeEntities

```php
<!-- In view script -->
<style>
  .center { text-align: center; }
  .padding { padding: 10px; }
</style>

<?php
$entities = array();
for ($i = 1; $i <= 8; $i++) {
    $obj = new stdClass();
    $obj->name = 'Object' . $i;
    $obj->pic = "thumb0{$i}.png";
    $entities[] = $obj;
}

$params = array(
    'cols' => 3,
    'entities' => $entities,
    'leftToRight' => true,
    'nameCallback' => function ($entity) { return $entity->name; },
    'remainderAlign' => 'center',
    'tdClass' => 'center padding',    
    'urlCallback' => function ($entity) { return 'http://intzone.com'; },
    // keys for drawing thumbnail
    'drawThumbnailBox' => true,
    'thumbnailCallback' => function ($entity) { return $entity->pic; },
    'thumbnailPath' => 'http://docs.intzone.com',
    'maxThumbnailHeight' => 60,
    'maxThumbnailWidth' => 90,
    'webRoot' => '',
);
$instance = new ZnZendColumnizeEntities();
echo $instance($params);
?>
```
_BECOMES_

<table id="" class="" cellspacing="0" cellpadding="0" width="100%">
<tr class="" style="background-color:white;">
<td style="text-align:center; padding:10px; background-color:white;" width="33%">
<table align="center" cellspacing="0" style="border:0;padding:0;background-color:inherit;" cellpadding="0" width="100%">
<tr style="border:0;"><td class="" width="90" height="60" align="center" valign="middle" style="border:0; padding:0;">
<a class="" target="" href="http://intzone.com">
<img class="" align="center" src="http://docs.intzone.com/thumb01.png" width="60" height="60" />
</a>
<div class=""><a class="" target="" href="http://intzone.com">
Object1
</a>
</div>

</td></tr>
</table>
</td>
<td style="text-align:center; padding:10px; background-color:white;" width="33%">
<table align="center" cellspacing="0" style="border:0;padding:0;" cellpadding="0" width="100%">
<tr style="border:0;"><td class="" width="90" height="60" align="center" valign="middle" style="border:0; padding:0;">
<a class="" target="" href="http://intzone.com">
<img class="" align="center" src="http://docs.intzone.com/thumb02.png" width="60" height="60" />
</a>
<div class=""><a class="" target="" href="http://intzone.com">
Object2
</a>
</div>

</td></tr>
</table>
</td>
<td style="text-align:center; padding:10px; background-color:white;" width="33%">
<table align="center" cellspacing="0" style="border:0;padding:0;" cellpadding="0" width="100%">
<tr style="border:0;"><td class="" width="90" height="60" align="center" valign="middle" style="border:0; padding:0;">
<a class="" target="" href="http://intzone.com">
<img class="" align="center" src="http://docs.intzone.com/thumb03.png" width="60" height="60" />
</a>
<div class=""><a class="" target="" href="http://intzone.com">
Object3
</a>
</div>

</td></tr>
</table>
</td>
</tr>
<tr class="">
<td style="text-align:center; padding:10px; background-color:white;" width="33%">
<table align="center" cellspacing="0" style="border:0;padding:0;" cellpadding="0" width="100%">
<tr style="border:0;"><td class="" width="90" height="60" align="center" valign="middle" style="border:0; padding:0;">
<a class="" target="" href="http://intzone.com">
<img class="" align="center" src="http://docs.intzone.com/thumb04.png" width="60" height="60" />
</a>
<div class=""><a class="" target="" href="http://intzone.com">
Object4
</a>
</div>

</td></tr>
</table>
</td>
<td style="text-align:center; padding:10px; background-color:white;" width="33%">
<table align="center" cellspacing="0" style="border:0;padding:0;" cellpadding="0" width="100%">
<tr style="border:0;"><td class="" width="90" height="60" align="center" valign="middle" style="border:0; padding:0;">
<a class="" target="" href="http://intzone.com">
<img class="" align="center" src="http://docs.intzone.com/thumb05.png" width="60" height="60" />
</a>
<div class=""><a class="" target="" href="http://intzone.com">
Object5
</a>
</div>

</td></tr>
</table>
</td>
<td style="text-align:center; padding:10px; background-color:white;" width="33%">
<table align="center" cellspacing="0" style="border:0;padding:0;" cellpadding="0" width="100%">
<tr style="border:0;"><td class="" width="90" height="60" align="center" valign="middle" style="border:0; padding:0;">
<a class="" target="" href="http://intzone.com">
<img class="" align="center" src="http://docs.intzone.com/thumb06.png" width="60" height="60" />
</a>
<div class=""><a class="" target="" href="http://intzone.com">
Object6
</a>
</div>

</td></tr>
</table>
</td>
</tr>
</table>
<table id="" class="" cellspacing="0" cellpadding="0" width="100%">
<tr class="">
<td style="text-align:center; padding:10px; background-color:white;" width="50%">
<table align="center" cellspacing="0" style="border:0;padding:0;" cellpadding="0" width="100%">
<tr style="border:0;"><td class="" width="90" height="60" align="center" valign="middle" style="border:0; padding:0;">
<a class="" target="" href="http://intzone.com">
<img class="" align="center" src="http://docs.intzone.com/thumb07.png" width="60" height="60" />
</a>
<div class=""><a class="" target="" href="http://intzone.com">
Object7
</a>
</div>

</td></tr>
</table>
</td>
<td style="text-align:center; padding:10px; background-color:white;" width="50%">
<table align="center" cellspacing="0" style="border:0;padding:0;" cellpadding="0" width="100%">
<tr style="border:0;"><td class="" width="90" height="60" align="center" valign="middle" style="border:0; padding:0;">
<a class="" target="" href="http://intzone.com">
<img class="" align="center" src="http://docs.intzone.com/thumb08.png" width="60" height="60" />
</a>
<div class=""><a class="" target="" href="http://intzone.com">
Object8
</a>
</div>

</td></tr>
</table>
</td>
</tr>
</table>

