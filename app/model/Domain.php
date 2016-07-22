<?php

namespace uc\server\app\model;

use uc\server\app\base\Model;

class Domain extends Model
{

    protected $tableName = '{{%domains}}';

    function add_domain($domain, $ip)
    {
        if ($domain) {
            $this->db->execute("INSERT INTO {{%domains}} SET domain='$domain', ip='$ip'");
        }
        return $this->db->insert_id();
    }

    function get_total_num()
    {
        $data = $this->db->result_first("SELECT COUNT(*) FROM {{%domains}}");
        return $data;
    }

    function get_list($page, $ppp, $totalnum)
    {
        $start = $this->base->page_get_start($page, $ppp, $totalnum);
        $data = $this->db->fetch_all("SELECT * FROM {{%domains}} LIMIT $start, $ppp");
        return $data;
    }

    function delete_domain($arr)
    {
        $domainids = $this->base->implode($arr);
        $this->db->execute("DELETE FROM {{%domains}} WHERE id IN ($domainids)");
        return $this->db->affected_rows();
    }

    function update_domain($domain, $ip, $id)
    {
        $this->db->execute("UPDATE {{%domains}} SET domain='$domain', ip='$ip' WHERE id='$id'");
        return $this->db->affected_rows();
    }
}
