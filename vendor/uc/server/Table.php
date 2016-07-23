<?php

namespace uc\server;

use ucs\db\Query;

/**
 * 数据单表模型,
 * 1. 封装常用表操作方法
 * 2. 数据校验
 * 3. 缓存
 */
class Table extends Query
{
    /**
     *
     * @var \ucs\db\Connection 
     */
    protected $conn;
    
    /**
     *
     * @var \ucs\db\Command 命令执行工具 
     */
    protected $cmd;
    
    /**
     *
     * @var string 表名
     */
    protected $name;
    
    /**
     *
     * @var string 主键名
     */
    protected $pk;
    
    /**
     *
     * @var Control 
     */
    protected $base;
    
    /**
     *
     * @var Db 
     */
    protected $db;

    /**
     * 构造函数
     * @param string $config db连接配置
     * @param string $conn db连接实例名称 默认 'db'
     */
    public function __construct(&$base)
    {
        parent::__construct();
        
        $this->base = $base;
        $this->db = $this->base->db;
        
        $conn = 'db';

        //利用Yii的依赖注入容器创建db连接实例
        if (!\Uii::$container->hasSingleton($conn)) {
            \Uii::$container->setSingleton($conn, [
                'class' => 'ucs\db\Connection',
                'dsn' => 'mysql:host=' . UC_DBHOST . ';dbname=' . UC_DBNAME,
                'username' => UC_DBUSER,
                'password' => UC_DBPW,
                'charset' => UC_DBCHARSET,
                'tablePrefix' => UC_DBTABLEPRE
            ]);
        }

        $this->conn = \Uii::$container->get($conn);
        
        $this->cmd = $this->conn->createCommand();
        
    }
    
    /**
     * 
     * @return string 表名 带界定符和前缀占位符
     */
    public function getName()
    {
        return "{{%{$this->name}}}";
    }
    
    /**
     * 按条件查找一条记录
     * @param array $condition 
     * [
     * 'status' => 10, 
     * 'type' => 2,
     * 'id' => [4, 8, 15, 16, 23, 42], 
     * ]
     * @return array
     */
    public function find($condition, $field = '*')
    {
        return $this->select($field)
                ->from($this->getName())
                ->where($condition)
                ->createCommand($this->conn)
                ->queryOne();
    }
    
    /**
     * 按条件查找所有记录
     * @param array $condition 
     * [
     * 'status' => 10, 
     * 'type' => 2,
     * 'id' => [4, 8, 15, 16, 23, 42], 
     * ]
     * @param string $field
     * @param string $key
     * @param int $offset
     * @param int $limit
     * @return array
     */
    public function findAll($condition, $field = '*', $key = '', $offset = -1, $limit = -1)
    {
        $rows = $this->select($field)
                ->from($this->getName())
                ->where($condition)
                ->offset($offset)
                ->limit($limit)
                ->indexBy($key)
                ->createCommand($this->conn)
                ->queryAll();
        return $rows;
    }
    
    /**
     * 添加一条记录
     * @param array $columns 字段值 [
     *     'name' => 'Sam',
     *     'age' => 30,
     * ]
     * @return int ID
     * @see \ucs\db\Command::insert
     */
    public function insert($columns)
    {
        $this->cmd->insert($this->getName(), $columns)->execute();
        return $this->conn->getLastInsertID();
    }
    
    /**
     * 按条件更新数据
     * @param array $columns ['status' => 1]
     * @param array $condition ['uid' => 101, 'store_id' => 29]
     * @param array $params 绑定条件中的参数值
     * @return int
     */
    public function update(array $columns, $condition)
    {
        return $this->cmd
                ->update($this->getName(), $columns, $condition)
                ->execute();
    }
    
    /**
     * 按条件删除
     * @param array|string $condition
     * @param array $params
     * @return int
     */
    public function delete($condition, array $params = [])
    {
        return $this->cmd
                ->delete($this->getName(), $condition, $params)
                ->execute();
    }
}

