<?php

/**
 * 通用函数库
 */

if (! function_exists('file_put_contents')) {

    function file_put_contents($filename, $s)
    {
        $fp = fopen($filename, 'w');
        fwrite($fp, $s);
        fclose($fp);
    }
}

/**
 * 给字符串中特殊字符增加反斜杠
 * @param string $string
 * @param bool $force 为true代表强制添加 false代表不处理
 * @param bool $strip 是否已经添加过
 * @return string
 */
function daddslashes($string, $force = FALSE, $strip = FALSE)
{
    if ($force) {
        if (is_array($string)) {
            foreach ($string as $key => $val) {
                $string[$key] = daddslashes($val, $force, $strip);
            }
        } else {
            $string = addslashes($strip ? stripslashes($string) : $string);
        }
    }
    return $string;
}

/**
 * 从$_GET $_POST $_COOKIE $_REQUEST中取值
 * @param string $k
 * @param string $var G|P|C|R 任选其一
 * @return mixed 未定义值会返回null
 */
function getgpc($k, $var = 'R')
{
    switch ($var) {
        case 'G':
            $var = &$_GET;
            break;
        case 'P':
            $var = &$_POST;
            break;
        case 'C':
            $var = &$_COOKIE;
            break;
        case 'R':
            $var = &$_REQUEST;
            break;
    }
    return isset($var[$k]) ? $var[$k] : NULL;
}

/**
 * 打开socket资源的简单封装,从fsockopen|pfsockopen|stream_socket_client三种中备选
 * @param string $hostname 主机名
 * @param string $port 主机端口
 * @param int $errno 错误编号
 * @param string $errstr 错误消息
 * @param int $timeout 超时时间
 * @return string
 */
function fsocketopen($hostname, $port = 80, &$errno, &$errstr, $timeout = 15)
{
    $fp = '';
    if (function_exists('fsockopen')) {
        $fp = fsockopen($hostname, $port, $errno, $errstr, $timeout);
    } elseif (function_exists('pfsockopen')) {
        $fp = pfsockopen($hostname, $port, $errno, $errstr, $timeout);
    } elseif (function_exists('stream_socket_client')) {
        $fp = stream_socket_client($hostname . ':' . $port, $errno, $errstr, $timeout);
    }
    return $fp;
}

/**
 * 处理html特殊字符
 * @param string $string
 * @param int|null $flags 一组常量备选值
 * @return string
 */
function dhtmlspecialchars($string, $flags = null)
{
    if (is_array($string)) {
        foreach ($string as $key => $val) {
            $string[$key] = dhtmlspecialchars($val, $flags);
        }
    } else {
        if ($flags === null) {
            $string = str_replace(array(
                '&',
                '"',
                '<',
                '>'
            ), array(
                '&amp;',
                '&quot;',
                '&lt;',
                '&gt;'
            ), $string);
            if (strpos($string, '&amp;#') !== false) {
                $string = preg_replace('/&amp;((#(\d{3,5}|x[a-fA-F0-9]{4}));)/', '&\\1', $string);
            }
        } else {
            if (PHP_VERSION < '5.4.0') {
                $string = htmlspecialchars($string, $flags);
            } else {
                if (strtolower(CHARSET) == 'utf-8') {
                    $charset = 'UTF-8';
                } else {
                    $charset = 'ISO-8859-1';
                }
                $string = htmlspecialchars($string, $flags, $charset);
            }
        }
    }
    return $string;
}

/**
 * xml反序列化
 * @param string $xml
 * @param bool $isnormal
 * @return array
 */
function xml_unserialize(&$xml, $isnormal = FALSE)
{
    $xml_parser = new \uc\server\XML($isnormal);
    $data = $xml_parser->parse($xml);
    $xml_parser->destruct();
    return $data;
}

/**
 * 将数组序列化为xml文本
 * @param array $arr
 * @param bool $htmlon
 * @param bool $isnormal
 * @param int $level
 * @return string
 */
