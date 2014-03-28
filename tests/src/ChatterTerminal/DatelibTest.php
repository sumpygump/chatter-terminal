<?php
/**
 * Qi Datelib test cases file 
 *
 * @package ChatterTerminal
 */

namespace ChatterTerminal\Tests;

use ChatterTerminal\Datelib;
use ChatterTerminal\Tests\BaseTestCase;

/**
 * ChatterTerminal Datelib Test cases
 * 
 * @uses BaseTestCase
 * @package ChatterTerminal
 * @author Jansen Price <jansen.price@gmail.com>
 * @version $Id$
 */
class DatelibTest extends BaseTestCase
{
    /**
     * Set up before each test
     * 
     * @return void
     */
    public function setUp()
    {
        $this->_object = new Datelib();
    }

    /**
     * Tear down after each test
     *
     * @return void
     */
    public function tearDown()
    {
    }

    /**
     * testTimestamp2Date
     *
     * @return void
     */
    public function testTimestamp2DateEmptyInput()
    {
        $expected = '';

        $actual = Datelib::timestamp2date('');

        $this->assertEquals($expected, $actual);
    }

    /**
     * testTimestamp2Date
     *
     * @return void
     */
    public function testTimestamp2Date()
    {
        $timestamp = time();
        $expected = date('m/d/Y', $timestamp);

        $actual = Datelib::timestamp2date($timestamp);

        $this->assertEquals($expected, $actual);
    }

    /**
     * testTimestamp2DateFormatted
     *
     * @return void
     */
    public function testTimestamp2DateFormatted()
    {
        $timestamp = time();
        $expected = date('Y-m-d H:i:s', $timestamp);

        $actual = Datelib::timestamp2date($timestamp, 'Y-m-d H:i:s');

        $this->assertEquals($expected, $actual);
    }

    /**
     * testDate2TimestampEmptyInput
     *
     * @return void
     */
    public function testDate2TimestampEmptyInput()
    {
        $timestamp = Datelib::date2timestamp('');

        $this->assertEquals('', $timestamp);
    }

    /**
     * testDate2Timestamp
     *
     * @return void
     */
    public function testDate2Timestamp()
    {
        $date = '2014-02-28';

        $timestamp = Datelib::date2timestamp($date);

        $this->assertEquals(1393567200, $timestamp);
    }

    /**
     * testDateAdd
     *
     * @return void
     */
    public function testDateAdd()
    {
        $newDate = Datelib::dateadd(4, '2014-03-20');

        $this->assertEquals('03/24/2014', $newDate);
    }

    /**
     * testDateAddFormatted
     *
     * @return void
     */
    public function testDateAddFormatted()
    {
        $newDate = Datelib::dateadd(4, '2014-03-20', 'Ymd');

        $this->assertEquals('20140324', $newDate);
    }

    /**
     * Test human relative date for one year ago
     * 
     * @return void
     */
    public function testHumanRelativeOneYearAgo()
    {
        $date = strtotime('-1 year');

        $result = Datelib::humanRelativeDate($date);
        $this->assertEquals('1 year ago', $result);
    }

    /**
     * testHumanRelativeInputStartAsWords
     *
     * @return void
     */
    public function testHumanRelativeInputStartAsString()
    {
        $date = strtotime('-1 year');

        $result = Datelib::humanRelativeDate($date, true, ' ago', date('m/d/Y'));
        $this->assertEquals('1 year ago', $result);
    }

    /**
     * testHumanRelativeInputStartNumeric
     *
     * @return void
     */
    public function testHumanRelativeInputStartNumeric()
    {
        $date = strtotime('-1 year');

        $result = Datelib::humanRelativeDate($date, true, ' ago', time());
        $this->assertEquals('1 year ago', $result);
    }

    /**
     * testHumanRelativeInputDateNonNumeric
     *
     * @return void
     */
    public function testHumanRelativeInputDateNonNumeric()
    {
        $date = date('m/d/Y', strtotime('-1 year'));

        $result = Datelib::humanRelativeDate($date);
        $this->assertEquals('1 year ago', $result);
    }

    /**
     * testHumanRelativeInputSeconds
     *
     * @return void
     */
    public function testHumanRelativeInputSeconds()
    {
        $date = strtotime('-1 second');

        $result = Datelib::humanRelativeDate($date);
        $this->assertEquals('1 second ago', $result);
    }

    /**
     * testHumanRelativeInputMinutes
     *
     * @return void
     */
    public function testHumanRelativeInputMinutes()
    {
        $date = strtotime('-5 minutes');

        $result = Datelib::humanRelativeDate($date);
        $this->assertEquals('5 minutes ago', $result);
    }

    /**
     * testHumanRelativeInputFuture
     *
     * @return void
     */
    public function testHumanRelativeInputFuture()
    {
        $date = strtotime('+5 minutes');

        $result = Datelib::humanRelativeDate($date);
        $this->assertEquals('5 minutes from now', $result);
    }

    /**
     * testHumanRelativeShortForm
     *
     * @return void
     */
    public function testHumanRelativeShortForm()
    {
        $date = strtotime('-38 hours');

        $result = Datelib::humanRelativeDate($date, false);
        $this->assertEquals('2 d ago', $result);
    }

