<?php

namespace uc\server\app\model;

use uc\server\app\base\Model;

class Tag extends Model
{

    protected $tableName = '{{%tags}}';

    public function get_tag_by_name($tagname)
    {
        $arr = $this->db->fetch_all("SELECT * FROM {{%tags}} WHERE tagname='$tagname'");
        return $arr;
    }

    public function get_template($appid)
    {
        $result = $this->db->result_first("SELECT tagtemplates FROM {{%applications}} WHERE appid='$appid'");
        return $result;
    }

    public function updatedata($appid, $data)
    {
        $appid = intval($appid);
        $data = xml_unserialize($data);
        $this->base->load('app');
        $data[0] = addslashes($data[0]);
        $datanew = array();
        if (is_array($data[1])) {
            foreach ($data[1] as $r) {
                $datanew[] = $_ENV['misc']->array2string($r);
            }
        }
        $tmp = $_ENV['app']->get_apps('type', ['appid' => $appid]);
        $datanew = addslashes($tmp[0]['type'] . "\t" . implode("\t", $datanew));
        if (! empty($data[0])) {
            $return = $this->db->result_first("SELECT count(*) FROM {{%tags}} WHERE tagname='$data[0]' AND appid='$appid'");
            if ($return) {
                $this->db->execute("UPDATE {{%tags}} SET data='$datanew', expiration='" . $this->base->time . "' WHERE tagname='$data[0]' AND appid='$appid'");
            } else {
                $this->db->execute("INSERT INTO {{%tags}} (tagname, appid, data, expiration) VALUES ('$data[0]', '$appid', '$datanew', '" . $this->base->time . "')");
            }
        }
    }

    public function formatcache($appid, $tagname)
    {
        $return = $this->db->result_first("SELECT count(*) FROM {{%tags}} WHERE tagname='$tagname' AND appid='$appid'");
        if ($return) {
            $this->db->execute("UPDATE {{%tags}} SET expiration='0' WHERE tagname='$tagname' AND appid='$appid'");
        } else {
            $this->db->execute("INSERT INTO {{%tags}} (tagname, appid, expiration) VALUES ('$tagname', '$appid', '0')");
        }
    }
}
