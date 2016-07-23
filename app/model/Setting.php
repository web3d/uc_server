<?php

namespace uc\server\app\model;

use uc\server\Table;

class Setting extends Table
{
    protected $tableName = 'settings';

    public function get_settings($keys = [])
    {
        
        return $this->findAll($keys ? ['k' => ['in', $keys]] : [], '*', 'k');
    }
}
