<?php

namespace uc\server;

class Template
{

    var $tpldir;

    var $objdir;

    var $tplfile;

    var $objfile;

    var $langfile;

    var $vars;

    var $force = 0;

    var $var_regexp = "\@?\\\$[a-zA-Z_]\w*(?:\[[\w\.\"\'\[\]\$]+\])*";

    var $vtag_regexp = "\<\?=(\@?\\\$[a-zA-Z_]\w*(?:\[[\w\.\"\'\[\]\$]+\])*)\?\>";

    var $const_regexp = "\{([\w]+)\}";

    var $languages = array();

    var $sid;

    public function __construct()
    {
        ob_start();
        $this->defaulttpldir = UC_APPDIR . '/view/default';
        $this->tpldir = UC_APPDIR . '/view/default';
        $this->objdir = UC_DATADIR . './view';
        $this->langfile = UC_APPDIR . '/view/default/templates.lang.php';
        if (version_compare(PHP_VERSION, '5') == - 1) {
            register_shutdown_function(array(
                &$this,
                '__destruct'
            ));
        }
    }

    public function assign($k, $v)
    {
        $this->vars[$k] = $v;
    }

    public function display($file)
    {
        extract($this->vars, EXTR_SKIP);
        include $this->gettpl($file);
    }

    protected function gettpl($file)
    {
        isset($_REQUEST['inajax']) && ($file == 'header' || $file == 'footer') && $file = $file . '_ajax';
        isset($_REQUEST['inajax']) && ($file == 'admin_header' || $file == 'admin_footer') && $file = substr($file, 6) . '_ajax';
        $this->tplfile = $this->tpldir . '/' . $file . '.htm';
        $this->objfile = $this->objdir . '/' . $file . '.php';
        $tplfilemtime = @filemtime($this->tplfile);
        if ($tplfilemtime === FALSE) {
            $this->tplfile = $this->defaulttpldir . '/' . $file . '.htm';
        }
        if ($this->force || ! file_exists($this->objfile) || @filemtime($this->objfile) < filemtime($this->tplfile)) {
            if (empty($this->language)) {
                @include $this->langfile;
                if (is_array($languages)) {
                    $this->languages += $languages;
                }
            }
            $this->complie();
        }
        return $this->objfile;
    }

    protected function complie()
    {
        $template = file_get_contents($this->tplfile);
        $template = preg_replace("/\<\!\-\-\{(.+?)\}\-\-\>/s", "{\\1}", $template);
        $template = preg_replace_callback("/\{lang\s+(\w+?)\}/is", [$this, 'lang'], $template);
        
        $template = preg_replace("/\{($this->var_regexp)\}/", "<?=\\1?>", $template);
        $template = preg_replace("/\{($this->const_regexp)\}/", "<?=\\1?>", $template);
        $template = preg_replace("/(?<!\<\?\=|\\\\)$this->var_regexp/", "<?=\\0?>", $template);
        
        $template = preg_replace_callback("/\<\?=(\@?\\\$[a-zA-Z_]\w*)((\[[\\$\[\]\w]+\])+)\?\>/is", [$this, 'arrayindex'], $template);
        
        $template = preg_replace_callback("/\{\{eval (.*?)\}\}/is", [$this, 'stripvtag_eval'], $template);
        $template = preg_replace_callback("/\{eval (.*?)\}/is", [$this, 'stripvtag_eval'], $template);
        $template = preg_replace_callback("/\{for (.*?)\}/is", [$this, 'stripvtag_for_start'], $template);
        
        $template = preg_replace_callback("/\{elseif\s+(.+?)\}/is", [$this, 'stripvtag_elseif'], $template);
        
        for ($i = 0; $i < 2; $i ++) {
            $template = preg_replace_callback("/\{loop\s+$this->vtag_regexp\s+$this->vtag_regexp\s+$this->vtag_regexp\}(.+?)\{\/loop\}/is", [$this, 'loopsection_1'], $template);
            $template = preg_replace_callback("/\{loop\s+$this->vtag_regexp\s+$this->vtag_regexp\}(.+?)\{\/loop\}/is", [$this, 'loopsection_2'], $template);
        }
        $template = preg_replace_callback("/\{if\s+(.+?)\}/is", [$this, 'stripvtag_if_start'], $template);
        
        $template = preg_replace("/\{template\s+(\w+?)\}/is", "<? include \$this->gettpl('\\1');?>", $template);
        $template = preg_replace_callback("/\{template\s+(.+?)\}/is", [$this, 'stripvtag_include'], $template);
        
        $template = preg_replace("/\{else\}/is", "<? } else { ?>", $template);
        $template = preg_replace("/\{\/if\}/is", "<? } ?>", $template);
        $template = preg_replace("/\{\/for\}/is", "<? } ?>", $template);
        
        $template = preg_replace("/$this->const_regexp/", "<?=\\1?>", $template);
        
        $template = "<? if(!defined('UC_ROOT')) exit('Access Denied');?>\r\n$template";
        $template = preg_replace("/(\\\$[a-zA-Z_]\w+\[)([a-zA-Z_]\w+)\]/i", "\\1'\\2']", $template);
        
        $template = preg_replace("/\<\?(\s{1})/is", "<?php\\1", $template);
        $template = preg_replace("/\<\?\=(.+?)\?\>/is", "<?php echo \\1;?>", $template);
        
        $fp = fopen($this->objfile, 'w');
        fwrite($fp, $template);
        fclose($fp);
    }

