<?php

namespace uc\server\app\control;

use uc\server\app\base\Control;

class DomainControl extends Control
{

    function __construct()
    {
        parent::__construct();
        $this->init_input();
        $this->load('domain');
    }

    function onls()
    {
        return $_ENV['domain']->get_list(1, 9999, 9999);
    }
}
