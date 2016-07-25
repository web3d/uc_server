<?php

namespace uc\server\app\model;

use uc\server\Table;
use uc\server\Misc;

class Feed extends Table
{

    protected $name = 'feeds';

    public function get_total_num()
    {
        return $this->count();
    }

    public function get_list($page, $ppp, $totalnum)
    {
        $start = $this->base->page_get_start($page, $ppp, $totalnum);
        $data = $this->findAll([], '*', '', $start, $ppp);
        
        foreach ((array) $data as $k => $v) {
            $searchs = $replaces = array();
            $title_data = Misc::string2array($v['title_data']);
            foreach (array_keys($title_data) as $key) {
                $searchs[] = '{' . $key . '}';
                $replaces[] = $title_data[$key];
            }
            $searchs[] = '{actor}';
            $replaces[] = $v['username'];
            $searchs[] = '{app}';
            $replaces[] = $this->base->apps[$v['appid']]['name'];
            $data[$k]['title_template'] = str_replace($searchs, $replaces, $data[$k]['title_template']);
            $data[$k]['dateline'] = $v['dateline'] ? $this->base->date($data[$k]['dateline']) : '';
        }
        return $data;
    }
}
