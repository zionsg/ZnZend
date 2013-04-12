### Example for `znZendTimeRange` View Helper

```php
<!-- In view script -->
<?php
$startTime = '2012-11-24 00:00:00';
$endTime = '2012-11-24 02:00:00';
echo $this->znZendFormatTimeRange('g:i a', 'g:i a', ' - g:i a', $startTime, $startTime) . '<br />';
echo $this->znZendFormatTimeRange('g:i a', 'g:i a', ' - g:i a', $startTime, $endTime) . '<br />';

// Ignore midnight
var_dump($this->znZendFormatTimeRange('g:i a', 'g:i a', ' - g:i a', $startTime, $endTime, true));

// End time is midnight and view helper set to ignore midnight
$startTime = '2012-11-24 22:30:00';
$endTime = '2012-11-24 00:00:00';
echo $this->znZendFormatTimeRange('g:i a', 'g:i a', ' - g:i a', $startTime, $endTime, true);
?>
```
_BECOMES_

12:00 am<br />
12:00 am - 2:00 am<br />
<pre class='xdebug-var-dump' dir='ltr'>
<small>string</small> <font color='#cc0000'>''</font> <i>(length=0)</i>
</pre>
10:30 pm
