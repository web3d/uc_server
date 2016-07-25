<?php

namespace uc\server\app\control\admin;

use uc\server\app\base\BackendControl as Control;

class FeedControl extends Control
{

    var $apps = array();

    var $operations = array();

    function __construct()
    {
        parent::__construct();
        if (! $this->user['isfounder'] && ! $this->user['allowadminnote']) {
            $this->message('no_permission_for_this_module');
        }
        $this->load('feed');
        $this->apps = $this->cache['apps'];
        $this->check_priv();
    }

    function onls()
    {
        $page = getgpc('page');
        $delete = getgpc('delete', 'P');
        $num = $_ENV['feed']->get_total_num();
        $feedlist = $_ENV['feed']->get_list($page, UC_PPP, $num);
        $multipage = $this->page($num, UC_PPP, $page, 'admin.php?m=feed&a=ls');
        
        $this->view->assign('feedlist', $feedlist);
        $this->view->assign('multipage', $multipage);
        
        $this->view->display('admin_feed');
    }
}
