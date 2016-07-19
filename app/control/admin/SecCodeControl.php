<?php

namespace uc\server\app\control\admin;

use uc\server\app\base\Control;

class SecCodeControl extends Control
{

    public function __construct()
    {
        parent::__construct();
        $authkey = md5(UC_KEY . $_SERVER['HTTP_USER_AGENT'] . $this->onlineip);
        
        $this->time = time();
        $seccodeauth = getgpc('seccodeauth');
        $seccode = $this->authcode($seccodeauth, 'DECODE', $authkey);
        
        @header("Expires: -1");
        @header("Cache-Control: no-store, private, post-check=0, pre-check=0, max-age=0", FALSE);
        @header("Pragma: no-cache");
        
        include_once UC_ROOT . 'lib/seccode.class.php';
        $code = new \uc\server\SecCode();
        $code->code = $seccode;
        $code->type = 0;
        $code->width = 70;
        $code->height = 21;
        $code->background = 0;
        $code->adulterate = 1;
        $code->ttf = 1;
        $code->angle = 0;
        $code->color = 1;
        $code->size = 0;
        $code->shadow = 1;
        $code->animator = 0;
        $code->fontpath = UC_ROOT . 'images/fonts/';
        $code->datapath = UC_ROOT . 'images/';
        $code->includepath = '';
        $code->display();
    }
}
