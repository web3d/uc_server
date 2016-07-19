<?php

namespace uc\server;

use uc\server\db\DbInterface;

class Db
{
    /**
     *
     * @var \uc\server\db\DbInterface 
     */
    private static $instance;
    
    /**
     * 
     * @return \uc\server\db\DbInterface
     */
    public static function instance()
    {
        if (!self::$instance instanceof DbInterface) {
            if (function_exists('mysql_connect')) {
                self::$instance = new \uc\server\db\MySQL;
            }

            self::$instance = new \uc\server\db\MySQLi;
        }
        
        return self::$instance;
    }
}

