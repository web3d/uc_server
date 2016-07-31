<?php

namespace uc\server\app\model;

use uc\server\Table;

class BadWord extends Table
{
    protected $name = 'badwords';
    
    public function add_badword($find, $replacement, $admin, $type = 1)
    {
        if ($find) {
            $find = trim($find);
            $replacement = trim($replacement);
            $findpattern = $this->pattern_find($find);
            if ($type == 1) {
                $this->db->execute("REPLACE INTO {{%badwords}} SET find='$find', replacement='$replacement', admin='$admin', findpattern='$findpattern'");
            } elseif ($type == 2) {
                $this->db->execute("INSERT INTO {{%badwords}} SET find='$find', replacement='$replacement', admin='$admin', findpattern='$findpattern'", 'SILENT');
            }
        }
        return $this->db->insert_id();
    }

    public function get_total_num()
    {
        $data = $this->db->result_first("SELECT COUNT(*) FROM {{%badwords}}");
        return $data;
    }

    public function get_list($page, $ppp, $totalnum)
    {
        $start = $this->base->page_get_start($page, $ppp, $totalnum);
        $data = $this->db->fetch_all("SELECT * FROM {{%badwords}} LIMIT $start, $ppp");
        return $data;
    }

    public function delete_badword($arr)
    {
        $badwordids = $this->base->implode($arr);
        $this->db->execute("DELETE FROM {{%badwords}} WHERE id IN ($badwordids)");
        return $this->db->affected_rows();
    }

    public function truncate_badword()
    {
        $this->db->execute("TRUNCATE {{%badwords}}");
    }

    public function update_badword($find, $replacement, $id)
    {
        $findpattern = $this->pattern_find($find);
        $this->db->execute("UPDATE {{%badwords}} SET find='$find', replacement='$replacement', findpattern='$findpattern' WHERE id='$id'");
        return $this->db->affected_rows();
    }

    private function pattern_find($find)
    {
        $find = preg_quote($find, "/'");
        $find = str_replace("\\", "\\\\", $find);
        $find = str_replace("'", "\\'", $find);
        return '/' . preg_replace("/\\\{(\d+)\\\}/", ".{0,\\1}", $find) . '/is';
    }
}
