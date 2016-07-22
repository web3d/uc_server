<?php

namespace uc\server;

class Db
{
    
    /**
     *
     * @var $this 
     */
    private static $instance;
    
    /**
     *
     * @var \ucs\db\Connection db底层连接对象
     */
    private $conn;
    
    /**
     *
     * @var \ucs\db\Command 命令执行工具 
     */
    private $cmd;
    
    /**
     *
     * @var int 查询次数
     */
    public $querynum = 0;

    /**
     *
     * @var array sql查询历史语句
     */
    public $histories;

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

    public function __construct()
    {
        
        $this->conn = \Uii::createObject([
            'class' => 'ucs\db\Connection',
            'dsn' => 'mysql:host=' . UC_DBHOST . ';dbname=' . UC_DBNAME,
            'username' => UC_DBUSER,
            'password' => UC_DBPW,
            'charset' => UC_DBCHARSET,
            'tablePrefix' => UC_DBTABLEPRE
        ]);
        
        $this->cmd = $this->conn->createCommand();
    }
    
    /**
     * 获取db实例
     * @return $this
     */
    public static function instance()
    {
        if (!self::$instance instanceof Db) {
            
            self::$instance = new self;
        }
        
        return self::$instance;
    }
    
    /**
     * 根据sql构造数据查询对象
     * @param string $sql
     * @param string $type SILENT
     * @return mixed
     */
    public function query($sql, $type = '')
    {
        try {
            $query = $this->cmd->setSql($sql)->query();
            
            $this->querynum ++;
            $this->histories[] = $sql;
            
            return $query;
        } catch (\ucs\db\Exception $e) {
            if ($type != 'SILENT') {
                $this->halt($e->getTraceAsString(), $sql);
            }
        }

        return false;
    }
    
    /**
     * 执行操作类的sql
     * @param string $sql
     * @param string $type UNBUFFERED
     * @return int|bool 影响记录条数
     */
    public function execute($sql, $type = '')
    {
        $resultmode = $type == 'UNBUFFERED' ? MYSQLI_USE_RESULT : MYSQLI_STORE_RESULT;
        
        $result = $this->cmd->setSql($sql)->execute();
        
        $this->querynum ++;
        $this->histories[] = $sql;
        return $result;
    }
    
    /**
     * 
     * @param \ucs\db\DataReader
     * @param string $result_type
     * @return mixed
     */
    public function fetch_array(\ucs\db\DataReader $query, $result_type = MYSQLI_ASSOC)
    {
        return $query->read();
    }

    /**
     * 返回第一条的第一个字段的值
     * @param string $sql
     * @return mixed
     */
    public function result_first($sql)
    {
        return $this->cmd->setSql($sql)->queryScalar();
    }

    /**
     * 返回第一行
     * @param string $sql
     * @return array
     */
    public function fetch_first($sql)
    {
        return $this->cmd->setSql($sql)->queryOne();
    }

    /**
     * 返回所有行
     * @param string $sql
     * @param string $id
     * @return array
     */
    public function fetch_all($sql, $id = '')
    {
        $arr = [];
        
        $reader = $this->cmd->setSql($sql)->query();
        while ($data = $reader->read()) {
            $id ? $arr[$data[$id]] = $data : $arr[] = $data;
        }
        return $arr;
    }
    
    /**
     * 
     * @param \ucs\db\DataReader $query
     * @return array
     */
    public function fetch_row(\ucs\db\DataReader $query)
    {
        return $query->read();
    }

    /**
     * 
     * @return string db的版本名称
     */
    public function version()
    {
        return ;
    }

    /**
     * 
     * @param string $str 安全过滤
     * @return string
     */
    public function escape_string($str)
    {
        return $str;
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
            $this->conn->open();
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
    
    /**
     * 
     * @return int 影响行数
     */
    public function affected_rows()
    {
        return $this->cmd->pdoStatement->rowCount();
    }
    
    /**
     * 
     * @return int 记录插入后返回的主键ID
     */
    public function insert_id()
    {
        return $this->conn->getLastInsertID();
    }

    /**
     * 
     * @return string 底层错误消息
     */
    public function error()
    {
        $info = $this->conn->pdo->errorInfo();
        return $info[2];
    }

    /**
     * 
     * @return int 底层错误编号
     */
    public function errno()
    {
        return $this->conn->pdo->errorCode();
    }
}

