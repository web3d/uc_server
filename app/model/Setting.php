<?php

namespace uc\server\app\model;

use uc\server\app\base\Model;

class Setting extends Model
{
    protected $tableName = '{{%settings}}';

    public function get_settings($keys = '')
    {
        if ($keys) {
            $keys = $this->base->implode($keys);
            $sqladd = "k IN ($keys)";
        } else {
            $sqladd = '1';
        }
        
        $arr = $this->db->fetch_all("SELECT * FROM {{%settings}} WHERE $sqladd");
        if ($arr) {
            foreach ($arr as $k => $v) {
                $arr[$v['k']] = $v['v'];
                unset($arr[$k]);
            }
        }
        return $arr;
    }
}
