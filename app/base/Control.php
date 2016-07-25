<?php

namespace uc\server\app\base;

class Control implements \uc\server\ControlInterface
{

    /**
     *
     * @var string 当前用户标识,encode过的
     */
    public $sid;

    public $time;

    public $onlineip;

    /**
     *
     * @var \uc\server\Db
     */
    public $db;

    /**
     *
     * @var \uc\server\Template 
     */
    protected $view;

    /**
     *
     * @var array 用户信息 
     */
    public $user = [
        'uid' => 0,
        'username' => '',
    ];

    public $settings = array();

    public $cache = array();

    public $app = array();

    public $lang = array();

    public $input = array();

    public function __construct()
    {
        $this->init_var();
        $this->init_db();
        $this->init_cache();
        $this->init_app();
        $this->init_user();
        $this->init_template();
        $this->init_note();
        $this->init_mail();
    }

    private function init_var()
    {
        $this->time = time();
        
        $this->onlineip = uc_clientip();
        
        define('FORMHASH', $this->formhash());
        $_GET['page'] = max(1, intval(getgpc('page')));
        
        include_once UC_APPDIR . '/view/default/main.lang.php';
        $this->lang = $lang;
    }

    private function init_cache()
    {
        $this->settings = $this->cache('settings');
        $this->cache['apps'] = $this->cache('apps');
        if (PHP_VERSION > '5.1') {
            $timeoffset = intval($this->settings['timeoffset'] / 3600);
            @date_default_timezone_set('Etc/GMT' . ($timeoffset > 0 ? '-' : '+') . (abs($timeoffset)));
        }
    }

    private function init_input($getagent = '')
    {
        $input = getgpc('input', 'R');
        if ($input) {
            $input = $this->authcode($input, 'DECODE', $this->app['authkey']);
            parse_str($input, $this->input);
            $this->input = daddslashes($this->input, 1, TRUE);
            $agent = $getagent ? $getagent : $this->input['agent'];
            
            if (($getagent && $getagent != $this->input['agent']) || (! $getagent && md5($_SERVER['HTTP_USER_AGENT']) != $agent)) {
                exit('Access denied for agent changed');
            } elseif ($this->time - $this->input('time') > 3600) {
                exit('Authorization has expired');
            }
        }
        if (empty($this->input)) {
            exit('Invalid input');
        }
    }

    private function init_db()
    {
        $this->db = \uc\server\Db::instance();
    }

    private function init_app()
    {
        $appid = intval(getgpc('appid'));
        $appid && $this->app = $this->cache['apps'][$appid];
    }

    private function init_user()
    {
        if (isset($_COOKIE['uc_auth'])) {
            @list ($uid, $username, $agent) = explode('|', $this->authcode($_COOKIE['uc_auth'], 'DECODE', ($this->input ? $this->app['appauthkey'] : UC_KEY)));
            if ($agent != md5($_SERVER['HTTP_USER_AGENT'])) {
                $this->setcookie('uc_auth', '');
            } else {
                $this->user['uid'] = $uid;
                $this->user['username'] = $username;
            }
        }
    }

    private function init_template()
    {
        $this->view = new \uc\server\Template();
        $this->view->assign('dbhistories', $this->db->histories);
        $this->view->assign('charset', UC_CHARSET);
        $this->view->assign('dbquerynum', $this->db->querynum);
        $this->view->assign('user', $this->user);
    }

    private function init_note()
    {
        if ($this->note_exists() && ! getgpc('inajax')) {
            $this->load('note')->send();
        }
    }

    private function init_mail()
    {
        if ($this->mail_exists() && ! getgpc('inajax')) {
            $this->load('mail')->send();
        }
    }

    public function authcode($string, $operation = 'DECODE', $key = '', $expiry = 0)
    {
        return uc_authcode($string, $operation, $key ? : UC_KEY, $expiry);
    }

    protected function page($num, $perpage, $curpage, $mpurl)
    {
        
        return (new \uc\server\Pager())->output($num, $perpage, $curpage, $mpurl);
    }

    public function page_get_start($page, $ppp, $totalnum)
    {
        return (new \uc\server\Pager())->getStart($page, $ppp, $totalnum);
    }

    public function load($model, $base = NULL, $release = '')
    {
        $base = $base ? $base : $this;
        if (empty($_ENV[$model])) {
            $class = '\\uc\\server\\app\\model\\' . ucwords($model);
            $_ENV[$model] = new $class($base);
        }
        return $_ENV[$model];
    }

    public function get_setting($k = array(), $decode = FALSE)
    {
        $return = array();
        $sqladd = $k ? "WHERE k IN (" . $this->implode($k) . ")" : '';
        $settings = $this->db->fetch_all("SELECT * FROM " . UC_DBTABLEPRE . "settings $sqladd");
        if (is_array($settings)) {
            foreach ($settings as $arr) {
                $return[$arr['k']] = $decode ? unserialize($arr['v']) : $arr['v'];
            }
        }
        return $return;
    }

    public function set_setting($k, $v, $encode = FALSE)
    {
        $v = is_array($v) || $encode ? addslashes(serialize($v)) : $v;
        $this->db->execute("REPLACE INTO " . UC_DBTABLEPRE . "settings SET k='$k', v='$v'");
    }

