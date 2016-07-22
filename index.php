<?php

/*
 * [UCenter] (C)2001-2099 Comsenz Inc.
 * This is NOT a freeware, use is subject to license terms
 *
 * $Id: index.php 1139 2012-05-08 09:02:11Z liulanbo $
 */
error_reporting(0);

define('IN_UC', TRUE);

require __DIR__ . '/app/bootstrap.php';

$m = getgpc('m');
$a = getgpc('a');
if (empty($m) && empty($a)) {
    header('Location: admin.php');
    exit();
}

if (in_array($m, array(
    'app',
    'frame',
    'user',
    'pm',
    'pm_client',
    'tag',
    'feed',
    'friend',
    'domain',
    'credit',
    'mail',
    'version'
))) {
    
    $c = '\\uc\\server\\app\\control\\' . ucwords($m) . 'Control';
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
