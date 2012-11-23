### Example for znZendExcerpt

```php
<!-- In view script -->
<?php
$text = 'The quick brown <!--more-->fox jumps over the lazy old dog.';
echo $this->znZendExcerpt($text) . '<br /><br />';

$text = 'The quick brown fox jumps over the lazy old dog.';
echo $this->znZendExcerpt($text, 5, '...read more', 'http://intzone.com');
?>
```
_BECOMES_
The quick brown<br /><br />
The quick brown fox <a href="http://intzone.com">...read more</a>