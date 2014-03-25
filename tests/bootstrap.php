<?php
/**
 * Tests bootstrap
 *
 * @package Qis
 */

require_once 'BaseTestCase.php';

date_default_timezone_set('America/Chicago');

$root = realpath(dirname(dirname(__FILE__)));

$loader = require_once $root . DIRECTORY_SEPARATOR . 'vendor/autoload.php';
$loader->add('', $root . DIRECTORY_SEPARATOR . 'src');
