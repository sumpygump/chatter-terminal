<?php
/**
 * Qi Datelib class file
 *
 * @package Qi
 */

/**
 * Qi_Datelib
 *
 * Provides library functions for dealing with dates
 *
 * @package Qi
 * @author Jansen Price <jansen.price@gmail.com>
 * @license http://www.opensource.org/licenses/mit-license.php MIT
 * @version 1.2
 */
class Qi_Datelib
{
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
     * @param mixed $v Number of days to add
     * @param mixed $d Date to start adding from
     * @param string $f Format
     * @return string
     */
    public static function dateadd($v, $d=null, $f="m/d/Y")
    {
        $d = ($d ? $d : date("Y-m-d"));
        return date($f, strtotime($v." days", strtotime($d)));
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

        $unitIndex = 0;

        $seconds = self::datediff("s", $date, $now, true);

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
            $unitIndex = 1;

            if ($count != 1) {
                $unitIndex = 2;
            }
        }

        $separator = ' ';
        if ($post == '') {
            $separator = '';
        }

        // Return "<5>< ><hours>< ago>"
        return $count . $separator . self::$units[$key][$unitIndex] . $post;
    }

    /**
     * Calculate the difference between two dates
     *
     * @param mixed $interval A string representing the interval
     * @param mixed $datefrom The start date (from date)
     * @param mixed $dateto The end date (to date)
     * @param bool $using_timestamps Flag to indicate dates are timestamps
     * @return float The difference between the dates using $interval
     */
    public static function datediff($interval, $datefrom, $dateto,
        $using_timestamps = false)
    {
        /*
        $interval can be:
        yyyy - Number of full years
        q - Number of full quarters
        m - Number of full months
        y - Difference between day numbers
            (eg 1st Jan 2004 is "1", the first day. 2nd Feb 2003 is "33".
            The datediff is "-32".)
        d - Number of full days
        w - Number of full weekdays
        ww - Number of full weeks
        h - Number of full hours
        n - Number of full minutes
        s - Number of full seconds (default)
        */

        if (!$using_timestamps) {
            $datefrom = strtotime($datefrom, 0);
            $dateto   = strtotime($dateto, 0);
        }
        $difference = $dateto - $datefrom; // Difference in seconds

        switch ($interval) {

        case 'yyyy': // Number of full years

            $years_difference = floor($difference / 31536000);

            $time = mktime(
                date("H", $datefrom), date("i", $datefrom),
                date("s", $datefrom), date("n", $datefrom),
                date("j", $datefrom), date("Y", $datefrom) + $years_difference
            );

            if ($time > $dateto) {
                $years_difference--;
            }

            $time = mktime(
                date("H", $dateto), date("i", $dateto), date("s", $dateto),
                date("n", $dateto), date("j", $dateto),
                date("Y", $dateto) - ($years_difference + 1)
            );

            if ($time > $datefrom) {
                $years_difference++;
            }

            $datediff = $years_difference;
            break;

        case "q": // Number of full quarters

            $quarters_difference = floor($difference / 8035200);
            while (
                mktime(
                    date("H", $datefrom), date("i", $datefrom),
                    date("s", $datefrom),
                    date("n", $datefrom) + ($quarters_difference * 3),
                    date("j", $dateto), date("Y", $datefrom)
                ) < $dateto
            ) {
                $months_difference++;
            }
            $quarters_difference--;
            $datediff = $quarters_difference;
            break;

        case "m": // Number of full months

            $months_difference = floor($difference / 2678400);
            while (
                mktime(
                    date("H", $datefrom), date("i", $datefrom),
                    date("s", $datefrom),
                    date("n", $datefrom) + ($months_difference),
                    date("j", $dateto), date("Y", $datefrom)
                ) < $dateto
            ) {
                $months_difference++;
            }
            $months_difference--;
            $datediff = $months_difference;
            break;

        case 'y': // Difference between day numbers

            $datediff = date("z", $dateto) - date("z", $datefrom);
            break;

        case "d": // Number of full days

            $datediff = floor($difference / 86400);
            break;

        case "w": // Number of full weekdays

            $days_difference  = floor($difference / 86400);
            $weeks_difference = floor($days_difference / 7); // Complete weeks
            $first_day        = date("w", $datefrom);
            $days_remainder   = floor($days_difference % 7);

            // Do we have a Saturday or Sunday in the remainder?
            $odd_days = $first_day + $days_remainder;

            if ($odd_days > 7) { // Sunday
                $days_remainder--;
            }
            if ($odd_days > 6) { // Saturday
                $days_remainder--;
            }
            $datediff = ($weeks_difference * 5) + $days_remainder;
            break;

        case "ww": // Number of full weeks

            $datediff = floor($difference / 604800);
            break;

        case "h": // Number of full hours

            $datediff = floor($difference / 3600);
            break;

        case "n": // Number of full minutes

            $datediff = floor($difference / 60);
            break;

        default: // Number of full seconds (default)

            $datediff = $difference;
            break;
        }

        return $datediff;
    }

    /**
     * Converts a date and time string from one format to another
     *
     * (e.g. d/m/Y => Y-m-d, d.m.Y => Y/d/m, ...)
     * mod of http://www.php.net/manual/en/function.date.php#71397
     *
     * @param string $date_format1 The format to convert from
     * @param string $date_format2 The format to convert to
     * @param string $date_str The date to format
     * @return string
     */
    public function date_convert_format($date_format1, $date_format2, $date_str)
    {
        $base_struc     = split('[:/.\ \-]', $date_format1);
        $date_str_parts = split('[:/.\ \-]', $date_str);

        // print_r( $base_struc ); echo "\n"; // for testing
        // print_r( $date_str_parts ); echo "\n"; // for testing

        $date_elements = array();

        $p_keys = array_keys($base_struc);
        foreach ($p_keys as $p_key) {
            if (!empty( $date_str_parts[$p_key])) {
                $date_elements[$base_struc[$p_key]] = $date_str_parts[$p_key];
            } else {
                return false;
            }
        }

        // print_r($date_elements); // for testing

        if (array_key_exists('M', $date_elements)) {
            $Mtom = array(
                "Jan" => "01",
                "Feb" => "02",
                "Mar" => "03",
                "Apr" => "04",
                "May" => "05",
                "Jun" => "06",
                "Jul" => "07",
                "Aug" => "08",
                "Sep" => "09",
                "Oct" => "10",
                "Nov" => "11",
                "Dec" => "12",
            );

            $date_elements['m'] = $Mtom[$date_elements['M']];
        }

        // print_r($date_elements); // for testing

        $dummy_ts = mktime(
            $date_elements['H'],
            $date_elements['i'],
            $date_elements['s'],
            $date_elements['m'],
            $date_elements['d'],
            $date_elements['Y']
        );

        return date($date_format2, $dummy_ts);
    }
}
