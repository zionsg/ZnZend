<?php
/**
 * ZnZend
 *
 * @link https://github.com/zionsg/ZnZend for canonical source repository
 */

namespace ZnZend\View\Helper;

use DateTime;
use Zend\View\Helper\AbstractHelper;

/**
 * View helper to format date range
 */
class ZnZendFormatDateRange extends AbstractHelper
{
    /**
     * __invoke
     *
     * For single dates, use PHP date() - the parameter order here caters to date ranges
     *
     * Start and end dates can be DateTime, English textual datetime description or UNIX timestamp
     * If start date is empty or invalid, '' is returned
     * If end date is empty or invalid, it will be set to start date
     *
     * @see    http://php.net/manual/en/function.date.php on output format strings
     * @param  string  $dateFormat       Output format to use if end date is empty or the same as start date
     * @param  string  $rangeStartFormat Output format to use for start date if the 2 dates are different
     * @param  string  $rangeEndFormat   Output format to use for end date if the 2 dates are different
     * @param  DateTime|string|int|float $startDate Start date
     * @param  DateTime|string|int|float $endDate   End date
     * @return string
     */
    public function __invoke($dateFormat, $rangeStartFormat, $rangeEndFormat, $startDate, $endDate)
    {
        // Convert start date to UNIX timestamp
        $startTimestamp = $this->getTimestamp($startDate);
        if (false === $startTimestamp) {
            return '';
        }

        // Convert end date to UNIX timestamp
        $endTimestamp = $this->getTimestamp($endDate);
        if (false === $endTimestamp) {
            $endTimestamp = $startTimestamp;
        }

        // Format as single date if both dates are the same else format as range
        if (date('Y-m-d', $endTimestamp) == date('Y-m-d', $startTimestamp)) {
            $output = date($dateFormat, $startTimestamp);
        } else {
            $output = date($rangeStartFormat, $startTimestamp) . date($rangeEndFormat, $endTimestamp);
        }

        return $output;
    }

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
