<?php

namespace uc\server\mail;

class SMTP
{
    protected $host;
    protected $port;
    protected $auth = [
        'on' => false,
        'username' => '',
        'password' => '',
        'from' => ''
    ];
    protected $from = '';

    /**
     * 设置stmp服务器信息
     * @param string $host
     * @param int $port
     */
    public function setServer($host, $port = 25)
    {
        $this->host = $host;
        $this->port = $port;
    }
    
    /**
     * 设置授权帐号信息
     * @param bool $on
     * @param string $username
     * @param string $password
     */
    public function setAuth($on, $username = '', $password = '')
    {
        $this->auth = [
            'on' => $on,
            'username' => $username,
            'password' => $password
        ];
    }
    
    public function setFrom($from)
    {
        $this->from = $from;
    }
    
    public function send($to, $subject, $message, $headers, $delimeter)
    {
        if (!$fp = fsocketopen($this->host, $this->port, $errno, $errstr, 30)) {
            return false;
        }

        stream_set_blocking($fp, true);

        $lastmessage = fgets($fp, 512);
        if (substr($lastmessage, 0, 3) != '220') {
            return false;
        }

        fputs($fp, ($this->auth['on'] ? 'EHLO' : 'HELO') . " discuz\r\n");
        $lastmessage = fgets($fp, 512);
        if (substr($lastmessage, 0, 3) != 220 && substr($lastmessage, 0, 3) != 250) {
            return false;
        }

        while (1) {
            if (substr($lastmessage, 3, 1) != '-' || empty($lastmessage)) {
                break;
            }
            $lastmessage = fgets($fp, 512);
        }

        if ($this->auth['on']) {
            fputs($fp, "AUTH LOGIN\r\n");
            $lastmessage = fgets($fp, 512);
            if (substr($lastmessage, 0, 3) != 334) {
                return false;
            }

            fputs($fp, base64_encode($this->auth['username']) . "\r\n");
            $lastmessage = fgets($fp, 512);
            if (substr($lastmessage, 0, 3) != 334) {
                return false;
            }

            fputs($fp, base64_encode($this->auth['password']) . "\r\n");
            $lastmessage = fgets($fp, 512);
            if (substr($lastmessage, 0, 3) != 235) {
                return false;
            }
        }

        fputs($fp, "MAIL FROM: <" . preg_replace("/.*\<(.+?)\>.*/", "\\1", $this->from) . ">\r\n");
        $lastmessage = fgets($fp, 512);
        if (substr($lastmessage, 0, 3) != 250) {
            fputs($fp, "MAIL FROM: <" . preg_replace("/.*\<(.+?)\>.*/", "\\1", $this->from) . ">\r\n");
            $lastmessage = fgets($fp, 512);
            if (substr($lastmessage, 0, 3) != 250) {
                return false;
            }
        }

        $email_tos = array();
        foreach (explode(',', $to) as $touser) {
            $touser = trim($touser);
            if ($touser) {
                fputs($fp, "RCPT TO: <" . preg_replace("/.*\<(.+?)\>.*/", "\\1", $touser) . ">\r\n");
                $lastmessage = fgets($fp, 512);
                if (substr($lastmessage, 0, 3) != 250) {
                    fputs($fp, "RCPT TO: <" . preg_replace("/.*\<(.+?)\>.*/", "\\1", $touser) . ">\r\n");
                    $lastmessage = fgets($fp, 512);
                    return false;
                }
            }
        }

        fputs($fp, "DATA\r\n");
        $lastmessage = fgets($fp, 512);
        if (substr($lastmessage, 0, 3) != 354) {
            return false;
        }

        $headers .= 'Message-ID: <' . gmdate('YmdHs') . '.' . substr(md5($message . microtime()), 0, 6) . rand(100000, 999999) . '@' . $_SERVER['HTTP_HOST'] . ">{$delimeter}";

        fputs($fp, "Date: " . gmdate('r') . $delimeter);
        fputs($fp, "To: " . $to . $delimeter);
        fputs($fp, "Subject: " . $subject . $delimeter);
        fputs($fp, $headers . $delimeter);
        fputs($fp, $delimeter . $delimeter);
        fputs($fp, "{$message}{$delimeter}{$delimeter}");
        $lastmessage = fgets($fp, 512);
        if (substr($lastmessage, 0, 3) != 250) {
            return false;
        }

        fputs($fp, "QUIT{$delimeter}");
        return true;
    }
}

