<?php
/**
 * Tests bootstrap
 *
 * @package Qis
 */

require_once 'BaseTestCase.php';

date_default_timezone_set('America/Chicago');

$root = realpath(dirname(dirname(__FILE__)));

$vendorAutoloadPath = $root . DIRECTORY_SEPARATOR . 'vendor/autoload.php';
if (!file_exists($vendorAutoloadPath)) {
    die("You must run `composer install` before proceeding\n");
}

$loader = require_once $vendorAutoloadPath;
$loader->add('', $root . DIRECTORY_SEPARATOR . 'src');
