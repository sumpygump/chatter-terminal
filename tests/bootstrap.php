<?php
/**
 * Tests bootstrap
 *
 * @package Qis
 */

require_once 'BaseTestCase.php';

date_default_timezone_set('America/Chicago');

$root = realpath(dirname(dirname(__FILE__)));

// Include path
$paths = array(
    '.',
    $root,
    $root . DIRECTORY_SEPARATOR . 'lib',
    get_include_path(),
);
set_include_path(implode(PATH_SEPARATOR, $paths));

require_once 'Qi/Console/Std.php';
require_once 'Qi/Console/Terminal.php';
require_once 'Qi/Console/ArgV.php';
