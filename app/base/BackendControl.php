<?php

namespace uc\server\app\base;

class BackendControl extends Control
{
    /**
     *
     * @var array 用户信息 
     */
    public $user = [
        'uid' => 0,
        'username' => '',
        'admin' => false,
        'isfounder' => false,
        'allowadminsetting' => false,
        'allowadminapp' => false,
        'allowadminuser' => false,
        'allowadminpm' => false,
        'allowadmincredits' => false,
        'allowadminbadword' => false,
        'allowadmindomain' => false,
        'allowadmindb' => false,
        'allowadmincache' => false,
        'allowadminnote' => false,
    ];

    protected $cookie_status = 1;

    public function __construct()
    {
        parent::__construct();
        
        $this->sid = $this->cookie_status ? getgpc('sid', 'C') : rawurlencode(getgpc('sid', 'R'));
        
        $a = getgpc('a');
        if (! (getgpc('m') == 'user' && ($a == 'login' || $a == 'logout'))) {
            $this->check_priv();
        }
        
        $this->view->sid = $this->sid;
        if ($this->cookie_status) {
            $this->setcookie('sid', $this->sid, 86400);
        }
        
        $this->view->assign('user', $this->user);
        $this->view->assign('iframe', getgpc('iframe'));
    }

    /**
     * 对当前用户的管理权限进行初始化
     */
    protected function check_priv()
    {
        $this->user['username'] = $this->sid_decode($this->sid);
        if (empty($this->user['username'])) {
            $this->jumpToLoginPage();
        }
        
        $this->user['admin'] = true;
        $this->user['isfounder'] = $this->is_founder($this->user['username']);
        if (! $this->user['isfounder']) {
            $admin = $this->db->fetch_first("SELECT a.*, m.* FROM " 
                    . UC_DBTABLEPRE . "admins a LEFT JOIN " 
                    . UC_DBTABLEPRE . "members m USING(uid) " 
                    . "WHERE a.username='{$this->user['username']}'");
            if (empty($admin)) {
                $this->jumpToLoginPage();
            }
            
            $this->user = array_merge($this->user, $admin);
        }
    }

    /**
     * 判断指定用户是否是创始者
     * @param string $username
     * @return bool
     */
    protected function is_founder($username)
    {
        return $username == 'UCenterAdministrator' ? true : false;
    }
    
    protected function jumpToLoginPage()
    {
        header('Location: ' . UC_API . '/admin.php?m=user&a=login&iframe=' 
                . getgpc('iframe', 'G') 
                . ($this->cookie_status ? '' : '&sid=' . $this->sid)
        );
        exit();
    }

    protected function writelog($action, $extra = '')
    {
        $log = dhtmlspecialchars($this->user['username'] . "\t" 
                . $this->onlineip . "\t" . $this->time . "\t$action\t$extra");
        uc_writelog($log, gmdate('Ym', $this->time));
    }

    protected function fetch_plugins()
    {
        $plugindir = UC_ROOT . './plugin';
        
        $d = opendir($plugindir);
        $plugins = [];
        while ($f = readdir($d)) {
            if ($f != '.' && $f != '..' && is_dir($plugindir . '/' . $f)) {
                $pluginxml = file_get_contents("$plugindir/$f/plugin.xml");
                $plugins[$f] = xml_unserialize($pluginxml);
            }
        }
        
        return $plugins;
    }

    public function _call($a, $arg)
    {
        if (method_exists($this, $a) && $a{0} != '_') {
            $this->$a();
        } else {
            exit('Method does not exists');
        }
    }

    /**
     * 将username编码为sid
     * @param string $username
     * @return string
     */
    protected function sid_encode($username)
    {
        $ip = $this->onlineip;
        $agent = $_SERVER['HTTP_USER_AGENT'];
        $authkey = md5($ip . $agent . UC_KEY);
        $check = substr(md5($ip . $agent), 0, 8);
        return rawurlencode($this->authcode("$username\t$check", 'ENCODE', $authkey, 1800));
    }

    /**
     * 将sid解码为username
     * @param type $sid
     * @return string|boolean false代表解码失败
     */
    protected function sid_decode($sid)
    {
        $ip = $this->onlineip;
        $agent = $_SERVER['HTTP_USER_AGENT'];
        $authkey = md5($ip . $agent . UC_KEY);
        $s = $this->authcode(rawurldecode($sid), 'DECODE', $authkey, 1800);
        if (empty($s)) {
            return FALSE;
        }
        @list ($username, $check) = explode("\t", $s);
        if ($check == substr(md5($ip . $agent), 0, 8)) {
            return $username;
        } else {
            return FALSE;
        }
    }
}
