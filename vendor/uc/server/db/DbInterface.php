<?php

namespace uc\server\db;

interface DbInterface
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
    public function connect($dbhost, $dbuser, $dbpw, $dbname = '', $dbcharset = '', $pconnect = 0, $tablepre = '', $time = 0);
    
    /**
     * 根据sql构造查询对象
     * @param string $sql
     * @param string $type
     * @param int $cachetime
     * @return mixed
     */
    public function query($sql, $type = '', $cachetime = FALSE);
    
    /**
     * 返回第一条的第一个字段的值
     * @param string $sql
     * @return mixed
     */
    public function result_first($sql);
    
    /**
     * 返回第一行
     * @param string $sql
     * @return array
     */
    public function fetch_first($sql);
    
    /**
     * 返回所有行
     * @param string $sql
     * @param string $id
     * @return array
     */
    public function fetch_all($sql, $id = '');
    
    /**
     * 
     * @return int 影响行数
     */
    public function affected_rows();
    
    /**
     * 
     * @return string 底层错误消息
     */
    public function error();
    
    /**
     * 
     * @return int 底层错误编号
     */
    public function errno();
    
    /**
     * 
     * @return int 记录插入后返回的主键ID
     */
    public function insert_id();
    
    /**
     * 
     * @return string db的版本名称
     */
    public function version();
    
    /**
     * 
     * @param string $str 安全过滤
     * @return string
     */
    public function escape_string($str);
    
    /**
     * 
     * @return bool 关闭链接结果
     */
    public function close();
}

