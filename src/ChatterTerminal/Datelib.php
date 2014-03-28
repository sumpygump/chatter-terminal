<?php
/**
 * ChatterTerminal Datelib class file
 *
 * @package ChatterTerminal
 */

namespace ChatterTerminal;

/**
 * ChatterTerminal Datelib
 *
 * Provides library functions for dealing with dates
 *
 * @package ChatterTerminal
 * @author Jansen Price <jansen.price@gmail.com>
 * @license http://www.opensource.org/licenses/mit-license.php MIT
 * @version 1.2
 */
class Datelib
{
    const UNIT_INDEX_SHORT = 0;
    const UNIT_INDEX_LONG = 1;
    const UNIT_INDEX_LONG_PLURAL = 2;

    /**
     * Store units for human relative dates
     *
     * Format is seconds => array (abbr, singular, plural)
     *
     * @var array
     */
    public static $units = array (
        1        => array('s', 'second', 'seconds'),
        60       => array('m', 'minute', 'minutes'),
        3600     => array('h', 'hour', 'hours'),
        86400    => array('d', 'day', 'days'),
        604800   => array('w', 'week', 'weeks'),
        2592000  => array('mon', 'month', 'months'),
        31104000 => array('y', 'year', 'years'),
    );

    /**
     * timestamp2date
     *
     * @param int $timestamp Unix timestamp to convert
     * @param string $dateformat The format to return the date
     * @return string Date formatted with $dateformat
     */
    public static function timestamp2date($timestamp, $dateformat='m/d/Y')
    {
        if (!empty($timestamp)) {
            return date($dateformat, $timestamp);
        } else {
            return "";
        }
    }

    /**
     * date2timestamp
     *
     * @param string $strDate A string representation of a date
     * @return mixed Unix timestamp or empty string
     */
    public static function date2timestamp($strDate)
    {
        if (!empty($strDate)) {
            return strtotime($strDate);
        } else {
            return "";
        }
    }

    /**
     * Add a number of days to a date
     *
     * @param mixed $days Number of days to add
     * @param mixed $date Date to start adding from
     * @param string $format Format
     * @return string
     */
    public static function dateadd($days, $date = null, $format = "m/d/Y")
    {
        $date = ($date ? $date : date("Y-m-d"));
        return date($format, strtotime($days . " days", strtotime($date)));
    }

    /**
     * Provide date in human relative format
     * (e.g., 18 hours ago, 2 days ago)
     *
     * @param mixed $date The date to convert
     * @param bool $longForm Whether to use the long form
     * @param string $post Append with text
     * @param mixed $start Starting date time for comparison
     * @return string The resulting human relative date string
     */
    public static function humanRelativeDate($date, $longForm = true,
        $post = ' ago', $start = null)
    {
        if (null == $start) {
            $now = time();
        } else {
            if (!is_numeric($start)) {
                $start = strtotime($start);
            }
            $now = $start;
        }
        
        if (!is_numeric($date)) {
            $date = strtotime($date);
        }

        $unitIndex = self::UNIT_INDEX_SHORT;

        $seconds = self::datediff('s', $date, $now, true);

        // Support for future
        $prefix = '';
        if ($seconds < 0) {
            $seconds = abs($seconds);
            if ($post == ' ago') {
                // Only change the post if it isn't modified from the default
                $post = ' from now';
            }
            if (!$longForm) {
                // Add a prefix for short form
                $prefix = '+';
            }
        }

        $keys = array_keys(self::$units);
        $key  = current($keys);

        // Discover which item in units applies to the seconds
        while ($key <= $seconds && $key !== false) {
            $key = next($keys);
        }

        // The previous one was the right one
        if ($key > 1) {
            $key = prev($keys);
        }

        if ($key === false) {
            $key = end($keys);
        }

        $count = round($seconds / $key);

        if ($longForm) {
            $unitIndex = self::UNIT_INDEX_LONG;

            if ($count != 1) {
                $unitIndex = self::UNIT_INDEX_LONG_PLURAL;
            }
        }

        $separator = ($post == '') ? '' : ' ';

        // Return "<prefix><5>< ><hours>< ago>"
        return $prefix . $count . $separator . self::$units[$key][$unitIndex] . $post;
    }

