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
    public function testHumanRelativeInputStartAsWords()
    {
        $date = strtotime('-1 year');

        $result = Datelib::humanRelativeDate($date, true, ' ago', date('m/d/Y'));
        $this->assertEquals('1 year ago', $result);
    }

    public function testHumanRelativeInputStartNumeric()
    {
        $date = strtotime('-1 year');

        $result = Datelib::humanRelativeDate($date, true, ' ago', time());
        $this->assertEquals('1 year ago', $result);
    }
}

