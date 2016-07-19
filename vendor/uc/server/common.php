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

function daddslashes($string, $force = 0, $strip = FALSE)
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

function fsocketopen($hostname, $port = 80, &$errno, &$errstr, $timeout = 15)
{
    $fp = '';
    if (function_exists('fsockopen')) {
        $fp = @fsockopen($hostname, $port, $errno, $errstr, $timeout);
    } elseif (function_exists('pfsockopen')) {
        $fp = @pfsockopen($hostname, $port, $errno, $errstr, $timeout);
    } elseif (function_exists('stream_socket_client')) {
        $fp = @stream_socket_client($hostname . ':' . $port, $errno, $errstr, $timeout);
    }
    return $fp;
}

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