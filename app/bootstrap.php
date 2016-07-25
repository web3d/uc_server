<?php

!defined('IN_UC') && exit('Access Denied');

/**
 * 应用启动
 */

error_reporting(E_ERROR);

!defined('UC_ROOT') && define('UC_ROOT', dirname(__DIR__) . '/');

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

require UC_DATADIR . 'config.inc.php';

$cache = 'cache';
if (!Uii::$container->hasSingleton($cache)) {
    Uii::$container->setSingleton($cache, [
        'class' => 'ucs\caching\FileCache',
        'cachePath' => UC_DATADIR . '/cache'
    ]);
}

$_GET = daddslashes($_GET, 1, TRUE);
$_POST = daddslashes($_POST, 1, TRUE);
$_COOKIE = daddslashes($_COOKIE, 1, TRUE);
$_SERVER = daddslashes($_SERVER);
$_FILES = daddslashes($_FILES);
$_REQUEST = daddslashes($_REQUEST, 1, TRUE);



