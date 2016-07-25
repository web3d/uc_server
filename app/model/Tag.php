<?php

namespace uc\server\app\model;

use uc\server\Table;
use uc\server\Misc;

class Tag extends Table
{

    protected $name = 'tags';

    /**
     * 根据名称查找所以匹配的标签
     * @param string $tagname
     * @return array
     */
    public function get_tag_by_name(string $tagname)
    {
        return $this->findAll(['tagname' => $tagname]);
    }

    /**
     * 找出一条应用数据中的标签模板设置内容
     * @param int $appid
     * @return string
     */
    public function get_template(int $appid)
    {
        $result = $this
                ->select('tagtemplates')
                ->from('{{%applications}}')
                ->where(['appid' => $appid])
                ->scalar();
        return $result;
    }

    /**
     * 更新数据
     * @param int $appid
     * @param string $data
     */
    public function updatedata(int $appid, string $data)
    {
        $data = xml_unserialize($data);
        $data[0] = addslashes($data[0]);
        $datanew = array();
        if (is_array($data[1])) {
            foreach ($data[1] as $r) {
                $datanew[] = Misc::array2string($r);
            }
        }

        $tmp = $this->base->load('app')->get_apps('type', ['appid' => $appid]);
        $datanew = addslashes($tmp[0]['type'] . "\t" . implode("\t", $datanew));
        if (! empty($data[0])) {
            $this->updateOrInsert(
                ['appid' => $appid, 'tagname' => $data[0]], 
                ['data' => $datanew, 'expiration' => $this->base->time]
            );
        }
    }

    /**
     * 格式化缓存?
     * @param int $appid
     * @param string $tagname
     */
    public function formatcache(int $appid, string $tagname)
    {
        $this->updateOrInsert(
                ['appid' => $appid, 'tagname' => $tagname], 
                ['expiration' => 0]
        );
    }
}