    /**
     * testHumanRelativeShortFormNoPost
     *
     * @return void
     */
    public function testHumanRelativeShortFormNoPost()
    {
        $date = strtotime('-15 minutes');

        $result = Datelib::humanRelativeDate($date, false, '');
        $this->assertEquals('15m', $result);
    }

    /**
     * testHumanRelativeShortFormFuture
     *
     * @return void
     */
    public function testHumanRelativeShortFormFuture()
    {
        $date = strtotime('+15 minutes');

        $result = Datelib::humanRelativeDate($date, false, '');
        $this->assertEquals('+15m', $result);
    }

    /**
     * testDateDiffDaysNumeric
     *
     * @return void
     */
    public function testDateDiffDaysNumeric()
    {
        $dateA = 1393653600;
        $dateB = 1395723600;

        $diff = Datelib::datediff('d', $dateA, $dateB);

        $this->assertEquals(23, $diff);
    }

    /**
     * testDateDiffUsingTimestamps
     *
     * @return void
     */
    public function testDateDiffUsingTimestamps()
    {
        $dateA = 1393653600;
        $dateB = 1395723600;

        $diff = Datelib::datediff('d', $dateA, $dateB, true);

        $this->assertEquals(23, $diff);
    }

    /**
     * testDateDiffDaysStrings
     *
     * @return void
     */
    public function testDateDiffDaysStrings()
    {
        $dateA = "2014-03-01";
        $dateB = "2014-03-25";

        $diff = Datelib::datediff('d', $dateA, $dateB);

        $this->assertEquals(23, $diff);
    }

    /**
     * testDateDiffFullYears
     *
     * @return void
     */
    public function testDateDiffFullYears()
    {
        $dateA = "2010-03-01";
        $dateB = "2014-03-25";

        $diff = Datelib::datediff('yyyy', $dateA, $dateB);

        $this->assertEquals(4, $diff);

        // This will use the logic to adjust by subtracting one year
        $dateA = "2010-03-01";
        $dateB = "2015-02-28";

        $diff = Datelib::datediff('yyyy', $dateA, $dateB);

        $this->assertEquals(4, $diff);
    }

    /**
     * testDateDiffQuarters
     *
     * @return void
     */
    public function testDateDiffQuarters()
    {
        $dateA = "2010-03-01";
        $dateB = "2014-03-25";

        $diff = Datelib::datediff('q', $dateA, $dateB);

        $this->assertEquals(15, $diff);
    }

    /**
     * testDateDiffMonths
     *
     * @return void
     */
    public function testDateDiffMonths()
    {
        $dateA = "2010-03-01";
        $dateB = "2013-03-25";

        $diff = Datelib::datediff('m', $dateA, $dateB);

        $this->assertEquals(35, $diff);
    }

    /**
     * testDateDiffDayNumbers
     *
     * @return void
     */
    public function testDateDiffDayNumbers()
    {
        $dateA = "2010-03-01";
        $dateB = "2010-03-12";

        $diff = Datelib::datediff('y', $dateA, $dateB);

        $this->assertEquals(11, $diff);
    }

    /**
     * testDateDiffDays
     *
     * @return void
     */
    public function testDateDiffDays()
    {
        $dateA = "2010-03-01";
        $dateB = "2010-03-12";

        $diff = Datelib::datediff('d', $dateA, $dateB);

        $this->assertEquals(11, $diff);
    }

    /**
     * testDateDiffFullWeekdays
     *
     * @return void
     */
    public function testDateDiffFullWeekdays()
    {
        $dateA = "2010-03-01";
        $dateB = "2010-03-20";

        $diff = Datelib::datediff('w', $dateA, $dateB);

        $this->assertEquals(14, $diff);

        $dateA = "2014-03-29";
        $dateB = "2014-04-05";

        $diff = Datelib::datediff('w', $dateA, $dateB);

        $this->assertEquals(5, $diff);

        $dateA = "2014-03-28";
        $dateB = "2014-04-05";

        $diff = Datelib::datediff('w', $dateA, $dateB);

        $this->assertEquals(6, $diff);
    }

    /**
     * testDateDiffWeeks
     *
     * @return void
     */
    public function testDateDiffWeeks()
    {
        $dateA = "2014-03-01";
        $dateB = "2014-04-05";

        $diff = Datelib::datediff('ww', $dateA, $dateB);

        $this->assertEquals(4, $diff);
    }

    /**
     * testDateDiffHours
     *
     * @return void
     */
    public function testDateDiffHours()
    {
        $dateA = "2014-03-01 08:30:00";
        $dateB = "2014-03-02 04:22:41";

        $diff = Datelib::datediff('h', $dateA, $dateB);

        $this->assertEquals(19, $diff);
    }

    /**
     * testDateDiffMinutes
     *
     * @return void
     */
    public function testDateDiffMinutes()
    {
        $dateA = "2014-03-01 08:30:00";
        $dateB = "2014-03-01 12:22:41";

        $diff = Datelib::datediff('n', $dateA, $dateB);

        $this->assertEquals(232, $diff);
    }
}

