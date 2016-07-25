<?php

namespace uc\server\app\model;

use uc\server\Table;

/**
 * 应用模型
 */
class App extends Table
{
    protected $name = 'applications';

    public function get_apps(string $col = '*', array $where = [])
    {
        $arr = $this->findAll($where, $col, 'appid');
        foreach ($arr as $k => $v) {
            isset($v['extra']) && ! empty($v['extra']) && $v['extra'] = unserialize($v['extra']);
            if ($tmp = $this->base->authcode($v['authkey'], 'DECODE', UC_MYKEY)) {
                $v['authkey'] = $tmp;
            }
            $arr[$k] = $v;
        }
        return $arr;
    }

    public function get_app_by_appid(int $appid, bool $includecert = FALSE)
    {
        $arr = $this->find(['appid' => $appid]);
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

    public function delete_apps(array $appids)
    {
        return $this->delete(['appid' => $appids]);
    }

    public function alter_app_table($appid, $operation = 'ADD')
    {
        if ($operation == 'ADD') {
            $this->getCommand()
                    ->addColumn('{{%notelist}}', "app$appid", 'tinyint NOT NULL')
                    ->execute();
        } else {
            $this->getCommand()
                    ->dropColumn('{{%notelist}}', "app$appid")
                    ->execute();
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