function xml_serialize($arr, $htmlon = FALSE, $isnormal = FALSE, $level = 1)
{
    $s = $level == 1 ? "<?xml version=\"1.0\" encoding=\"ISO-8859-1\"?>\r\n<root>\r\n" : '';
    $space = str_repeat("\t", $level);
    foreach ($arr as $k => $v) {
        if (! is_array($v)) {
            $s .= $space . "<item id=\"$k\">" . ($htmlon ? '<![CDATA[' : '') . $v . ($htmlon ? ']]>' : '') . "</item>\r\n";
        } else {
            $s .= $space . "<item id=\"$k\">\r\n" . xml_serialize($v, $htmlon, $isnormal, $level + 1) . $space . "</item>\r\n";
        }
    }
    $s = preg_replace("/([\x01-\x08\x0b-\x0c\x0e-\x1f])+/", ' ', $s);
    return $level == 1 ? $s . "</root>" : $s;
}

/**
 * 获取用户的ip
 * @return string
 */
function uc_clientip()
{
    $cip = getenv('HTTP_CLIENT_IP');
    $xip = getenv('HTTP_X_FORWARDED_FOR');
    $rip = getenv('REMOTE_ADDR');
    $srip = $_SERVER['REMOTE_ADDR'];
    if ($cip && strcasecmp($cip, 'unknown')) {
        $onlineip = $cip;
    } elseif ($xip && strcasecmp($xip, 'unknown')) {
        $onlineip = $xip;
    } elseif ($rip && strcasecmp($rip, 'unknown')) {
        $onlineip = $rip;
    } elseif ($srip && strcasecmp($srip, 'unknown')) {
        $onlineip = $srip;
    }
    preg_match("/[\d\.]{7,15}/", $onlineip, $match);
    $onlineip = $match[0] ? $match[0] : 'unknown';
    
    return $onlineip;
}

/**
 * 剪裁字符
 * @param string $string
 * @param int $length
 * @param string $dot
 * @return string
 */
function uc_custr($string, $length, $dot = ' ...')
{
    if (strlen($string) <= $length) {
        return $string;
    }

    $string = str_replace(array(
        '&amp;',
        '&quot;',
        '&lt;',
        '&gt;'
            ), array(
        '&',
        '"',
        '<',
        '>'
            ), $string);

    $strcut = '';
    if (strtolower(UC_CHARSET) == 'utf-8') {

        $n = $tn = $noc = 0;
        while ($n < strlen($string)) {

            $t = ord($string[$n]);
            if ($t == 9 || $t == 10 || (32 <= $t && $t <= 126)) {
                $tn = 1;
                $n ++;
                $noc ++;
            } elseif (194 <= $t && $t <= 223) {
                $tn = 2;
                $n += 2;
                $noc += 2;
            } elseif (224 <= $t && $t < 239) {
                $tn = 3;
                $n += 3;
                $noc += 2;
            } elseif (240 <= $t && $t <= 247) {
                $tn = 4;
                $n += 4;
                $noc += 2;
            } elseif (248 <= $t && $t <= 251) {
                $tn = 5;
                $n += 5;
                $noc += 2;
            } elseif ($t == 252 || $t == 253) {
                $tn = 6;
                $n += 6;
                $noc += 2;
            } else {
                $n ++;
            }

            if ($noc >= $length) {
                break;
            }
        }
        if ($noc > $length) {
            $n -= $tn;
        }

        $strcut = substr($string, 0, $n);
    } else {
        for ($i = 0; $i < $length; $i ++) {
            $strcut .= ord($string[$i]) > 127 ? $string[$i] . $string[++$i] : $string[$i];
        }
    }

    $strcut = str_replace(array(
        '&',
        '"',
        '<',
        '>'
            ), array(
        '&amp;',
        '&quot;',
        '&lt;',
        '&gt;'
            ), $strcut);

    return $strcut . $dot;
}

/**
 * 构造用于表单的hash校验值
 * @param string $base
 * @param string $salt
 * @return string
 */
function uc_formhash($base, $salt)
{
    return substr(md5(substr($base, 0, - 4) . $salt), 16);
}

