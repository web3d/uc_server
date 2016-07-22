<?php

namespace uc\server\app\model;

use uc\server\app\base\Model;
use ucs\helpers\ArrayHelper;

/**
 * 插件管理
 */
class Plugin extends Model
{

    public function get_plugins()
    {
        $arr = array();
        $dir = UC_ROOT . './plugin';
        $d = opendir($dir);
        while ($f = readdir($d)) {
            if ($f != '.' && $f != '..' && $f != '.svn' && is_dir($dir . '/' . $f)) {
                $s = file_get_contents($dir . '/' . $f . '/plugin.xml');
                $arr1 = xml_unserialize($s);
                $arr1['dir'] = $f;
                unset($arr1['lang']);
                $arr[] = $arr1;
            }
        }
        
        ArrayHelper::multisort($arr, 'tabindex');
        
        return $arr;
    }

    public function get_plugin($pluginname)
    {
        $f = file_get_contents(UC_ROOT . "./plugin/$pluginname/plugin.xml");
        return xml_unserialize($f);
    }

    public function cert_get_file()
    {
        return UC_ROOT . './data/tmp/ucenter_' . substr(md5(UC_KEY), 0, 16) . '.cert';
    }

    public function cert_dump_encode($arr, $life = 0)
    {
        $s = "# UCenter Applications Setting Dump\n" . "# Version: UCenter " . UC_SERVER_VERSION . "\n" . "# Time: " . $this->base->time . "\n" . "# Expires: " . ($this->base->time + $life) . "\n" . "# From: " . UC_API . "\n" . "#\n" . "# This file was BASE64 encoded\n" . "#\n" . "# UCenter Community: http://www.discuz.net\n" . "# Please visit our website for latest news about UCenter\n" . "# --------------------------------------------------------\n\n\n" . wordwrap(base64_encode(serialize($arr)), 50, "\n", 1);
        return $s;
    }

    public function cert_dump_decode($certfile)
    {
        $s = @file_get_contents($certfile);
        if (empty($s)) {
            return array();
        }
        preg_match("/# Expires: (.*?)\n/", $s, $m);
        if (empty($m[1]) || $m[1] < $this->base->time) {
            unlink($certfile);
            return array();
        }
        $s = preg_replace("/(#.*\s+)*/", '', $s);
        $arr = daddslashes(unserialize(base64_decode($s)), 1);
        return $arr;
    }
}
