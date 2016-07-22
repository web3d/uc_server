<?php

namespace uc\server\db;

/**
 * Db引擎基类,提供一些通用方法
 */
abstract class Base implements DbInterface
{    
    /**
     *
     * @var int 查询次数
     */
    public $querynum = 0;

    /**
     *
     * @var \mysqli db底层连接对象
     */
    protected $link;

    /**
     *
     * @var array sql查询历史语句
     */
    public $histories;

    /**
     *
     * @var string db host名 含端口 
     */
    protected $dbhost;

    /**
     *
     * @var string db 用户名
     */
    protected $dbuser;

    /**
     *
     * @var string db 用户名密码
     */
    protected $dbpw;

    /**
     *
     * @var string db 连接使用的字符串
     */
    protected $dbcharset;

    /**
     *
     * @var bool 是否持久连接
     */
    protected $pconnect;

    /**
     *
     * @var string 表名前缀
     */
    protected $tablepre;

    /**
     *
     * @var int sql缓存时长 
     */
    protected $time;

    /**
     *
     * @var int 连接丢失的时间
     */
    protected $goneaway = 5;

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
        $this->dbhost = $dbhost;
        $this->dbuser = $dbuser;
        $this->dbpw = $dbpw;
        $this->dbname = $dbname;
        $this->dbcharset = $dbcharset;
        $this->pconnect = $pconnect;
        $this->tablepre = $tablepre;
        $this->time = $time;
    }
    
    /**
     * 从yii2中移植 替换表名前缀
     * Returns the actual name of a given table name.
     * This method will strip off curly brackets from the given table name
     * and replace the percentage character '%' with [[Connection::tablePrefix]].
     * @param string $sql the sql to be converted
     * @return string the real sql
     */
    public function replaceRawTableName($sql)
    {
        if (strpos($sql, '{{') !== false) {
            $sql = preg_replace('/\\{\\{(.*?)\\}\\}/', '\1', $sql);
            return str_replace('%', $this->tablepre, $sql);
        } else {
            return $sql;
        }
    }
}

