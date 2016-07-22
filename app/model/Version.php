<?php

namespace uc\server\app\model;

use uc\server\app\base\Model;

class Version extends Model
{

    protected $tableName = '{{%settings}}';

    public function check()
    {
        $data = $this->db->result_first("SELECT v FROM {{%settings}} WHERE k='version'");
        return $data;
    }
}
