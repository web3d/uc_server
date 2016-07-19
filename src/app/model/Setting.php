<?php

namespace uc\server\app\model;

class Setting
{

    var $db;

    var $base;

    function __construct(&$base)
    {
        $this->base = $base;
        $this->db = $base->db;
    }

    function get_settings($keys = '')
    {
        if ($keys) {
            $keys = $this->base->implode($keys);
            $sqladd = "k IN ($keys)";
        } else {
            $sqladd = '1';
        }
        $arr = array();
        $arr = $this->db->fetch_all("SELECT * FROM " . UC_DBTABLEPRE . "settings WHERE $sqladd");
        if ($arr) {
            foreach ($arr as $k => $v) {
                $arr[$v['k']] = $v['v'];
                unset($arr[$k]);
            }
        }
        return $arr;
    }
}
