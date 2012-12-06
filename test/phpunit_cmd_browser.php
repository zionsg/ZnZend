<?php
/**
 * Runs PHPUnit command in browser for ZnZend module tests
 *
 * Useful if there is no access to commandline, eg. restricted permissions
 * Assuming the contents of ZnZend module are in <webroot>/zf2app/vendor/ZnZend,
 * and this file resides in ZnZend/test, just type the following line in the browser:
 *     http://localhost/zf2app/vendor/ZnZend/test/phpunit_cmd_browser.php
 *
 * @author Zion Ng <zion@intzone.com>
 * @link   [Source] http://github.com/zionsg/ZnZend
 * @since  2012-12-04T13:00+08:00
 */
 
// Assumes PHPUnit has been installed via Composer and is in include path 
require_once 'PHPUnit/vendor/autoload.php'; 
$command = new PHPUnit_TextUI_Command;
?>
<pre>
  <?php $command->run(array(), true); // output result in <pre> tags ?>
</pre>
