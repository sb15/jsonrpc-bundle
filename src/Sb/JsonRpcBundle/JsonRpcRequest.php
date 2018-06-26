<?php

namespace Sb\JsonRpcBundle;

use Sb\JsonRpcBundle\Exception\JsonRpcException;
use Symfony\Component\HttpFoundation\Request;

class JsonRpcRequest
{
    private $httpRequest;
    private $jsonRequestRaw;
    private $id;
    private $method;
    private $params;

    public function __construct(Request $request)
    {
        $this->httpRequest = $request;
    }

    /**
     * @throws JsonRpcException
     * @throws \LogicException
     */
    public function parseRequest()
    {
        if (!$this->httpRequest->isMethod('POST')) {
            throw new JsonRpcException(JsonRpcException::INVALID_REQUEST, 'Invalid method, method should be POST');
        }
        if ($this->httpRequest->getContentType() !== 'json') {
            throw new JsonRpcException(JsonRpcException::INVALID_REQUEST, 'Content-Type should by application/json');
        }
        $this->jsonRequestRaw = $this->httpRequest->getContent();
        $this->parseJsonRequest();
    }


    /**
     * @throws JsonRpcException
     */
    protected function parseJsonRequest()
    {
        $body = json_decode($this->jsonRequestRaw, true);
        if (empty($body)) {
            throw new JsonRpcException(JsonRpcException::INVALID_REQUEST);
        }
        if (empty($body['id'])) {
            throw new JsonRpcException(JsonRpcException::INVALID_REQUEST);
        }
        if (empty($body['method'])) {
            throw new JsonRpcException(JsonRpcException::INVALID_REQUEST);
        }
        if (!isset($body['params'])) {
            throw new JsonRpcException(JsonRpcException::INVALID_REQUEST);
        }
        $this->id = $body['id'];
        $this->method = $body['method'];
        $this->params = $body['params'];
    }

    /**
     * @return Request
     */
    public function getHttpRequest()
    {
        return $this->httpRequest;
    }

    public function getId()
    {
        return $this->id;
    }

    public function getMethod()
    {
        return $this->method;
    }

    public function getParams()
    {
        return $this->params;
    }

    public function getParam($param, $def = null)
    {
        return isset($this->params[$param]) ? $this->params[$param] : $def;
    }

    /**
     * @return mixed
     */
    public function getJsonRequestRaw()
    {
        return $this->jsonRequestRaw;
    }
}