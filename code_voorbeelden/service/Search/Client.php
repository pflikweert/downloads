<?php

namespace TradusBundle\Service\Search;

/**
 * Class Client
 * @package TradusBundle\Service\Search
 */
class Client {

    /**
     * @var \TradusBundle\Service\Search\Endpoint $endPoint
     */
    protected $endpoint;

    /**
     * @var AdapterCurl
     */
    protected $adapter;

    /**
     * Client constructor.
     *
     * @param null $options
     */
    public function __construct($options = null) {
        if($options) {
            $this->createEndPoint($options);
        }
        $this->setAdapter(new AdapterCurl());
    }

    /**
     * Create Endpoint for the search
     * @param null $options
     */
    public function createEndPoint($options = null) {
        $this->endpoint = new EndPoint($options);
    }

    /**
     * @return Endpoint
     */
    public function getEndPoint() {
        return $this->endpoint;
    }

    /**
     * @param QueryInterface $query
     * @return Request
     */
    public function createRequest(QueryInterface $query) {
        $request = new Request();
        return $query->createRequest($request);
    }

    /**
     * @param QueryInterface $query
     * @param Response $response
     * @return Result
     */
    public function createResult(QueryInterface $query, Response $response) {
        $result = new Result($query, $response);
        return $result;
    }

    /**
     * @return Query
     */
    public function getQuerySelect() {
        return  new Query();
    }

    /**
     * @return QuerySuggest
     */
    public function getQuerySuggest() {
       return new QuerySuggest();
    }

    /**
     * @param QueryInterface $query
     * @return Result
     */
    public function execute(QueryInterface $query){
        $request = $this->createRequest($query);
        $response = $this->adapter->execute($request, $this->endpoint);
        $result = $this->createResult($query, $response);
        return $result;
    }

    /**
     * @param $adapter
     */
    public function setAdapter($adapter) {
        $this->adapter = $adapter;
    }


}