<?php

namespace uc\server\app\model;

use uc\server\app\base\Model;

class Cron extends Model
{

    function note_delete_user()
    {
        
    }

    function note_delete_pm()
    {
        $data = $this->db->result_first("SELECT COUNT(*) FROM " . UC_DBTABLEPRE . "badwords");
        return $data;
    }

}
