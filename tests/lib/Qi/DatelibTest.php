<?php
/**
 * Qi Datelib test cases file 
 *
 * @package Qi
 */

/**
 * @see Qi_Datelib
 */
require_once 'Qi/Datelib.php';

/**
 * Qi Datelib Test cases
 * 
 * @uses BaseTestCase
 * @package Qi
 * @author Jansen Price <jansen.price@gmail.com>
 * @version $Id$
 */
class Qi_Datelib_Test extends BaseTestCase
{
    /**
     * Set up before each test
     * 
     * @return void
     */
    public function setUp()
    {
        $this->_object = new Qi_Datelib();
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
     * Test human relative date for one year ago
     * 
     * @return void
     */
    public function testHumanRelativeOneYearAgo()
    {
        $date = strtotime('-1 year');

        $result = Qi_Datelib::humanRelativeDate($date);
        $this->assertEquals('1 year ago', $result);
    }
}
