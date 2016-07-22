<?php

namespace uc\server\db;

class MySQLi extends Base
{

    /**
     * db连接
     * @param string $dbhost
     * @param string $dbuser
     * @param string $dbpw
     * @param string $dbname
     * @param string $dbcharset
     * @param bool $pconnect
     * @param string $tablepre
     * @param int $time
     */
    public function connect($dbhost, $dbuser, $dbpw, $dbname = '', $dbcharset = '', $pconnect = 0, $tablepre = '', $time = 0)
    {
        parent::connect($dbhost, $dbuser, $dbpw, $dbname, $dbcharset, $pconnect, $tablepre, $time);
        
        if (! $this->link = new \mysqli($dbhost, $dbuser, $dbpw, $dbname)) {
            $this->halt('Can not connect to MySQL server');
        }
        
        if ($this->version() > '4.1') {
            if ($dbcharset) {
                $this->link->set_charset($dbcharset);
            }
            
            if ($this->version() > '5.0.1') {
                $this->link->query("SET sql_mode=''");
            }
        }
    }

    /**
     * 
     * @param \mysqli_result $query
     * @param string $result_type
     * @return mixed
     */
    public function fetch_array($query, $result_type = MYSQLI_ASSOC)
    {
        return $query ? $query->fetch_array($result_type) : null;
    }

    /**
     * 返回第一条的第一个字段的值
     * @param string $sql
     * @return mixed
     */
    public function result_first($sql)
    {
        $query = $this->query($sql);
        return $this->result($query, 0);
    }

    /**
     * 返回第一行
     * @param string $sql
     * @return array
     */
    public function fetch_first($sql)
    {
        $query = $this->query($sql);
        return $this->fetch_array($query);
    }

    /**
     * 返回所有行
     * @param string $sql
     * @param string $id
     * @return array
     */
    public function fetch_all($sql, $id = '')
    {
        $arr = array();
        $query = $this->query($sql);
        while ($data = $this->fetch_array($query)) {
            $id ? $arr[$data[$id]] = $data : $arr[] = $data;
        }
        return $arr;
    }

    protected function cache_gc()
    {
        $this->query("DELETE FROM {{%sqlcaches}} WHERE expiry < {$this->time}");
    }

    /**
     * 根据sql构造查询对象
     * @param string $sql
     * @param string $type
     * @param int $cachetime
     * @return mixed
     */
    public function query($sql, $type = '', $cachetime = FALSE)
    {
        $sql = $this->replaceRawTableName($sql);
        $resultmode = $type == 'UNBUFFERED' ? MYSQLI_USE_RESULT : MYSQLI_STORE_RESULT;
        
        if (! ($query = $this->link->query($sql, $resultmode)) && $type != 'SILENT') {
            $this->halt('MySQL Query Error', $sql);
        }
        $this->querynum ++;
        $this->histories[] = $sql;
        return $query;
    }

    /**
     * 
     * @return int 影响行数
     */
    public function affected_rows()
    {
        return $this->link->affected_rows;
    }

    /**
     * 
     * @return string 底层错误消息
     */
    public function error()
    {
        return (($this->link) ? $this->link->error : mysqli_error());
    }

    /**
     * 
     * @return int 底层错误编号
     */
    public function errno()
    {
        return intval(($this->link) ? $this->link->errno : mysqli_errno());
    }

    /**
     * 
     * @param \mysqli_result $query
     * @param int $row offset
     * @return mixed?
     */
    public function result($query, $row)
    {
        if (! $query || $query->num_rows == 0) {
            return null;
        }
        $query->data_seek($row);
        $assocs = $query->fetch_row();
        return $assocs[0];
    }

    /**
     * 获取符合条件的记录行数
     * @param \mysqli_result $query
     * @return int
     */
    public function num_rows($query)
    {
        $query = $query ? $query->num_rows : 0;
        return $query;
    }

    /**
     * 字段数
     * @param \mysqli_result $query
     * @return int
     */
    public function num_fields($query)
    {
        return $query ? $query->field_count : 0;
    }

    /**
     * 
     * @param \mysqli_result $query
     * @return bool
     */
    public function free_result($query)
    {
        return $query ? $query->free() : false;
    }

    /**
     * 
     * @return int 记录插入后返回的主键ID
     */
    public function insert_id()
    {
        return ($id = $this->link->insert_id) >= 0 ? $id : $this->result($this->query("SELECT last_insert_id()"), 0);
    }

    /**
     * 
     * @param \mysqli_result $query
     * @return array
     */
    public function fetch_row($query)
    {
        $query = $query ? $query->fetch_row() : null;
        return $query;
    }

    /**
     * 获取字段定义信息
     * @param \mysqli_result $query
     * @return object|bool|null
     */
    public function fetch_fields($query)
    {
        return $query ? $query->fetch_field() : null;
    }

    /**
     * 
     * @return string db的版本名称
     */
    public function version()
    {
        return $this->link->server_info;
    }

    /**
     * 
     * @param string $str 安全过滤
     * @return string
     */
    public function escape_string($str)
    {
        return $this->link->escape_string($str);
    }

    /**
     * 
     * @return bool 关闭链接结果
     */
    public function close()
    {
        return $this->link->close();
    }

    /**
     * 终止
     * @param string $message
     * @param string $sql
     */
    protected function halt($message = '', $sql = '')
    {
        $error = $this->error();
        $errorno = $this->errno();
        if ($errorno == 2006 && $this->goneaway -- > 0) {
            $this->connect($this->dbhost, $this->dbuser, $this->dbpw, $this->dbname, $this->dbcharset, $this->pconnect, $this->tablepre, $this->time);
            $this->query($sql);
        } else {
            $s = '';
            if ($message) {
                $s = "<b>UCenter info:</b> $message<br />";
            }
            if ($sql) {
                $s .= '<b>SQL:</b>' . htmlspecialchars($sql) . '<br />';
            }
            $s .= '<b>Error:</b>' . $error . '<br />';
            $s .= '<b>Errno:</b>' . $errorno . '<br />';
            $s = str_replace($this->tablepre, '[Table]', $s);
            exit($s);
        }
    }
}
