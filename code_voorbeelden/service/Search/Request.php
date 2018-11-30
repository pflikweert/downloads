<?php

namespace TradusBundle\Service\Search;

/**
 * Class Request
 *
 * @package TradusBundle\Service\Search
 */
class Request {

    /**
     * Request GET method.
     */
    const METHOD_GET = 'GET';

    /**
     * Request POST method.
     */
    const METHOD_POST = 'POST';

    /**
     * Request HEAD method.
     */
    const METHOD_HEAD = 'HEAD';

    /**
     * Default options.
     * @var array
     */
    protected $options = [
        'method' => self::METHOD_GET,
        'type'   => Query::TYPE_SELECT,
    ];

    /**
     * Request params.
     *
     * Multivalue params are supported using a multidimensional array:
     * 'fq' => array('cat:1','published:1')
     *
     * @var array
     */
    protected $params = [];

    /**
     * Get options value
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
        if(!array_key_exists($key, $this->options))
            return false;

        $this->options[$key] = $value;
        return true;
    }

    /**
     * Set request method.
     *
     * @param string $method
     * @return bool
     */
    public function setMethod($method) {
        return $this->setOption('method', $method);
    }

    /**
     * Get request method.
     *
     * @return string
     */
    public function getMethod() {
        return $this->getOption('method');
    }

    /**
     * Set query type.
     *
     * @param string $type
     * @return bool
     */
    public function setType($type) {
        return $this->setOption('type', $type);
    }

    /**
     * Get query type.
     *
     * @return string
     */
    public function getType() {
        return $this->getOption('type');
    }

    /**
     * Get a param value.
     *
     * @param string $key
     * @return string|array
     */
    public function getParam($key) {
        if (isset($this->params[$key])) {
            return $this->params[$key];
        }
    }

    /**
     * Get all params.
     *
     * @return array
     */
    public function getParams() {
        return $this->params;
    }

    /**
     * Set request params.
     *
     * @param array $params
     */
    public function setParams($params) {
        $this->clearParams();
        $this->addParams($params);
    }

    /**
     * Add a request param.
     *
     * If you add a request param that already exists the param will be converted into a multivalue param,
     * unless you set the overwrite param to true.
     *
     * Empty params are not added to the request. If you want to empty a param disable it you should use
     * remove param instead.
     *
     * @param string       $key
     * @param string|array $value
     * @param bool         $overwrite
     */
    public function addParam($key, $value, $overwrite = false){
        if (null !== $value) {
            if (!$overwrite && isset($this->params[$key])) {
                if (!is_array($this->params[$key])) {
                    $this->params[$key] = [$this->params[$key]];
                }
                $this->params[$key][] = $value;
            } else {
                // not all solr handlers support 0/1 as boolean values...
                if (true === $value) {
                    $value = 'true';
                } elseif (false === $value) {
                    $value = 'false';
                }
                $this->params[$key] = $value;
            }
        }
    }

    /**
     * Add multiple params to the request.
     *
     * @param array $params
     * @param bool  $overwrite
     */
    public function addParams($params, $overwrite = false) {
        foreach ($params as $key => $value) {
            $this->addParam($key, $value, $overwrite);
        }
    }

    /**
     * Remove a param by key.
     *
     * @param string $key
     */
    public function removeParam($key) {
        if (isset($this->params[$key])) {
            unset($this->params[$key]);
        }
    }
    /**
     * Clear all request params.
     */
    public function clearParams() {
        $this->params = [];
    }

    /**
     * Get an URI for this request.
     *
     * @return string
     */
    public function getUri() {
        return $this->getType().'?'.$this->getQueryString();
    }

    /**
     * Get the query string for this request.
     *
     * @return string
     */
    public function getQueryString() {
        $queryString = '';
        if (count($this->params) > 0) {
            $queryString = http_build_query($this->params, null, '&');
            $queryString = preg_replace(
                '/%5B(?:[0-9]|[1-9][0-9]+)%5D=/',
                '=',
                $queryString
            );
        }
        return $queryString;
    }




}