<?php

/*
 * [UCenter] (C)2001-2099 Comsenz Inc.
 * This is NOT a freeware, use is subject to license terms
 *
 * $Id: admin.php 1139 2012-05-08 09:02:11Z liulanbo $
 */
error_reporting(E_ALL);

define('IN_UC', TRUE);
define('UC_ROOT', substr(__FILE__, 0, - 9));
define('UC_API', strtolower((isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] == 'on' ? 'https' : 'http') . '://' . $_SERVER['HTTP_HOST'] . substr($_SERVER['PHP_SELF'], 0, strrpos($_SERVER['PHP_SELF'], '/'))));
define('UC_DATADIR', UC_ROOT . 'data/');
define('UC_DATAURL', UC_API . '/data');
define('UC_APPDIR', UC_ROOT . 'app');
define('UC_VENDORDIR', UC_ROOT . 'vendor');

require UC_APPDIR . '/release.php';

require UC_VENDORDIR . '/autoloader.php';
$loader->map('uc\\server\\app', UC_APPDIR);
$loader->map('uc\\server\\plugin', UC_ROOT . 'plugin');
$loader->register();

require UC_VENDORDIR . '/uc/server/common.php';

$_GET = daddslashes($_GET, 1, TRUE);
$_POST = daddslashes($_POST, 1, TRUE);
$_COOKIE = daddslashes($_COOKIE, 1, TRUE);
$_SERVER = daddslashes($_SERVER);
$_FILES = daddslashes($_FILES);
$_REQUEST = daddslashes($_REQUEST, 1, TRUE);

require UC_DATADIR . 'config.inc.php';

$m = getgpc('m');
$a = getgpc('a');
$m = empty($m) ? 'frame' : $m;
$a = empty($a) ? 'index' : $a;

define('RELEASE_ROOT', '');

if (in_array($m, array(
    'admin',
    'app',
    'badword',
    'cache',
    'db',
    'domain',
    'frame',
    'log',
    'note',
    'feed',
    'mail',
    'setting',
    'user',
    'credit',
    'seccode',
    'tool',
    'plugin',
    'pm'
))) {
    $c = '\\uc\\server\\app\\control\\admin\\' . ucwords($m) . 'Control';
    $control = new $c();
    $method = 'on' . $a;
    if (method_exists($control, $method) && $a{0} != '_') {
        $control->$method();
    } elseif (method_exists($control, '_call')) {
        $control->_call('on' . $a, '');
    } else {
        exit('Action not found!');
    }
} else {
    exit('Module not found!');
}

