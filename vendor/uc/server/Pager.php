<?php

namespace uc\server;

class Pager
{
    
    protected $maxListedPages = 10;
    protected $offset = 2;
    
    /**
     * 计算有效的起始记录索引
     * @param int $pageIndex 当前页面
     * @param int $pageSize 每页显示数量
     * @param int $totalNum 记录总数
     * @return int
     */
    public function getStart($pageIndex, $pageSize, $totalNum)
    {
        return (max(1, min(ceil($totalNum / $pageSize), intval($pageIndex))) - 1) * $pageSize;
    }

    /**
     * 构造分页html文本
     * @param int $num 记录总数
     * @param int $pageSize 每页显示数量
     * @param int $pageIndex 当前页码
     * @param string $mpurl url前缀
     * @param bool $simple
     * @param string $ajaxTarget
     * @return string
     */
    public function output($num, $pageSize, $pageIndex, $mpurl, $simple = false, $ajaxTarget = '', $autogoto = false, $realPages = '')
    {
        $multipage = '';
        
        if ($num <= $pageSize) {
            return $multipage;
        }

        $pages = ceil($num / $pageSize);
        
        list($from, $to) = $this->calFromTo($pages, $this->maxListedPages, $pageIndex, $this->offset);

        $mpurl .= strpos($mpurl, '?') ? '&' : '?';
        
        $multipage .= ($pageIndex - $this->offset > 1 && $pages > $this->maxListedPages) 
                ? '<a href="' . $mpurl . 'page=1" class="first"' 
                    . $ajaxTarget . '>1 ...</a>' 
                : '';
        $multipage .= ($pageIndex > 1 && !$simple) 
                ? '<a href="' . $mpurl . 'page=' . ($pageIndex - 1) 
                    . '" class="prev"' . $ajaxTarget . '>&lsaquo;&lsaquo;</a>' 
                : '';
        for ($i = $from; $i <= $to; $i ++) {
            $multipage .= ($i == $pageIndex) 
                    ? '<strong>' . $i . '</strong>' 
                    : '<a href="' . $mpurl . 'page=' . $i 
                        . ($ajaxTarget && $i == $pages && $autogoto ? '#' : '') 
                        . '"' . $ajaxTarget . '>' . $i . '</a>';
        }

        $multipage .= ($pageIndex < $pages && !$simple) 
                ? '<a href="' . $mpurl . 'page=' . ($pageIndex + 1) 
                    . '" class="next"' . $ajaxTarget . '>&rsaquo;&rsaquo;</a>' 
                : '';
        $multipage .= ($to < $pages) 
                ? '<a href="' . $mpurl . 'page=' . $pages . '" class="last"' 
                    . $ajaxTarget . '>... ' . $realPages . '</a>' 
                : '';
        $multipage .= (!$simple && $pages > $this->maxListedPages && !$ajaxTarget) 
                ? '<kbd><input type="text" name="custompage" size="3"' 
                    . ' onkeydown="if(event.keyCode==13) {window.location=\'' 
                    . $mpurl . 'page=\'+this.value; return false;}" /></kbd>' 
                : '';

        return $multipage 
                ? '<div class="pages">' 
                    . (!$simple ? '<em>&nbsp;' . $num . '&nbsp;</em>' : '') 
                    . $multipage . '</div>' 
                : '';
    }
    
    /**
     * 计算界面上列出的起止页数
     * @param int $pages 总页数
     * @param int $maxListedPages 最大列出页数
     * @param int $pageIndex 当前页码
     * @param int $offset
     * @return array [1, 10]
     */
    private function calFromTo($pages, $maxListedPages, $pageIndex, $offset)
    {
        if ($maxListedPages > $pages) {
            return [1, $pages];
        }
        
        $from = $pageIndex - $offset;
        $to = $from + $maxListedPages - 1;
        if ($from < 1) {
            $to = $pageIndex + 1 - $from;
            $from = 1;
            if ($to - $from < $maxListedPages) {
                $to = $maxListedPages;
            }
        } elseif ($to > $pages) {
            $from = $pages - $maxListedPages + 1;
            $to = $pages;
        }
        
        
        return [$from, $to];
    }

}
