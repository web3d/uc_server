<?php
/**
 * @link http://www.yiiframework.com/
 * @copyright Copyright (c) 2008 Yii Software LLC
 * @license http://www.yiiframework.com/license/
 */

namespace ucs\caching;

/**
 * ArrayCache provides caching for the current request only by storing the values in an array.
 *
 * See [[Cache]] for common cache operations that ArrayCache supports.
 *
 * Unlike the [[Cache]], ArrayCache allows the expire parameter of [[set]], [[add]], [[multiSet]] and [[multiAdd]] to
 * be a floating point number, so you may specify the time in milliseconds (e.g. 0.1 will be 100 milliseconds).
 *
 * @author Carsten Brandt <mail@cebe.cc>
 * @since 2.0
 */
class ArrayCache extends Cache
{
    private $cache;


    /**
     * @inheritdoc
     */
    public function exists($key)
    {
        $key = $this->buildKey($key);
        return isset($this->cache[$key]) && ($this->cache[$key][1] === 0 || $this->cache[$key][1] > microtime(true));
    }

    /**
     * @inheritdoc
     */
    protected function getValue($key)
    {
        if (isset($this->cache[$key]) && ($this->cache[$key][1] === 0 || $this->cache[$key][1] > microtime(true))) {
            return $this->cache[$key][0];
        } else {
            return false;
        }
    }

    /**
     * @inheritdoc
     */
    protected function setValue($key, $value, $duration)
    {
        $this->cache[$key] = [$value, $duration === 0 ? 0 : microtime(true) + $duration];
        return true;
    }

    /**
     * @inheritdoc
     */
    protected function addValue($key, $value, $duration)
    {
        if (isset($this->cache[$key]) && ($this->cache[$key][1] === 0 || $this->cache[$key][1] > microtime(true))) {
            return false;
        } else {
            $this->cache[$key] = [$value, $duration === 0 ? 0 : microtime(true) + $duration];
            return true;
        }
    }

    /**
     * @inheritdoc
     */
    protected function deleteValue($key)
    {
        unset($this->cache[$key]);
        return true;
    }

    /**
     * @inheritdoc
     */
    protected function flushValues()
    {
        $this->cache = [];
        return true;
    }
}
