<?php

namespace uc\server\app\model;

class Version
{

    var $db;

    var $base;

    function __construct(&$base)
    {
        $this->base = $base;
        $this->db = $base->db;
    }

    function check()
    {
        $data = $this->db->result_first("SELECT v FROM " . UC_DBTABLEPRE . "settings WHERE k='version'");
        return $data;
    }
}
