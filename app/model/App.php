<?php

namespace uc\server\app\model;

use uc\server\app\base\Model;

/**
 * 应用模型
 */
class App extends Model
{
    protected $tableName = '{{%applications}}';

    public function get_apps($col = '*', $where = '')
    {
        $arr = $this->db->fetch_all("SELECT $col FROM {{%applications}}" . ($where ? ' WHERE ' . $where : ''), 'appid');
        foreach ($arr as $k => $v) {
            isset($v['extra']) && ! empty($v['extra']) && $v['extra'] = unserialize($v['extra']);
            if ($tmp = $this->base->authcode($v['authkey'], 'DECODE', UC_MYKEY)) {
                $v['authkey'] = $tmp;
            }
            $arr[$k] = $v;
        }
        return $arr;
    }

    public function get_app_by_appid($appid, $includecert = FALSE)
    {
        $appid = intval($appid);
        $arr = $this->db->fetch_first("SELECT * FROM {{%applications}} WHERE appid='$appid'");
        $arr['extra'] = unserialize($arr['extra']);
        if ($tmp = $this->base->authcode($arr['authkey'], 'DECODE', UC_MYKEY)) {
            $arr['authkey'] = $tmp;
        }
        if ($includecert) {
            $this->load('plugin');
            $certfile = $_ENV['plugin']->cert_get_file();
            $appdata = $_ENV['plugin']->cert_dump_decode($certfile);
            if (is_array($appdata[$appid])) {
                $arr += $appdata[$appid];
            }
        }
        return $arr;
    }

    public function delete_apps($appids)
    {
        $appids = $this->base->implode($appids);
        $this->db->execute("DELETE FROM {{%applications}} WHERE appid IN ($appids)");
        return $this->db->affected_rows();
    }

    public function alter_app_table($appid, $operation = 'ADD')
    {
        if ($operation == 'ADD') {
            $this->db->execute("ALTER TABLE {{%notelist}} ADD COLUMN app$appid tinyint NOT NULL", 'SILENT');
        } else {
            $this->db->execute("ALTER TABLE {{%notelist}} DROP COLUMN app$appid", 'SILENT');
        }
    }

    public function test_api($url, $ip = '')
    {
        $this->base->load('misc');
        if (! $ip) {
            $ip = $_ENV['misc']->get_host_by_url($url);
        }
        
        if ($ip < 0) {
            return FALSE;
        }
        return $_ENV['misc']->dfopen($url, 0, '', '', 1, $ip);
    }
}
