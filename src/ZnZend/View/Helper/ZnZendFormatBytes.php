<?php
/**
 * ZnZend
 *
 * @author Zion Ng <zion@intzone.com>
 * @link   [Source] http://github.com/zionsg/ZnZend
 * @since  2012-11-23T23:00+08:00
 */
namespace ZnZend\View\Helper;

use Zend\View\Helper\AbstractHelper;

/**
 * View helper to format bytes to human-readable form
 */
class ZnZendFormatBytes extends AbstractHelper
{
    /**
     * Binary prefixes (IEC)
     *
     * @see http://en.wikipedia.org/wiki/Binary_prefix
     * @var array
     */
    protected $prefix = array('', 'Ki', 'Mi', 'Gi', 'Ti', 'Pi', 'Ei', 'Zi', 'Yi');

    /**
     * __invoke
     *
     * The final numeric value should be less than 1024 and more than 0
     * when shown with the final unit and up to 2 decimal places
     *
     * @param int         $bytes            Value in bytes
     * @param null|string $byteString       DEFAULT='B'. String to use to denote "bytes"
     * @param boolean     $returnMultiplier DEFAULT=false. Whether to return multiplier only
     */
    public function __invoke($bytes, $byteString = 'B', $returnMultiplier = false)
    {
        $bytes = (int) $bytes;
        if ($byteString === null) {
            $byteString = 'B';
        }

        $multiplier = 1;
        foreach ($this->prefix as $key => $value) {
            if ($bytes < 1024) {
                return (
                    $returnMultiplier
                    ? $multiplier
                    : sprintf('%.2f %s%s', $bytes, $value, $byteString)
                );
            }
            $bytes /= 1024;
            $multiplier *= 1024;
        }

        return (
            $returnMultiplier
            ? $multiplier
            : sprintf('%.2f %s%s', $bytes * 1024, end($this->_prefix), $byteString)
        );
    } // end function __invoke

} // end class
