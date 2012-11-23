### Example for znZendFormatBytes

```php
<!-- In view script -->
<?php
$startDate = '2012-11-23 19:00:00';
$endDate = '2012-11-25 21:00:00';
echo $this->znZendFormatDateRange('d M Y', 'd M', ' - d M Y', $startDate, $startDate) . '<br />';
echo $this->znZendFormatDateRange('d M Y', 'd M', ' - d M Y', $startDate, $endDate);
?>
```
_BECOMES_

23 Nov 2012<br />
23 Nov - 25 Nov 2012