    protected function arrayindex(array $matches)
    {
        $name = $matches[1];
        $items = $matches[2];
        
        $items = preg_replace("/\[([a-zA-Z_]\w*)\]/is", "['\\1']", $items);
        return "<?=$name$items?>";
    }
    
    private function stripvtag_if_start(array $matches)
    {
        $s = '<? if (' . $matches[1] . ') { ?>';
        return $this->stripvtag($s);
    }
    
    private function stripvtag_elseif(array $matches)
    {
        $s = '<? } elseif (' . $matches[1] . ') { ?>';
        return $this->stripvtag($s);
    }
    
    private function stripvtag_for_start(array $matches)
    {
        $s = '<? for (' . $matches[1] . ') { ?>';
        return $this->stripvtag($s);
    }
    
    private function stripvtag_eval(array $matches)
    {
        $s = '<? ' . $matches[1] . ' ?>';
        return $this->stripvtag($s);
    }
    
    private function stripvtag_include(array $matches)
    {
        $s = '<? include \$this->gettpl(' . $matches[1] . '); ?>';
        return $this->stripvtag($s);
    }

    protected function stripvtag($s)
    {
        
        return preg_replace("/$this->vtag_regexp/is", "\\1", str_replace("\\\"", '"', $s));
    }

    protected function loopsection_1(array $matches)
    {
        list ($_, $arr, $k, $v, $statement) = $matches;
        
        $arr = $this->stripvtag($arr);
        $k = $this->stripvtag($k);
        $v = $this->stripvtag($v);
        $statement = str_replace("\\\"", '"', $statement);
        return $k ? "<? foreach((array)$arr as $k => $v) {?>$statement<? }?>" : "<? foreach((array)$arr as $v) {?>$statement<? } ?>";
    }
    
    protected function loopsection_2(array $matches)
    {
        list ($_, $arr, $v, $statement) = $matches;
        $k = '';
        
        $arr = $this->stripvtag($arr);
        $k = $this->stripvtag($k);
        $v = $this->stripvtag($v);
        $statement = str_replace("\\\"", '"', $statement);
        return $k ? "<? foreach((array)$arr as $k => $v) {?>$statement<? }?>" : "<? foreach((array)$arr as $v) {?>$statement<? } ?>";
    }

    protected function lang(array $matches)
    {
        $k = $matches[1];
        return ! empty($this->languages[$k]) ? $this->languages[$k] : "{ $k }";
    }

    private function _transsid($url, $tag = '', $wml = 0)
    {
        $sid = $this->sid;
        $tag = stripslashes($tag);
        if (! $tag || (! preg_match("/^(http:\/\/|mailto:|#|javascript)/i", $url) && ! strpos($url, 'sid='))) {
            if ($pos = strpos($url, '#')) {
                $urlret = substr($url, $pos);
                $url = substr($url, 0, $pos);
            } else {
                $urlret = '';
            }
            $url .= (strpos($url, '?') ? ($wml ? '&amp;' : '&') : '?') . 'sid=' . $sid . $urlret;
        }
        return $tag . $url;
    }

    public function __destruct()
    {
        if (getgpc('sid', 'C')) {}
        $sid = rawurlencode($this->sid);
        $searcharray = array(
            "/\<a(\s*[^\>]+\s*)href\=([\"|\']?)([^\"\'\s]+)/ies",
            "/(\<form.+?\>)/is"
        );
        $replacearray = array(
            "\$this->_transsid('\\3','<a\\1href=\\2')",
            "\\1\n<input type=\"hidden\" name=\"sid\" value=\"" . rawurldecode(rawurldecode(rawurldecode($sid))) . "\" />"
        );
        $content = preg_replace($searcharray, $replacearray, ob_get_contents());
        ob_end_clean();
        echo $content;
    }
}
