<?php
/**
 * ZnZend
 *
 * @author Zion Ng <zion@intzone.com>
 * @link   [Source] http://github.com/zionsg/ZnZend
 * @since  2012-11-23T23:00+08:00
 */
namespace ZnZendTest\View\Helper;

use PHPUnit_Framework_TestCase as TestCase;
use ZnZend\View\Helper\ZnZendFormatBytes;

/**
 * Tests ZnZend\View\Helper\ZnZendFormatBytes
 */
class ZnZendFormatBytesTest extends TestCase
{
    /**
     * Helper instance
     *
     * @var ZnZendFormatBytes
     */
    protected $helper;

    /**
     * Prepares the environment before running a test.
     */
    public function setUp()
    {
        // Constructor has no arguments
        $this->helper = new ZnZendFormatBytes();
    }

    /**
     * Cleans up the environment after running a test.
     */
    protected function tearDown()
    {
        unset($this->helper);
    }

    public function testInvokeWithOneKilobyte()
    {
        $helper = $this->helper;
        $value = 1024;
        $expected = '1.00 KiB';
        $actual = $helper($value);
        $this->assertEquals($expected, $actual);
    }

}