/**
 * uc用于数据编码和解码
 * @param string $string
 * @param string $operation DECODE|ENCODE
 * @param string $key
 * @param int $expiry
 * @return string
 */
function uc_authcode($string, $operation = 'DECODE', $key = '', $expiry = 0)
{
    $ckey_length = 4;

    $key = md5($key);
    $keya = md5(substr($key, 0, 16));
    $keyb = md5(substr($key, 16, 16));
    $keyc = $ckey_length ? ($operation == 'DECODE' ? substr($string, 0, $ckey_length) : substr(md5(microtime()), - $ckey_length)) : '';

    $cryptkey = $keya . md5($keya . $keyc);
    $key_length = strlen($cryptkey);

    $string = $operation == 'DECODE' ? base64_decode(substr($string, $ckey_length)) : sprintf('%010d', $expiry ? $expiry + time() : 0) . substr(md5($string . $keyb), 0, 16) . $string;
    $string_length = strlen($string);

    $result = '';
    $box = range(0, 255);

    $rndkey = array();
    for ($i = 0; $i <= 255; $i ++) {
        $rndkey[$i] = ord($cryptkey[$i % $key_length]);
    }

    for ($j = $i = 0; $i < 256; $i ++) {
        $j = ($j + $box[$i] + $rndkey[$i]) % 256;
        $tmp = $box[$i];
        $box[$i] = $box[$j];
        $box[$j] = $tmp;
    }

    for ($a = $j = $i = 0; $i < $string_length; $i ++) {
        $a = ($a + 1) % 256;
        $j = ($j + $box[$a]) % 256;
        $tmp = $box[$a];
        $box[$a] = $box[$j];
        $box[$j] = $tmp;
        $result .= chr(ord($string[$i]) ^ ($box[($box[$a] + $box[$j]) % 256]));
    }

    if ($operation == 'DECODE') {
        if ((substr($result, 0, 10) == 0 || substr($result, 0, 10) - time() > 0) && substr($result, 10, 16) == substr(md5(substr($result, 26) . $keyb), 0, 16)) {
            return substr($result, 26);
        } else {
            return '';
        }
    } else {
        return $keyc . str_replace('=', '', base64_encode($result));
    }
}

/**
 * 生成与时区相关的指定格式的日期时间格式
 * @param int $time
 * @param int $type 1 - 指定时间格式; 2- 指定日期格式; 3 - 指定了日期和时间的格式
 * @param array $formatDate 日期格式如 'Y-n-j'
 * @param array $formatTime 时间格式如 'H:i'
 * @param int $offset 换算成秒
 * @return string
 */
function uc_gmdate($time, $type = 3, $formatDate = 'Y-n-j', $formatTime = 'H:i', $offset = 0)
{
    $format[0] = ($type & 2) ? (! empty($formatDate) ? $formatDate : 'Y-n-j') : '';
    $format[1] = ($type & 1) ? (! empty($formatTime) ? $formatTime : 'H:i') : '';
    return gmdate(implode(' ', $format), $time + $offset);
}

/**
 * 日志写入
 * @param string $msg
 * @param string $filename
 */
function uc_writelog($msg, $filename)
{
    $logfile = UC_ROOT . './data/logs/' . $filename . '.php';
    if (is_file($logfile) && filesize($logfile) > 2048000) {
        PHP_VERSION < '4.2.0' && mt_srand((double) microtime() * 1000000);
        $hash = '';
        $chars = 'ABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789abcdefghijklmnopqrstuvwxyz';
        for ($i = 0; $i < 4; $i ++) {
            $hash .= $chars[mt_rand(0, 61)];
        }
        rename($logfile, UC_ROOT . './data/logs/' . $filename . '_' . $hash . '.php');
    }
    if ($fp = fopen($logfile, 'a')) {
        flock($fp, 2);
        fwrite($fp, "<?PHP exit;?>\t" . str_replace(array(
            '<?',
            '?>',
            '<?php'
        ), '', $msg) . "\n");
        fclose($fp);
    }
}