    /**
     * Calculate the difference between two dates
     *
     * $interval can be:
     *  yyyy - Number of full years
     *  q - Number of full quarters
     *  m - Number of full months
     *  y - Difference between day numbers
     *      (eg 1st Jan 2004 is "1", the first day. 2nd Feb 2003 is "33".
     *      The datediff is "-32".)
     *  d - Number of full days
     *  w - Number of full weekdays
     *  ww - Number of full weeks
     *  h - Number of full hours
     *  n - Number of full minutes
     *  s - Number of full seconds (default)
     *
     * @param mixed $interval A string representing the interval
     * @param mixed $datefrom The start date (from date)
     * @param mixed $dateto The end date (to date)
     * @param bool $usingTimestamps Flag to indicate dates are timestamps
     * @return float The difference between the dates using $interval
     */
    public static function datediff($interval, $datefrom, $dateto,
        $usingTimestamps = false)
    {
        if (!$usingTimestamps && !is_numeric($datefrom) && !is_numeric($dateto)) {
            $datefrom = strtotime($datefrom, 0);
            $dateto   = strtotime($dateto, 0);
        }
        $difference = $dateto - $datefrom; // Difference in seconds

        switch ($interval) {

        case 'yyyy': // Number of full years

            $yearsDifference = floor($difference / 31536000);

            $time = mktime(
                date("H", $datefrom), date("i", $datefrom),
                date("s", $datefrom), date("n", $datefrom),
                date("j", $datefrom), date("Y", $datefrom) + $yearsDifference
            );

            if ($time > $dateto) {
                $yearsDifference--;
            }

            $time = mktime(
                date("H", $dateto), date("i", $dateto), date("s", $dateto),
                date("n", $dateto), date("j", $dateto),
                date("Y", $dateto) - ($yearsDifference + 1)
            );

            if ($time > $datefrom) {
                $yearsDifference++;
            }

            return $yearsDifference;
            break;

        case "q": // Number of full quarters

            $quartersDifference = floor($difference / 8035200);
            while (
                mktime(
                    date("H", $datefrom), date("i", $datefrom),
                    date("s", $datefrom),
                    date("n", $datefrom) + ($quartersDifference * 3),
                    date("j", $dateto), date("Y", $datefrom)
                ) < $dateto
            ) {
                $quartersDifference++;
            }
            $quartersDifference--;

            return $quartersDifference;
            break;

        case "m": // Number of full months

            $monthsDifference = floor($difference / 2678400);
            while (
                mktime(
                    date("H", $datefrom), date("i", $datefrom),
                    date("s", $datefrom),
                    date("n", $datefrom) + ($monthsDifference),
                    date("j", $dateto), date("Y", $datefrom)
                ) < $dateto
            ) {
                $monthsDifference++;
            }
            $monthsDifference--;

            return $monthsDifference;
            break;

        case 'y': // Difference between day numbers

            return date("z", $dateto) - date("z", $datefrom);
            break;

        case "d": // Number of full days

            return floor($difference / 86400);
            break;

        case "w": // Number of full weekdays

            $daysDifference  = floor($difference / 86400);
            $weeksDifference = floor($daysDifference / 7); // Complete weeks
            $firstDay        = date("w", $datefrom);
            $daysRemainder   = floor($daysDifference % 7);

            // Do we have a Saturday or Sunday in the remainder?
            $oddDays = $firstDay + $daysRemainder;

            if ($oddDays > 7) { // Sunday
                $daysRemainder--;
            }
            if ($oddDays > 6) { // Saturday
                $daysRemainder--;
            }

            return ($weeksDifference * 5) + $daysRemainder;
            break;

        case "ww": // Number of full weeks

            return floor($difference / 604800);
            break;

        case "h": // Number of full hours

            return floor($difference / 3600);
            break;

        case "n": // Number of full minutes

            return floor($difference / 60);
            break;

        default: // Number of full seconds (default)

            return $difference;
            break;
        }
    }
}
