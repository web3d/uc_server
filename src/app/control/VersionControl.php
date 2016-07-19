<?php

namespace uc\server\app\control;

use uc\server\app\base\Control;

class VersionControl extends Control
{

    function __construct()
    {
        parent::__construct();
        $this->load('version');
    }

    function oncheck()
    {
        $db_version = $_ENV['version']->check();
        $return = array(
            'file' => UC_SERVER_VERSION,
            'db' => $db_version
        );
        return $return;
    }
}
