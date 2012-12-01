### Example for znZendFormatBytes

```php
<!-- In view script -->
<?php
$value = 20000000;
echo $this->znZendFormatBytes($value) . '<br />';
echo $this->znZendFormatBytes($value, null, true);
?>
```
_BECOMES_

19.07 MiB<br />
1048576