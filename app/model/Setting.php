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
    
    /**
     * 
     * @return string 从设置中取出系统db结构版本号
     */
    public function getVersion()
    {
        return $this->select('v')->where(['k' => 'version'])->scalar();
    }
}
