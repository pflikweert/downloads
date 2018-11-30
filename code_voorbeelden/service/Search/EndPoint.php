<?php

namespace TradusBundle\Service\Search;

/**
 * Class EndPoint
 *
 * @package TradusBundle\Service\Search
 */
class EndPoint {

    /**
     * Default options.
     *
     * The defaults match a standard Solr example instance as distributed by
     * the Apache Lucene Solr project.
     *
     * @var array
     */
    private $options = [
        'scheme' => 'http',
        'host' => '127.0.0.1',
        'port' => 8983,
        'path' => '/solr2',
        'core' => null,
        'timeout' => 20,
    ];

    /**
     * EndPoint constructor.
     * @param null $options
     * @throws \Exception
     */
    public function __construct($options = null) {
        if (is_array($options) && isset($options['endpoint'])) {
            $this->setOptionsByEndPoint($options['endpoint']);
        } elseif (is_string($options)) {
            $this->setOptionsByEndPoint($options);
        }

    }

    /**
     * @param string $endPointUrl
     *
     * @throws \Exception
     */
    public function setOptionsByEndPoint(string $endPointUrl) {
        $urlParts = parse_url($endPointUrl);
        $this->setScheme($urlParts['scheme']);
        $this->setHost($urlParts['host']);
        $this->setPort($urlParts['port']);

        $path = $urlParts['path'];
        // In the path is also the core, so lets get it out
        if ('/' == substr($path, -1)) {
            $path = substr($path, 0, -1);
        }
        $pos = strrpos($path, '/');
        if ($pos === false) {
            throw new \Exception('Wrong endpoint config url, missing core');
        }

        $core = substr($path, $pos+1, strlen($path)-$pos);
        $path = substr($path, 0 , $pos);
        $this->setCore($core);
        $this->setPath($path);
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
        if(!array_key_exists($key, $this->options))
            return false;

        $this->options[$key] = $value;
        return true;
    }

    /**
     * Set Host
     * @param string $host This can be a hostname or an IP address
     * @return bool
     */
    public function setHost($host) {
        return $this->setOption('host', $host);
    }

    /**
     * Get host
     * @return bool|mixed
     */
    public function getHost() {
        return $this->getOption('host');
    }

    /**
     * Set port option.
     *
     * @param int $port Common values are 80, 8080 and 8983
     * @return bool
     */
    public function setPort(int $port) {
        return $this->setOption('port', $port);
    }

    /**
     * Get port option.
     *
     * @return int
     */
    public function getPort() {
        return $this->getOption('port');
    }

    /**
     * Set path option.
     *
     * If the path has a trailing slash it will be removed.
     *
     * @param string $path
     * @return bool
     */
    public function setPath($path) {
        if ('/' == substr($path, -1)) {
            $path = substr($path, 0, -1);
        }
        return $this->setOption('path', $path);
    }

    /**
     * Get path option.
     *
     * @return string
     */
    public function getPath() {
        return $this->getOption('path');
    }

    /**
     * Set core option.
     *
     * @param string $core
     * @return bool
     */
    public function setCore($core) {
        return $this->setOption('core', $core);
    }

    /**
     * Get core option.
     *
     * @return string
     */
    public function getCore() {
        return $this->getOption('core');
    }

    /**
     * Set timeout option.
     *
     * @param int $timeout
     * @return bool
     */
    public function setTimeout($timeout) {
        return $this->setOption('timeout', $timeout);
    }

    /**
     * Get timeout option.
     *
     * @return string
     */
    public function getTimeout() {
        return $this->getOption('timeout');
    }

    /**
     * Set scheme option.
     *
     * @param string $scheme
     * @return bool
     */
    public function setScheme($scheme) {
        return $this->setOption('scheme', $scheme);
    }

    /**
     * Get scheme option.
     *
     * @return string
     */
    public function getScheme() {
        return $this->getOption('scheme');
    }

    /**
     * Get the base url for all requests.
     * Based on host, path, port and core options.
     *
     * @return string
     */
    public function getBaseUri() {
        $uri = $this->getScheme().'://'.$this->getHost().':'.$this->getPort().$this->getPath().'/';
        $core = $this->getCore();
        if (!empty($core)) {
            $uri .= $core.'/';
        }
        return $uri;
    }

}