    /**
     * 显示提示消息页面
     * @param string $message 提示消息,字符串中可以定义占位符 如 :username
     * @param string $redirect 跳转url 可以是站内url 也可是站外url
     * @param int $type 0 - 普通页面 1 - 客户端
     * @param array $vars 消息格式中相应待动态替换的值定义 [':username' => 'jimmy']
     */
    protected function message($message, $redirect = '', $type = 0, $vars =[])
    {
        include UC_APPDIR . '/view/default/messages.lang.php';
        if ($lang) {
            $this->lang = array_merge($this->lang, $lang);
        }
        if (isset($this->lang[$message])) {
            $message = str_replace(array_keys($vars), array_values($vars), $this->lang[$message]);
        }
        $this->view->assign('message', $message);
        if (! strpos($redirect, 'sid=') && (false === strpos($redirect, 'http://'))) {
                $redirect .= ! strpos($redirect, '?') ? '?' : '&';
                $redirect .= 'sid=' . $this->sid;
        }
        $this->view->assign('redirect', $redirect);
        
        $this->view->display(($type == 1) ? 'message_client' : 'message');
        exit();
    }

    protected function formhash()
    {
        return uc_formhash($this->time, UC_KEY);
    }

    protected function submitcheck()
    {
        return getgpc('formhash', 'P') == FORMHASH ? true : false;
    }

    public function date($time, $type = 3)
    {
        return uc_gmdate($time, $type, $this->settings['dateformat'], $this->settings['timeformat'], $this->settings['timeoffset']);
    }

    public function implode($arr)
    {
        return "'" . implode("','", (array) $arr) . "'";
    }

    protected function set_home($uid, $dir = '.')
    {
        $uid = sprintf("%09d", $uid);
        $dir1 = substr($uid, 0, 3);
        $dir2 = substr($uid, 3, 2);
        $dir3 = substr($uid, 5, 2);
        ! is_dir($dir . '/' . $dir1) && mkdir($dir . '/' . $dir1, 0777);
        ! is_dir($dir . '/' . $dir1 . '/' . $dir2) && mkdir($dir . '/' . $dir1 . '/' . $dir2, 0777);
        ! is_dir($dir . '/' . $dir1 . '/' . $dir2 . '/' . $dir3) && mkdir($dir . '/' . $dir1 . '/' . $dir2 . '/' . $dir3, 0777);
    }

    protected function get_home($uid)
    {
        $uid = sprintf("%09d", $uid);
        $dir1 = substr($uid, 0, 3);
        $dir2 = substr($uid, 3, 2);
        $dir3 = substr($uid, 5, 2);
        return $dir1 . '/' . $dir2 . '/' . $dir3;
    }

    protected function get_avatar($uid, $size = 'big', $type = '')
    {
        $size = in_array($size, array(
            'big',
            'middle',
            'small'
        )) ? $size : 'big';
        $uid = abs(intval($uid));
        $uid = sprintf("%09d", $uid);
        $dir1 = substr($uid, 0, 3);
        $dir2 = substr($uid, 3, 2);
        $dir3 = substr($uid, 5, 2);
        $typeadd = $type == 'real' ? '_real' : '';
        return $dir1 . '/' . $dir2 . '/' . $dir3 . '/' . substr($uid, - 2) . $typeadd . "_avatar_$size.jpg";
    }

    public function cache($cachefile)
    {
        static $_CACHE = array();
        if (! isset($_CACHE[$cachefile])) {
            $cachepath = UC_DATADIR . './cache/' . $cachefile . '.php';
            if (! file_exists($cachepath)) {
                $this->load('cache');
                $_ENV['cache']->updatedata($cachefile);
            } else {
                include $cachepath;
            }
        }
        return $_CACHE[$cachefile];
    }

    protected function input($k)
    {
        return isset($this->input[$k]) ? (is_array($this->input[$k]) ? $this->input[$k] : trim($this->input[$k])) : NULL;
    }

    protected function serialize($s, $htmlon = 0)
    {
        return xml_serialize($s, $htmlon);
    }

    protected function unserialize($s)
    {
        return xml_unserialize($s);
    }

    protected function cutstr($string, $length, $dot = ' ...')
    {
        return uc_custr($string, $length, $dot);
    }

    protected function setcookie($key, $value, $life = 0, $httponly = false)
    {
        (! defined('UC_COOKIEPATH')) && define('UC_COOKIEPATH', '/');
        (! defined('UC_COOKIEDOMAIN')) && define('UC_COOKIEDOMAIN', '');
        
        if ($value == '' || $life < 0) {
            $value = '';
            $life = - 1;
        }
        
        $life = $life > 0 ? $this->time + $life : ($life < 0 ? $this->time - 31536000 : 0);
        $path = $httponly && PHP_VERSION < '5.2.0' ? UC_COOKIEPATH . "; HttpOnly" : UC_COOKIEPATH;
        $secure = $_SERVER['SERVER_PORT'] == 443 ? 1 : 0;
        if (PHP_VERSION < '5.2.0') {
            setcookie($key, $value, $life, $path, UC_COOKIEDOMAIN, $secure);
        } else {
            setcookie($key, $value, $life, $path, UC_COOKIEDOMAIN, $secure, $httponly);
        }
    }

    protected function note_exists()
    {
        $noteexists = $this->db->result_first("SELECT value FROM " . UC_DBTABLEPRE . "vars WHERE name='noteexists'");
        if (empty($noteexists)) {
            return FALSE;
        } else {
            return TRUE;
        }
    }

    protected function mail_exists()
    {
        $mailexists = $this->db->result_first("SELECT value FROM " . UC_DBTABLEPRE . "vars WHERE name='mailexists'");
        if (empty($mailexists)) {
            return FALSE;
        } else {
            return TRUE;
        }
    }

    protected function dstripslashes($string)
    {
        if (is_array($string)) {
            foreach ($string as $key => $val) {
                $string[$key] = $this->dstripslashes($val);
            }
        } else {
            $string = stripslashes($string);
        }
        return $string;
    }
}
