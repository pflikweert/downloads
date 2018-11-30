<?php

namespace TradusBundle\Service\Search;


use InvalidArgumentException;
use Symfony\Component\HttpKernel\Exception\HttpException;

/**
 * Class AdapterCurl
 *
 * @package TradusBundle\Service\Search
 */
class AdapterCurl {

    /**
     * @var resource
     */
    protected $handle;

    /**
     * AdapterCurl constructor.
     */
    public function __construct() {

    }

    /**
     * Execute a Solr request using the cURL Http.
     *
     * @param Request $request
     * @param Endpoint $endpoint
     * @return Response
     *
     * @throws InvalidArgumentException
     */
    public function execute(Request $request, $endpoint) {
        return $this->getData($request, $endpoint);
    }

    /**
     * @param $request
     * @param $endpoint
     * @return Response
     * @throws InvalidArgumentException
     */
    protected function getData($request, $endpoint) {
       $this->handle = $this->createHandle($request, $endpoint);
       $httpResponse = curl_exec($this->handle);
       return $this->getResponse($this->handle, $httpResponse);
    }

    /**
     * Create curl handle for a request.
     *
     *
     * @param Request  $request
     * @param Endpoint $endpoint
     * @throws InvalidArgumentException
     *
     * @return resource
     */
    public function createHandle($request, $endpoint) {
        $url    = $endpoint->getBaseUri().$request->getUri();
        $method = $request->getMethod();

        $handler = curl_init();
        curl_setopt($handler, CURLOPT_URL, $url);
        curl_setopt($handler, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($handler, CURLOPT_ENCODING, "");
        curl_setopt($handler, CURLOPT_MAXREDIRS, 5);
        curl_setopt($handler, CURLOPT_TIMEOUT, $endpoint->getTimeout());
        curl_setopt($handler, CURLOPT_CONNECTTIMEOUT, $endpoint->getTimeout());
        curl_setopt($handler, CURLOPT_HTTP_VERSION, CURL_HTTP_VERSION_1_1);
        curl_setopt($handler, CURLOPT_POSTFIELDS, "");
        curl_setopt($handler, CURLOPT_HTTPHEADER, ["content-type: application/json"]);

        if($method == Request::METHOD_POST) {
            curl_setopt($handler, CURLOPT_POST, true);
        } elseif ($method == Request::METHOD_GET) {
            curl_setopt($handler, CURLOPT_HTTPGET, true);
        } elseif ($method == Request::METHOD_HEAD) {
             curl_setopt($handler, CURLOPT_CUSTOMREQUEST, 'HEAD');
        } else {
            throw new InvalidArgumentException("unsupported method: $method");
        }

        return $handler;
    }

    /**
     * Get the response for a curl handle.
     *
     * @param resource $handle
     * @param string   $httpResponse
     * @return Response
     */
    public function getResponse($handle, $httpResponse) {
        if (false !== $httpResponse && null !== $httpResponse) {
            $data = $httpResponse;
            $info = curl_getinfo($handle);
            $headers = [];
            $headers[] = 'HTTP/1.1 '.$info['http_code'].' OK';
        } else {
            $headers = [];
            $data = '';
        }
        $this->check($data, $headers, $handle);
        curl_close($handle);
        return new Response($data, $headers);
    }

    /**
     * Check result of a request.
     *
     * @param string   $data
     * @param array    $headers
     * @param resource $handle
     * @throws HttpException
     */
    public function check($data, $headers, $handle) {
        // if there is no data and there are no headers it's a total failure,
        // a connection to the host was impossible.
        if (empty($data) && 0 == count($headers)) {
            throw new HttpException(500,'HTTP request failed, '.curl_error($handle));
        }
    }

}
