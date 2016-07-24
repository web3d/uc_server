<?php

namespace uc\server\app\control;

use uc\server\app\base\Control;

class VersionControl extends Control
{

    public function oncheck()
    {
        return [
            'file' => UC_SERVER_VERSION,
            'db' => $this->load('setting')->getVersion()
        ];
    }
}
