<?php
/**
 * Base Test Case class file
 *
 * @package Chatter
 */

/**
 * @see PHPUnit/Framework.php
 */
require_once 'PHPUnit/Framework.php';

/**
 * Base Test Case
 * 
 * @uses PHPUnit_Framework_TestCase
 * @package Chatter
 * @subpackage Tests
 * @author Jansen Price <jansen.price@gmail.com>
 * @version $Id$
 */
class BaseTestCase extends PHPUnit_Framework_TestCase
{
    /**
     * Storage of object being tested
     *
     * @var object
     */
    protected $_object;
}
