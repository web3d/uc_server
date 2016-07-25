<?php

namespace uc\server;

/**
 * 将原Misc Model中网络操作相关方法移植成独立的类
 */
class HTTPClient
{
    /**
     * 从url中提取host部分
     * @param string $url
     * @return int|string -1 没有host部分 -2 无效IP格式 字符串代表正常返回
     */
    public static function get_host_by_url($url)
    {
        $m = parse_url($url);
        if (! $m['host']) {
            return - 1;
        }
        if (! preg_match("/^\d{1,3}\.\d{1,3}\.\d{1,3}\.\d{1,3}$/", $m['host'])) {
            $ip = gethostbyname($m['host']);
            if (! $ip || $ip == $m['host']) {
                return - 2;
            }
            return $ip;
        }
        
        return $m['host'];
    }

    /**
     * 判断是否有效url
     * @param string $url
     * @return bool
     */
    public static function check_url($url)
    {
        return preg_match("/(https?){1}:\/\/|www\.([^\[\"']+?)?/i", $url);
    }

    /**
     * 判断给定url是否IP地址
     * @param string $url
     * @return bool
     */
    public static function check_ip($url)
    {
        return preg_match("/^\d{1,3}\.\d{1,3}\.\d{1,3}\.\d{1,3}$/", $url);
    }

    public static function dfopen2($url, $limit = 0, $post = '', $cookie = '', $bysocket = FALSE, $ip = '', $timeout = 15, $block = TRUE, $encodetype = 'URLENCODE')
    {
        $__times__ = isset($_GET['__times__']) ? intval($_GET['__times__']) + 1 : 1;
        if ($__times__ > 2) {
            return '';
        }
        $url .= (strpos($url, '?') === FALSE ? '?' : '&') . "__times__=$__times__";
        return static::dfopen($url, $limit, $post, $cookie, $bysocket, $ip, $timeout, $block, $encodetype);
    }

    /**
     * 基于fsocketopen|fopen方式进行网络请求
     * @param string $url
     * @param int $limit
     * @param string $post
     * @param string $cookie
     * @param bool $bysocket
     * @param string $ip uc的域名解析机制,可以定义域名对象的ip
     * @param int $timeout
     * @param bool $block
     * @param string $encodetype
     * @return string
     */
    public static function dfopen($url, $limit = 0, $post = '', $cookie = '', $bysocket = FALSE, $ip = '', $timeout = 15, $block = TRUE, $encodetype = 'URLENCODE')
    {
        $return = '';
        $matches = parse_url($url);
        $scheme = $matches['scheme'];
        $host = $matches['host'];
        $path = $matches['path'] ? $matches['path'] . ($matches['query'] ? '?' . $matches['query'] : '') : '/';
        $port = ! empty($matches['port']) ? $matches['port'] : 80;
        
        $type = $post ? 'POST' : 'GET';
        
        $header = self::prepare_headers($host, $port, $post, $encodetype, $cookie);
        
        $fpflag = 0;
        if (! $fp = fsocketopen(($ip ? $ip : $host), $port, $errno, $errstr, $timeout)) {
            $context = stream_context_create(array(
                'http' => array(
                    'method' => $type,
                    'header' => $header,
                    'content' => $post,
                    'timeout' => $timeout
                )
            ));
            $fp = fopen($scheme . '://' . ($ip ? $ip : $host) . ':' . $port . $path, 'b', false, $context);
            $fpflag = 1;
        }
        
        if (! $fp) {
            return '';
        }
        
        stream_set_blocking($fp, $block);
        stream_set_timeout($fp, $timeout);
        fwrite($fp, "{$type} {$path} HTTP/1.0\r\n" . $header . ($post ? $post : ''));
        $status = stream_get_meta_data($fp);
        if (! $status['timed_out']) {
            while (! feof($fp) && ! $fpflag) {
                if (($header = fgets($fp)) && ($header == "\r\n" || $header == "\n")) {
                    break;
                }
            }

            $stop = false;
            while (! feof($fp) && ! $stop) {
                $data = fread($fp, ($limit == 0 || $limit > 8192 ? 8192 : $limit));
                $return .= $data;
                if ($limit) {
                    $limit -= strlen($data);
                    $stop = $limit <= 0;
                }
            }
        }
        fclose($fp);
        return $return;
        
    }
    
    private static function prepare_headers($host, $port, $post, $encodetype, $cookie = '')
    {
        $header = "Accept: */*\r\n";
        $header .= "Accept-Language: zh-cn\r\n";
        $header .= "User-Agent: {$_SERVER['HTTP_USER_AGENT']}\r\n";
        $header .= "Host: $host:$port\r\n";
        
        if ($post) {
            $boundary = 
                    $encodetype == 'URLENCODE' 
                    ? '' 
                    : ';' . substr($post, 0, trim(strpos($post, "\n")));
            $header .= 
                    $encodetype == 'URLENCODE' 
                    ? "Content-Type: application/x-www-form-urlencoded\r\n" 
                    : "Content-Type: multipart/form-data$boundary\r\n";
            
            $header .= 'Content-Length: ' . strlen($post) . "\r\n";
            $header .= "Cache-Control: no-cache\r\n";
        }
        
        $header .= "Connection: Close\r\n";
        $header .= "Cookie: $cookie\r\n\r\n";
        
        return $header;
    }
}