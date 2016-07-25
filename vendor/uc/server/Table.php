<?php

namespace uc\server;

use ucs\db\Query;

/**
 * 数据库单表操作层,特性:
 * 
 * 1. 封装常用表操作方法
 */
class Table extends Query
{
    /**
     *
     * @var \ucs\db\Connection 新的db连接对象
     */
    protected $conn;
    
    /**
     *
     * @var \ucs\db\Command db对应的command对象 
     */
    protected $command;

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
     * @var Control 兼容老的model过渡处理
     */
    protected $base;
    
    /**
     *
     * @var Db 兼容老的db连接对象
     */
    protected $db;

    /**
     * 构造函数 默认提前执行了from当前定义的table的方法,便于简化查询语句
     * @param Control $base 为兼容替换老的db对象,暂时这样定义
     */
    public function __construct(ControlInterface &$base)
    {
        parent::__construct();
        
        $this->initCompat($base);
        
        $this->initConnection();
        
        $this->from($this->getName());
        
        $this->getCommand();
    }
    
    private function initCompat(ControlInterface &$base)
    {
        $this->base = $base;
        $this->db = $this->base->db;
    }
    
    private function initConnection()
    {
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
     * @param string $fields 指定要返回的字段
     * @return array
     */
    public function find(array $condition, string $fields = '*')
    {
        return $this->select($fields)
                ->where($condition)
                ->one();
    }
    
    /**
     * 按条件查找所有记录
     * @param array $condition 
     * [
     * 'status' => 10, 
     * 'type' => 2,
     * 'id' => [4, 8, 15, 16, 23, 42], 
     * ]
     * @param string $fields
     * @param string $key
     * @param int $offset
     * @param int $limit
     * @return array
     */
    public function findAll(array $condition, string $fields = '*', string $key = '', int $offset = -1, int $limit = -1)
    {
        $rows = $this->select($fields)
                ->where($condition)
                ->offset($offset)
                ->limit($limit)
                ->indexBy($key)
                ->all();
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
    public function insert(array $columns)
    {
        $this->createCommand()->insert($this->getName(), $columns)->execute();
        return $this->conn->getLastInsertID();
    }
    
    /**
     * 按条件更新数据
     * @param array $columns ['status' => 1]
     * @param array $condition ['uid' => 101, 'store_id' => 29]
     * @param array $params 绑定条件中的参数值
     * @return int
     */
    public function update(array $columns, array $condition)
    {
        return $this->createCommand()
                ->update($this->getName(), $columns, $condition)
                ->execute();
    }
    
    /**
     * 按条件删除
     * @param array $condition
     * @param array $params
     * @return int
     */
    public function delete(array $condition, array $params = [])
    {
        return $this->createCommand()
                ->delete($this->getName(), $condition, $params)
                ->execute();
    }
    
    /**
     * 重载,强制传递当前Connection对象
     * @param \ucs\db\Connection $db
     * @return \ucs\db\Command
     */
    public function createCommand($db = null)
    {
        list ($sql, $params) = $this->conn->getQueryBuilder()->build($this);
        
        $this->getCommand()->setSql($sql);
        $this->getCommand()->bindValues($params);

        return $this->getCommand();
    }
    
    /**
     * 获取command对象
     * @return \ucs\db\Command
     */
    public function getCommand()
    {
        if (!$this->command instanceof \ucs\db\Command) {
            $this->command = $this->conn->createCommand();
        }
        
        return $this->command;
    }
}

