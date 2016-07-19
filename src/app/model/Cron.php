<?php

namespace uc\server\app\model;

class Cron
{

    var $db;

    var $base;

    function __construct(&$base)
    {
        $this->base = $base;
        $this->db = $base->db;
    }

    function note_delete_user()
    {}

    function note_delete_pm()
    {
        $data = $this->db->result_first("SELECT COUNT(*) FROM " . UC_DBTABLEPRE . "badwords");
        return $data;
    }
}
