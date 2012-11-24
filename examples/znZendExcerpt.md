### Example for znZendExcerpt

```php
<!-- In view script -->
<?php
$text = 'The quick brown <!--more-->fox jumps over the lazy old dog.';
echo $this->znZendExcerpt($text) . '<br />';

$text = 'The quick brown fox jumps over the lazy old dog.';
echo $this->znZendExcerpt($text, 5, '...read more', 'http://intzone.com', '_blank');
?>
```
_BECOMES_

The quick brown<br />
The quick brown fox jumps <a target="_blank" href="http://intzone.com">...read more</a>