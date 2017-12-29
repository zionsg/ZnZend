<?php
/**
 * ZnZend
 *
 * @link https://github.com/zionsg/ZnZend for canonical source repository
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
    protected $prefixes = ['', 'Ki', 'Mi', 'Gi', 'Ti', 'Pi', 'Ei', 'Zi', 'Yi'];

    /**
     * __invoke
     *
     * 0 <= final numeric value shown with unit < 1024
     * Precision is set to 2 decimal places
     *
     * @param  int|float $bytes            Value in bytes
     * @param  bool      $returnMultiplier DEFAULT=false. Whether to return multiplier only.
     *                                     $multiplier x $finalValue = $bytes
     *                                     This is provided for use in calculations if need be
     * @return string
     */
    public function __invoke($bytes, $returnMultiplier = false)
    {
        if (! is_numeric($bytes)) {
            $bytes = 0;
        }

        $base  = 1024;
        $power = (int) floor(log($bytes, $base));
        $power = min($power, count($this->prefixes) - 1); // to ensure power corresponds to a prefix

        $multiplier = pow($base, $power);
        return (
            $returnMultiplier
            ? $multiplier
            : sprintf('%.2f %s%s', $bytes / $multiplier, $this->prefixes[$power], 'B')
        );
    }
}
