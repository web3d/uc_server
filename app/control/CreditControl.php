<?php

namespace uc\server\app\control;

use uc\server\app\base\Control;
use uc\server\HTTPClient;

class CreditControl extends Control
{

    function __construct()
    {
        parent::__construct();
        $this->init_input();
        $this->load('note');
    }

    function onrequest()
    {
        $uid = intval($this->input('uid'));
        $from = intval($this->input('from'));
        $to = intval($this->input('to'));
        $toappid = intval($this->input('toappid'));
        $amount = intval($this->input('amount'));
        $status = 0;
        $this->settings['creditexchange'] = @unserialize($this->settings['creditexchange']);
        if (isset($this->settings['creditexchange'][$this->app['appid'] . '_' . $from . '_' . $toappid . '_' . $to])) {
            $toapp = $app = $this->cache['apps'][$toappid];
            $apifilename = isset($toapp['apifilename']) && $toapp['apifilename'] ? $toapp['apifilename'] : 'uc.php';
            if ($toapp['extra']['apppath'] && @include $toapp['extra']['apppath'] . './api/' . $apifilename) {
                $uc_note = new uc_note();
                $status = $uc_note->updatecredit(array(
                    'uid' => $uid,
                    'credit' => $to,
                    'amount' => $amount
                ), '');
            } else {
                $url = $_ENV['note']->get_url_code('updatecredit', "uid=$uid&credit=$to&amount=$amount", $toappid);
                $status = trim(HTTPClient::dfopen($url, 0, '', '', 1, $toapp['ip'], UC_NOTE_TIMEOUT));
            }
        }
        echo $status ? 1 : 0;
        exit();
    }
}
