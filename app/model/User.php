<?php

namespace uc\server\app\model;

use uc\server\Table;

class User extends Table
{

    protected $name = 'members';

    public function get_user_by_uid($uid)
    {
        return $this->find(['uid' => $uid]);
    }

    public function get_user_by_username($username)
    {
        return $this->find(['username' => $username]);
    }

    public function get_user_by_email($email)
    {
        return $this->find(['email' => $email]);
    }

    public function check_username($username)
    {
        $guestexp = '\xA1\xA1|\xAC\xA3|^Guest|^\xD3\xCE\xBF\xCD|\xB9\x43\xAB\xC8';
        $len = $this->dstrlen($username);
        if ($len > 15 || $len < 3 || preg_match("/\s+|^c:\\con\\con|[%,\*\"\s\<\>\&]|$guestexp/is", $username)) {
            return FALSE;
        } else {
            return TRUE;
        }
    }

    public function dstrlen($str)
    {
        if (strtolower(UC_CHARSET) != 'utf-8') {
            return strlen($str);
        }
        $count = 0;
        for ($i = 0; $i < strlen($str); $i ++) {
            $value = ord($str[$i]);
            if ($value > 127) {
                $count ++;
                if ($value >= 192 && $value <= 223)
                    $i ++;
                elseif ($value >= 224 && $value <= 239)
                    $i = $i + 2;
                elseif ($value >= 240 && $value <= 247)
                    $i = $i + 3;
            }
            $count ++;
        }
        return $count;
    }

    public function check_mergeuser($username)
    {
        return $this->from('{{%mergemembers}}')
                ->where(['appid' => $this->base->app['appid'], 'username' => $username])
                ->count();
    }

    public function check_usernamecensor($username)
    {
        $_CACHE['badwords'] = $this->base->cache('badwords');
        $censorusername = $this->base->get_setting('censorusername');
        $censorusername = $censorusername['censorusername'];
        $censorexp = '/^(' . str_replace(array(
            '\\*',
            "\r\n",
            ' '
        ), array(
            '.*',
            '|',
            ''
        ), preg_quote(($censorusername = trim($censorusername)), '/')) . ')$/i';
        $usernamereplaced = isset($_CACHE['badwords']['findpattern']) && ! empty($_CACHE['badwords']['findpattern']) ? @preg_replace($_CACHE['badwords']['findpattern'], $_CACHE['badwords']['replace'], $username) : $username;
        if (($usernamereplaced != $username) || ($censorusername && preg_match($censorexp, $username))) {
            return FALSE;
        } else {
            return TRUE;
        }
    }

    public function check_usernameexists($username)
    {
        return $this->select('username')
                ->from($this->getName())
                ->where(['username' => $username])
                ->scalar();
    }

    public function check_emailformat($email)
    {
        return strlen($email) > 6 && strlen($email) <= 32 && preg_match("/^([a-z0-9\-_.+]+)@([a-z0-9\-]+[.][a-z0-9\-.]+)$/", $email);
    }

    public function check_emailaccess($email)
    {
        $setting = $this->base->get_setting(array(
            'accessemail',
            'censoremail'
        ));
        $accessemail = $setting['accessemail'];
        $censoremail = $setting['censoremail'];
        $accessexp = '/(' . str_replace("\r\n", '|', preg_quote(trim($accessemail), '/')) . ')$/i';
        $censorexp = '/(' . str_replace("\r\n", '|', preg_quote(trim($censoremail), '/')) . ')$/i';
        if ($accessemail || $censoremail) {
            if (($accessemail && ! preg_match($accessexp, $email)) || ($censoremail && preg_match($censorexp, $email))) {
                return FALSE;
            } else {
                return TRUE;
            }
        } else {
            return TRUE;
        }
    }

    public function check_emailexists($email, $username = '')
    {
        $this->select('email')
                ->from($this->getName())
                ->where(['email' => $email]);
        if ($username !== '') {
            $this->andWhere(['<>', 'username', $username]);
        }
        
        return $this->scalar();
    }

    public function check_login($username, $password, &$user)
    {
        $user = $this->get_user_by_username($username);
        if (empty($user['username'])) {
            return - 1;
        } elseif ($user['password'] != md5(md5($password) . $user['salt'])) {
            return - 2;
        }
        return $user['uid'];
    }

    public function add_user(string $username, string $password, string $email, int $uid = 0, int $questionid = 0, string $answer = '', string $regip = '')
    {
        $salt = substr(uniqid(rand()), - 6);
        
        $user = [
            'username' => $username,
            'password' => md5(md5($password) . $salt),
            'email' => $email,
            'regip' => empty($regip) ? $this->base->onlineip : $regip,
            'regdate' => $this->base->time,
            'salt' => $salt,
            'secques' => ($questionid > 0) ? $this->quescrypt($questionid, $answer) : ''
        ];
        
        if ($uid > 0) {
            $user['uid'] = $uid;
        }
        
        $uid = $this->insert($user);
        
        $this->getCommand()->insert('{{%memberfields}}', ['uid' => $uid]);
        
        return $uid;
    }

