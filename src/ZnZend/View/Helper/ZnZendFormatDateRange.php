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
 * View helper to format date range
 */
class ZnZendFormatDateRange extends AbstractHelper
{
    /**
     * __invoke
     *
     * Note that the date parameters are not UNIX timestamps but strings,
     * eg: '2012-11-20 19:00:00'
     *
     * If start date is empty or invalid, '' is returned
     * If end date is empty or invalid, it will be set to start date
     *
     * @see    http://php.net/manual/en/function.date.php on output format strings
     * @param  string  $dateFormat       Output format to use if end date is empty or the same as start date
     * @param  string  $rangeStartFormat Output format to use for start date if the 2 dates are different
     * @param  string  $rangeEndFormat   Output format to use for end date if the 2 dates are different
     * @param  string  $startDateString  Start date as string. Will be converted to UNIX timestamp
     * @param  string  $endDateString    End date as string. Will be converted to UNIX timestamp
     * @return string
     */
    public function __invoke($dateFormat, $rangeStartFormat, $rangeEndFormat, $startDateString, $endDateString)
    {
        // Check start date
        if (empty($startDateString) || (int) $startDateString == 0) {
            return '';
        }
        $startTimestamp = strtotime($startDateString);
        if ($startTimestamp === false) {
            return '';
        }

        // Check end date
        if (empty($endDateString) || (int) $endDateString == 0) {
            $endDateString = $startDateString;
        }
        $endTimestamp = strtotime($endDateString);
        if ($endTimestamp === false) {
            $endTimestamp = $startTimestamp;
        }

        // Format as single date if both dates are the same else format as range
        if (date('Y-m-d', $endTimestamp) == date('Y-m-d', $startTimestamp)) {
            $output = date($dateFormat, $startTimestamp);
        } else {
            $output = date($rangeStartFormat, $startTimestamp) . date($rangeEndFormat, $endTimestamp);
        }

        return $output;
    } // end function __invoke

} // end class
