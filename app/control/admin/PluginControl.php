<?php

namespace uc\server\app\control\admin;

use uc\server\app\base\BackendControl as Control;

/**
 * 插件控制器
 * 既是插件控制器的基类,也是插件端入口类
 */
class PluginControl extends Control
{

    public function serialize($s, $htmlon = 0)
    {
        parent::serialize($s, $htmlon);
    }

    var $plugin = array();

    var $plugins = array();

    public function __construct()
    {
        parent::__construct();
        $this->check_priv();
        if (! $this->user['isfounder']) {
            $this->message('no_permission_for_this_module');
        }
        $a = getgpc('a');
        $this->load('plugin');
        $this->plugin = $_ENV['plugin']->get_plugin($a);
        $this->plugins = $_ENV['plugin']->get_plugins();
        if (empty($this->plugin)) {
            $this->message('read_plugin_invalid');
        }
        $this->view->assign('plugin', $this->plugin);
        $this->view->assign('plugins', $this->plugins);
        $this->view->languages = $this->plugin['lang'];
        $this->view->tpldir = UC_ROOT . './plugin/' . $a;
        $this->view->objdir = UC_DATADIR . './view';
    }

    public function _call($a, $arg)
    {
        
        $a = getgpc('a');
        if (! preg_match("/^[\w]{1,64}$/", $a)) {
            exit('Argument Invalid');
        }

        $pc_class = '\\uc\\server\\plugin\\' . $a . '\\Control';
        $pc = new $pc_class;
        
        $do = getgpc('do');
        $do = empty($do) ? 'onindex' : 'on' . $do;
        if (method_exists($pc, $do)) {
            $pc->$do();
        }

        exit('Plugin module not found');
        
    }
}

