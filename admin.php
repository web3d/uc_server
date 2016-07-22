<?php

/*
 * [UCenter] (C)2001-2099 Comsenz Inc.
 * This is NOT a freeware, use is subject to license terms
 *
 * $Id: admin.php 1139 2012-05-08 09:02:11Z liulanbo $
 */
error_reporting(E_ALL);

define('IN_UC', TRUE);

require __DIR__ . '/app/bootstrap.php';

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

