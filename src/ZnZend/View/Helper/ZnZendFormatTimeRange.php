<?php
/**
 * ZnZend
 *
 * @author Zion Ng <zion@intzone.com>
 * @link   http://github.com/zionsg/ZnZend for canonical source repository
 */

namespace ZnZend\View\Helper;

use Zend\View\Helper\AbstractHelper;

/**
 * View helper to format time range
 */
class ZnZendFormatTimeRange extends AbstractHelper
{
    /**
     * __invoke
     *
     * Note that the time parameters are not UNIX timestamps but strings,
     * eg: '2012-11-20 19:00:00'
     *
     * If $startTimeString is empty or invalid, '' is returned
     * If $endTimeString is empty or invalid, it will be set to $startTimeString
     *
     * @see    http://php.net/manual/en/function.date.php on output format strings
     * @param  string  $timeFormat       Output format to use if end time is empty or the same as start time
     * @param  string  $rangeStartFormat Output format to use for start time if the 2 times are different
     * @param  string  $rangeEndFormat   Output format to use for end time if the 2 times are different
     * @param  string  $startTimeString  Start time as string. Will be converted to UNIX timestamp
     * @param  string  $endTimeString    End time as string. Will be converted to UNIX timestamp
     * @param  boolean $ignoreMidnight   DEFAULT=false. If true and start time is 00:00:00, return ''.
     *                                   If true and end time is 00:00:00, it will not be shown
     * @return string
     */
    public function __invoke(
        $timeFormat,
        $rangeStartFormat,
        $rangeEndFormat,
        $startTimeString,
        $endTimeString,
        $ignoreMidnight = false
    ) {
        // Check start time
        if (empty($startTimeString) || (int) $startTimeString == 0) {
            return '';
        }
        $startTimestamp = strtotime($startTimeString);
        if ($startTimestamp === false) {
            return '';
        }
        if ($ignoreMidnight && (int)date('His', $startTimestamp) == 0) {
            return '';
        }

        // Check end time
        if (empty($endTimeString) || (int) $endTimeString == 0) {
            $endTimeString = $startTimeString;
        }
        $endTimestamp = strtotime($endTimeString);
        if ($endTimestamp === false) {
            $endTimestamp = $startTimestamp;
        }
        if ($ignoreMidnight && (int)date('His', $endTimestamp) == 0) {
            $endTimestamp = $startTimestamp;
        }

        // Format as single time if both times are the same else format as range
        if (date('H:i:s', $endTimestamp) == date('H:i:s', $startTimestamp)) {
            $output = date($timeFormat, $startTimestamp);
        } else {
            $output = date($rangeStartFormat, $startTimestamp) . date($rangeEndFormat, $endTimestamp);
        }

        return $output;
    } // end function __invoke

}
