<?php

namespace TradusBundle\Service\Search;

/**
 * Class Query
 *
 * @package TradusBundle\Service\Search
 */
class BaseQuery {
    /**
     * @param string $type
     * @return $this
     */
    public function setType(string $type) {
        $this->type = $type;
        return $this;
    }

    /**
     * @return mixed
     */
    public function getType() {
        return $this->type;
    }


    /**
     * Get options value
     *
     * @param $key
     * @return bool|mixed
     */
    public function getOption($key) {
        if(!array_key_exists($key, $this->options))
            return false;
        return $this->options[$key];
    }

    /**
     * Set options value
     *
     * @param $key
     * @param $value
     * @return bool
     */
    public function setOption($key, $value) {
        if (!array_key_exists($key, $this->options))
            return false;

        if (!empty($value) || $value === 0)
            $this->options[$key] = $value;

        return true;
    }

}