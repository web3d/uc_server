<?php

namespace uc\server\app\control\admin;

use uc\server\app\base\BackendControl as Control;

class FrameControl extends Control
{

    protected $members;

    protected $apps;

    protected $friends;

    public function __construct()
    {
        parent::__construct();
    }

    public function onindex()
    {
        $this->view->assign('sid', $this->view->sid);
        $mainurl = getgpc('mainurl');
        $mainurl = ! empty($mainurl) && preg_match("/^admin\.php\?(&*\w+=\w+)*$/i", $mainurl) 
                ? $mainurl 
                : 'admin.php?m=frame&a=main&sid=' . $this->view->sid;
        $this->view->assign('mainurl', $mainurl);
        $this->view->display('admin_frame_index');
    }

    public function onmain()
    {
        $ucinfo = '<sc' . 'ript language="Jav' . 'aScript" src="ht' . 'tp:/' . '/cus' . 'tome' . 'r.disc' . 'uz.n' . 'et/ucn' . 'ews' . '.p' . 'hp?' . $this->_get_uc_info() . '"></s' . 'cri' . 'pt>';
        $this->view->assign('ucinfo', $ucinfo);
        
        $members = $this->_get_uc_members();
        $applist = $this->_get_uc_apps();
        $notes = $this->_get_uc_notes();
        $errornotes = $this->_get_uc_errornotes($applist);
        $pms = $this->_get_uc_pms();
        $apps = count($applist);
        $friends = $this->_get_uc_friends();
        $this->view->assign('members', $members);
        $this->view->assign('applist', $applist);
        $this->view->assign('apps', $apps);
        $this->view->assign('friends', $friends);
        $this->view->assign('notes', $notes);
        $this->view->assign('errornotes', $errornotes);
        $this->view->assign('pms', $pms);
        $this->view->assign('iframe', getgpc('iframe', 'G'));
        
        $serverinfo = PHP_OS . ' / PHP v' . PHP_VERSION;
        $serverinfo .= @ini_get('safe_mode') ? ' Safe Mode' : NULL;
        $dbversion = $this->db->result_first("SELECT VERSION()");
        $fileupload = @ini_get('file_uploads') ? ini_get('upload_max_filesize') : '<font color="red">' . $lang['no'] . '</font>';
        $dbsize = 0;
        $tablepre = UC_DBTABLEPRE;
        $query = $tables = $this->db->fetch_all("SHOW TABLE STATUS LIKE '$tablepre%'");
        foreach ($tables as $table) {
            $dbsize += $table['Data_length'] + $table['Index_length'];
        }
        $dbsize = $dbsize ? $this->_sizecount($dbsize) : $lang['unknown'];
        $magic_quote_gpc = get_magic_quotes_gpc() ? 'On' : 'Off';
        $allow_url_fopen = ini_get('allow_url_fopen') ? 'On' : 'Off';
        $this->view->assign('serverinfo', $serverinfo);
        $this->view->assign('fileupload', $fileupload);
        $this->view->assign('dbsize', $dbsize);
        $this->view->assign('dbversion', $dbversion);
        $this->view->assign('magic_quote_gpc', $magic_quote_gpc);
        $this->view->assign('allow_url_fopen', $allow_url_fopen);
        
        $this->view->display('admin_frame_main');
    }

    public function onmenu()
    {
        $this->view->assign('plugins', $this->fetch_plugins());
        $this->view->display('admin_frame_menu');
    }

    public function onheader()
    {
        $applist = $this->load('app')->get_apps();
        $cparray = array(
            'UCHOME' => 'admincp.php',
            'DISCUZ' => 'admincp.php',
            'SUPESITE' => 'admincp.php',
            'XSPACE' => 'admincp.php',
            'SUPEV' => 'admincp.php',
            'ECSHOP' => 'admin/index.php',
            'ECMALL' => 'admin.php'
        );
        $admincp = '';
        if (is_array($applist)) {
            foreach ($applist as $k => $app) {
                if (isset($cparray[$app['type']])) {
                    $admincp .= '<li><a href="' . (substr($app['url'], - 1) == '/' ? $app['url'] : $app['url'] . '/') . $cparray[$app['type']] . '" target="_blank">' . $app['name'] . '</a></li>';
                }
            }
        }
        $this->view->assign('admincp', $admincp);
        $this->view->assign('username', $this->user['username']);
        $this->view->display('admin_frame_header');
    }

    private function _get_uc_members()
    {
        if (! $this->members) {
            $this->members = $this->db->result_first("SELECT COUNT(*) FROM {{%members}}");
        }
        return $this->members;
    }

    private function _get_uc_friends()
    {
        $friends = $this->db->result_first("SELECT COUNT(*) FROM {{%friends}}");
        return $friends;
    }

    private function _get_uc_apps()
    {
        if (! $this->apps) {
            $this->apps = $this->db->fetch_all("SELECT * FROM {{%applications}}");
        }
        return $this->apps;
    }

    private function _get_uc_pms()
    {
        $pms = 0;
        for ($i = 0; $i < 10; $i ++) {
            $pms += $this->db->result_first("SELECT COUNT(*) FROM {{%pm_messages_" . (string) $i . '}}');
        }
        return $pms;
    }

    private function _get_uc_notes()
    {
        $notes = $this->db->result_first("SELECT COUNT(*) FROM {{%notelist}} WHERE closed='0'");
        return $notes;
    }

    private function _get_uc_errornotes($applist)
    {
        $notelist = $this->db->fetch_all("SELECT * FROM {{%notelist}} ORDER BY dateline DESC LIMIT 20");
        $error = array();
        foreach ($notelist as $note) {
            foreach ($applist as $k => $app) {
                $error[$k] = 0;
                if ($note['app' . $app['appid']] < 0) {
                    $error[$k] ++;
                }
            }
        }
        return $error;
    }

    private function _sizecount($filesize)
    {
        if ($filesize >= 1073741824) {
            $filesize = round($filesize / 1073741824 * 100) / 100 . ' GB';
        } elseif ($filesize >= 1048576) {
            $filesize = round($filesize / 1048576 * 100) / 100 . ' MB';
        } elseif ($filesize >= 1024) {
            $filesize = round($filesize / 1024 * 100) / 100 . ' KB';
        } else {
            $filesize = $filesize . ' Bytes';
        }
        return $filesize;
    }

    private function _get_uc_info()
    {
        $update = array(
            'uniqueid' => UC_SITEID,
            'version' => UC_SERVER_VERSION,
            'release' => UC_SERVER_RELEASE,
            'php' => PHP_VERSION,
            'mysql' => $this->db->version(),
            'charset' => UC_CHARSET
        );
        $updatetime = @filemtime(UC_ROOT . './data/updatetime.lock');
        if (empty($updatetime) || ($this->time - $updatetime > 3600 * 4)) {
            @touch(UC_ROOT . './data/updatetime.lock');
            $update['members'] = $this->_get_uc_members();
            $update['friends'] = $this->_get_uc_friends();
            $apps = $this->_get_uc_apps();
            if ($apps) {
                foreach ($apps as $app) {
                    $update['app_' . $app['appid']] = $app['name'] . "\t" . $app['url'] . "\t" . $app['type'];
                }
            }
        }
        
        $data = '';
        foreach ($update as $key => $value) {
            $data .= $key . '=' . rawurlencode($value) . '&';
        }
        
        return 'update=' . rawurlencode(base64_encode($data)) . '&md5hash=' . substr(md5($_SERVER['HTTP_USER_AGENT'] . implode('', $update) . $this->time), 8, 8) . '&timestamp=' . $this->time;
    }
}
