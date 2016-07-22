<?php

namespace uc\server\app\base;

class Model
{
    
    /**
     *
     * @var \uc\server\Db db引擎对象
     */
    protected $db;

    /**
     *
     * @var Control 系统原来的关系,引用控制器
     */
    protected $base;
    
    /**
     *
     * @var string 表名 {{%table_name}}
     */
    protected $tableName;

    /**
     * 构造函数
     * @param \uc\server\app\base\Control $base 引用控制器
     */
    public function __construct(Control &$base)
    {
        $this->base = $base;
        $this->db = $base->db;
    }
}
