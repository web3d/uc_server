<?php

namespace uc\server;

class Misc
{
    /**
     * 每组数据之内键值分隔符
     */
    const UC_ARRAY_SEP_1 = 'UC_ARRAY_SEP_1';
    /**
     * 每组数据之间的分隔符
     */
    const UC_ARRAY_SEP_2 = 'UC_ARRAY_SEP_2';   

    public static function array2string($arr)
    {
        $s = $sep = '';
        if ($arr && is_array($arr)) {
            foreach ($arr as $k => $v) {
                $s .= $sep . addslashes($k) . static::UC_ARRAY_SEP_1 . $v;
                $sep = static::UC_ARRAY_SEP_2;
            }
        }
        return $s;
    }

    public static function string2array($s)
    {
        $arr = explode(static::UC_ARRAY_SEP_2, $s);
        $arr2 = array();
        foreach ($arr as $k => $v) {
            list ($key, $val) = explode(static::UC_ARRAY_SEP_1, $v);
            $arr2[$key] = $val;
        }
        return $arr2;
    }
}