    public function edit_user(string $username, string $oldpw, string $newpw, string $email, bool $ignoreoldpw = false, int $questionid = -1, string $answer = '')
    {
        $user = $this->find(['username' => $username], 'username, uid, password, salt');
        
        if ($ignoreoldpw) {
            if ($this->from('{{%protectedmembers}}')->where(['uid' => $user['uid']])->count()) {
                return - 8;
            }
        }
        
        if (! $ignoreoldpw && $data['password'] != md5(md5($oldpw) . $data['salt'])) {
            return - 1;
        }
        
        $data = [];
        if ($newpw) {
            $data['password'] = md5(md5($newpw) . $user['salt']);
        }
        if ($email) {
            $data['email'] = $email;
        }
        
        if ($questionid !== -1) {
            $data['secques'] = ($questionid > 0) ? $this->quescrypt($questionid, $answer) : '';
        }
        if ($data) {
            return $this->update($data, ['username' => $username]);
        } else {
            return - 7;
        }
    }

    public function delete_user(array $uidsarr)
    {
        if (! $uidsarr) {
            return 0;
        }
        
        $puids = $this->select('uid')
                ->from('{{%protectedmembers}}')
                ->where(['uid' => $uidsarr])
                ->column();
        
        $uids = array_diff($uidsarr, $puids);
        if (!$uids) {
            return 0;
        }
        
        $result = $this->delete(['uid' => $uids]);
        
        $this->getCommand()->delete('{{%memberfields}}', ['uid' => $uids]);
        $this->delete_useravatar($uidsarr);
        $this->base->load('note')->add('deleteuser', "ids=$uids");
        
        return $result;
    }

    public function delete_useravatar(array $uidsarr)
    {
        foreach ($uidsarr as $uid) {
            file_exists($avatar_file = UC_DATADIR . './avatar/' . $this->base->get_avatar($uid, 'big', 'real')) && unlink($avatar_file);
            file_exists($avatar_file = UC_DATADIR . './avatar/' . $this->base->get_avatar($uid, 'middle', 'real')) && unlink($avatar_file);
            file_exists($avatar_file = UC_DATADIR . './avatar/' . $this->base->get_avatar($uid, 'small', 'real')) && unlink($avatar_file);
            file_exists($avatar_file = UC_DATADIR . './avatar/' . $this->base->get_avatar($uid, 'big')) && unlink($avatar_file);
            file_exists($avatar_file = UC_DATADIR . './avatar/' . $this->base->get_avatar($uid, 'middle')) && unlink($avatar_file);
            file_exists($avatar_file = UC_DATADIR . './avatar/' . $this->base->get_avatar($uid, 'small')) && unlink($avatar_file);
        }
    }

    public function get_total_num($sqladd = '')
    {
        return $this->from($this->getName())->where($sqladd)->count();
    }

    public function get_list(int $page, int $ppp, int $totalnum, $sqladd)
    {
        return $this->from($this->getName())
                ->where($sqladd)
                ->offset($this->base->page_get_start($page, $ppp, $totalnum))
                ->limit($ppp)
                ->all();
    }

    public function name2id(array $usernamesarr)
    {
        return $this->select('uid')
                ->from($this->getName())
                ->where(['username' => $usernamesarr])
                ->column();
    }

    public function id2name(array $uidarr)
    {
        $users = $this->select('uid, username')
                ->from($this->getName())
                ->where(['uid' => $uidarr])
                ->all();
        
        $arr = [];
        foreach ($users as $user) {
            $arr[$user['uid']] = $user['username'];
        }
        
        return $arr;
    }

    public function quescrypt(int $questionid, string $answer)
    {
        return $questionid > 0 && $answer != '' ? substr(md5($answer . md5($questionid)), 16, 8) : '';
    }

    /**
     * 判断是否能继续尝试登录
     * @param string $username
     * @param string $ip
     * @return int
     */
    public function can_do_login(string $username, string $ip = '')
    {
        $username = substr(md5($username), 8, 15);
        if (! $ip) {
            $ip = $this->base->onlineip;
        }
        
        $ip_check = $user_check = array();
        $query = $this->db->query("SELECT * FROM {{%failedlogins}} WHERE ip='" . $ip . "' OR ip='$username'");
        while ($row = $this->db->fetch_array($query)) {
            if ($row['ip'] === $username) {
                $user_check = $row;
            } elseif ($row['ip'] === $ip) {
                $ip_check = $row;
            }
        }
        
        $expire = 15 * 60;
        if (empty($ip_check) || ($this->base->time - $ip_check['lastupdate'] > $expire)) {
            $ip_check = array();
            $this->getCommand()
                    ->setSql("REPLACE INTO {{%failedlogins}} (ip, count, lastupdate) VALUES ('{$ip}', '0', '{$this->base->time}')")
                    ->execute();
        }
        
        if (empty($user_check) || ($this->base->time - $user_check['lastupdate'] > $expire)) {
            $user_check = array();
            $this->getCommand()
                    ->setSql("REPLACE INTO {{%failedlogins}} (ip, count, lastupdate) VALUES ('{$username}', '0', '{$this->base->time}')")
                    ->execute();
        }
        
        $check_times = $this->base->settings['login_failedtime'] < 1 
        ? 5 
        : $this->base->settings['login_failedtime'];
        
        if ($ip_check || $user_check) {
            $time_left = min(($check_times - $ip_check['count']), ($check_times - $user_check['count']));
            return $time_left;
        }
        
        $this->getCommand()
                ->delete('{{%failedlogins}}', 
                        ['<', 'lastupdate', $this->base->time - ($expire + 1)]);
        
        return $check_times;
    }

    public function loginfailed(string $username, string $ip = '')
    {
        $result = $this->getCommand()
                ->update('{{%failedlogins}}', 
                        ['count' => 'count+1', 'lastupdate' => $this->base->time], 
                        ['ip' => [$ip ? : $this->base->onlineip, 
                            substr(md5($username), 8, 15)]]
                );
        return $result;
    }
}