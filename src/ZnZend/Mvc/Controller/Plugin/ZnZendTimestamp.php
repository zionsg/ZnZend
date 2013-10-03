<?php
/**
 * ZnZend
 *
 * @author Zion Ng <zion@intzone.com>
 * @link   http://github.com/zionsg/ZnZend for canonical source repository
 */

namespace ZnZend\Mvc\Controller\Plugin;

use Zend\Mvc\Controller\Plugin\AbstractPlugin;

/**
 * Controller plugin to return timestamp formatted to standard length and in base 36
 *
 * Used for generating names for files and folders where length is a factor during sorting.
 * Floats returned by microtime(true) may not always have the same number of digits, hence the
 * need to standardize the length, eg. 1371437694.2509, 1371437694.251, 1371437694.3
 * Base 36 conversion used to reduce length of result string.
 */
class ZnZendTimestamp extends AbstractPlugin
{
    /**
     * Return timestamp formatted to standard length and in base 36
     *
     * $timestamp is included for convenience and placed as the 2nd argument
     * as it will be rarely used compared to the 1st argument.
     *
     * @param bool       $convertToBase36 Default = true. Whether to convert timestamp to base 36.
     * @param null|float $timestamp       Default = null. If null, microtime(true) is used.
     */
    public function __invoke($convertToBase36 = true, $timestamp = null)
    {
        if (null === $timestamp || !is_numeric($timestamp)) {
            $timestamp = microtime(true);
        }

        // max() in case the latter is 0 or negative
        // 4 is derived from default PHP value of 14 for 'precision' and PHP_INT_MAX on 32-bit platform
        $precision = (int) ini_get('precision');
        $decimalDigits = max(4, $precision - strlen(PHP_INT_MAX));

        $formattedTimestamp = sprintf(
            '%0' . ($precision + 1) . ".{$decimalDigits}f",
            $timestamp
        );

        if (!$convertToBase36) {
            return $formattedTimestamp;
        }

        // base_convert() ignores the decimal point when converting floats
        // Eg. Converting 1371437694.2509, 1371437694.2510, 1371437694.3000 will yield different lengths
        // as they are seen as 13714376942509, 1371437694251 and 13714376943 when passed as floats
        $formattedTimestamp = str_replace('.', '', $formattedTimestamp);
        $maxTimestamp = PHP_INT_MAX . str_repeat('0', $decimalDigits);
        $base36PadLen = strlen(base_convert($maxTimestamp, 10, 36));

        $base36 = str_pad(base_convert($formattedTimestamp, 10, 36), $base36PadLen, '0', STR_PAD_LEFT);
        return $base36;
    }
}
