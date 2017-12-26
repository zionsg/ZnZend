<?php
/**
 * ZnZend
 *
 * @author Zion Ng <zion@intzone.com>
 * @link   http://github.com/zionsg/ZnZend for canonical source repository
 */

namespace ZnZend\View\Helper;

use DateTime;
use Zend\View\Helper\AbstractHelper;

/**
 * View helper to format time range
 */
class ZnZendFormatTimeRange extends AbstractHelper
{
    /**
     * __invoke
     *
     * For single dates, use PHP date() - the parameter order here caters to time ranges
     *
     * Start and end times can be DateTime, English textual datetime description or UNIX timestamp
     * If $startTimeString is empty or invalid, '' is returned
     * If $endTimeString is empty or invalid, it will be set to $startTimeString
     *
     * @see    http://php.net/manual/en/function.date.php on output format strings
     * @param  string  $timeFormat       Output format to use if end time is empty or the same as start time
     * @param  string  $rangeStartFormat Output format to use for start time if the 2 times are different
     * @param  string  $rangeEndFormat   Output format to use for end time if the 2 times are different
     * @param  DateTime|string|int|float $startTime Start time
     * @param  DateTime|string|int|float $endTime   End time
     * @param  bool    $ignoreMidnight   DEFAULT=false. If true and start time is 00:00:00, return ''.
     *                                   If true and end time is 00:00:00, it will not be shown
     * @return string
     */
    public function __invoke(
        $timeFormat,
        $rangeStartFormat,
        $rangeEndFormat,
        $startTime,
        $endTime,
        $ignoreMidnight = false
    ) {
        // Convert start time to UNIX timestamp
        $startTimestamp = $this->getTimestamp($startTime);
        if (false === $startTimestamp) {
            return '';
        }
        if ($ignoreMidnight && (int) date('His', $startTimestamp) == 0) {
            return '';
        }

        // Convert end time to UNIX timestamp
        $endTimestamp = $this->getTimestamp($endTime);
        if (false === $endTimestamp) {
            $endTimestamp = $startTimestamp;
        }
        if ($ignoreMidnight && (int) date('His', $endTimestamp) == 0) {
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

    /**
     * Convert various datetime representations into UNIX timestamp
     *
     * @param  DateTime|string|int|float $datetime
     * @return bool|int|float False is returned if $datetime is not a valid timestamp
     */
    protected function getTimestamp($datetime)
    {
        $timestamp = false;

        // Have to separate objects from primitive types as the other functions do not take in objects
        if (is_object($datetime)) {
            if ($datetime instanceof DateTime) {
                $timestamp = $datetime->getTimestamp();
            }
        } elseif (($parsedString = strtotime($datetime)) !== false) {
            $timestamp = $parsedString;
        } elseif (is_numeric($datetime)) {
            $timestamp = $datetime;
        }

        return $timestamp;
    }
}
