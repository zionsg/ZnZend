<?php
/**
 * ZnZend
 *
 * @author Zion Ng <zion@intzone.com>
 * @link   http://github.com/zionsg/ZnZend for canonical source repository
 */

namespace ZnZendTest\View\Helper;

use PHPUnit\Framework\TestCase as TestCase;
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
