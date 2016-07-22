<?php

namespace uc\server;

class Mailer
{

    protected $setting = [
        'mailsend' => 1,
        'mailserver' => '',
        'mailport' => 25,
        'mailsilent' => 1,
        'maildelimiter' => 1, // 0  1 2
        'mailusername' => 1,
        'mailauth' => 0,
    ];
    
    public function __construct(array $setting)
    {
        if (empty($setting['mailport'])) {
            unset($setting);
        }
        
        if ($setting) {
            $this->setting = array_merge($this->setting, $setting);
        }
    }

    public function send(array $mail, $appName)
    {
        if ($this->setting['mailsilent']) {
            error_reporting(0);
        }
        
        $mail['subject'] = $this->prepareSubject($mail['subject'], $appName, $mail['charset']);
        $mail['message'] = $this->prepareMessage($mail['message']);

        $mail['email_to'] = $this->prepreEmailTo($mail['email_to'], $mail['charset']);
        
        $email_from = $this->prepareFrom($mail['frommail'], $mail['charset'], $appName);
        $delimeter = $this->prepareDelimiter($this->setting['maildelimiter']);

        $headers = $this->prepareHeaders(
                $email_from, 
                $delimeter, 
                $mail['charset'], 
                $mail['htmlon'], 
                constant('UC_SERVER_VERSION')
        );

        if ($this->setting['mailsend'] == 1) {
            return $this->sendByMail($mail, $headers);
        } elseif ($this->setting['mailsend'] == 2) {
            return $this->sendBySocketSMTP($mail, $headers);
        } elseif ($this->setting['mailsend'] == 3) {
            return $this->sendByMailSMTP($mail, $headers, $email_from);
        }
    }
    
    private function prepareDelimiter($type)
    {
        return $type == 1 ? "\r\n" : ($type == 2 ? "\r" : "\n");
    }
    
    private function prepareSubject($subject, $appName, $charset)
    {
        return '=?' . $charset . '?B?' . base64_encode(str_replace("\r", '', 
                        str_replace("\n", '', '[' . $appName . '] ' . $subject))) 
                . '?=';
    }
    
    private function prepareMessage($message)
    {
        return chunk_split(base64_encode(str_replace("\r\n.", " \r\n..", str_replace("\n", "\r\n", str_replace("\r", "\n", str_replace("\r\n", "\n", str_replace("\n\r", "\r", $message)))))));
    }
    
    private function prepareFrom($from, $charset, $appName)
    {
        if ($from == '') {
            return "=?{$charset}?B?" . base64_encode($appName) 
                    . "?= <{$this->setting['maildefault']}>";
        }
        
        if (preg_match('/^(.+?) \<(.+?)\>$/', $from, $froms)) {
            return "=?{$charset}?B?" . base64_encode($froms[1]) 
                    . "?= <$froms[2]>";
        }
        
        return $from;
    }
    
    private function prepreEmailTo($emailTo, $charset)
    {
        $tousers = [];
        foreach (explode(',', $emailTo) as $touser) {
            $tousers[] = preg_match('/^(.+?) \<(.+?)\>$/', $touser, $to) 
                    ? ($this->setting['mailusername'] 
                        ? '=?' . $charset . '?B?' . base64_encode($to[1]) . "?= <$to[2]>" 
                        : $to[2]) 
                    : $touser;
        }

        return implode(',', $tousers);
    }
    
    private function prepareHeaders($from, $delimiter, $charset, $htmlOn, $version)
    {
        $headers = [
            'From' => $from,
            'X-Priority' => 3,
            'X-Mailer' => 'Discuz!' . $version,
            'MIME-Version' => '1.0',
            'Content-type' => 'text/' . ($htmlOn ? 'html' : 'plain'),
            'charset' => $charset,
            'Content-Transfer-Encoding' => 'base64'
        ];
        
        return implode($delimiter, array_map(
            function ($v, $k) { return "$k: $v"; },
            $headers,
            array_keys($headers)
        ));
    }

    /**
     * 通过 PHP 函数的 sendmail 发送
     * @param array $mail
     * @param string $headers
     * @return bool
     */
    public function sendByMail(array $mail, $headers)
    {
        if (!function_exists('mail')) {
            return false;
        }
        
        return mail($mail['email_to'], $mail['subject'], $mail['message'], $headers);
    }

    /**
     * 
     * @param array $mail
     * @param string $headers
     * @param string $delimeter
     * @return bool
     */
    public function sendBySocketSMTP(array $mail, $headers, $delimeter)
    {
        $adapter = new mail\SMTP();
        $adapter->setServer($this->setting['mailserver'], $this->setting['mailport']);
        $adapter->setAuth($this->setting['mailauth'], $this->setting['mailauth_username'], $this->setting['mailauth_password']);
        $adapter->setFrom($this->setting['mailfrom']);
        
        return $adapter->send($mail['email_to'], $mail['subject'], $mail['message'], $headers, $delimeter);
    }

    /**
     * 
     * @param array $mail
     * @param string $headers
     * @param string $from
     * @return bool
     */
    public function sendByMailSMTP(array $mail, $headers, $from)
    {
        if (!function_exists('mail')) {
            return false;
        }
        
        ini_set('SMTP', $this->setting['mailserver']);
        ini_set('smtp_port', $this->setting['mailport']);
        ini_set('sendmail_from', $from);

        return mail($mail['email_to'], $mail['subject'], $mail['message'], $headers);
    }

}
