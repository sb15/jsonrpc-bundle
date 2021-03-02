<?php

namespace Sb\JsonRpcBundle\Controller;

use Sb\JsonRpcBundle\Exception\JsonRpcException;
use Sb\JsonRpcBundle\JsonRpcRequest;
use Sb\JsonRpcBundle\JsonRpcResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Stopwatch\Stopwatch;
use function Symfony\Component\String\u;

class JsonRpcController
{
    /**
     * Array of names of fully exposed services (all methods of this services are allowed to be called)
     *
     * @var array $services
     */
    private $services = [];

    /**
     * @param $methodName
     * @return array
     * @throws JsonRpcException
     */
    public function getServiceAndMethodName($methodName)
    {
        $delimiter = '.';

        if (!\count($this->services)) {
            throw new JsonRpcException(JsonRpcException::METHOD_NOT_FOUND);
        }

        if (strpos($methodName, $delimiter) === false) {
            throw new JsonRpcException(JsonRpcException::METHOD_NOT_FOUND);
        }

        list($serviceName, $method) = explode($delimiter, $methodName);

        if (!array_key_exists($serviceName, $this->services)) {
            throw new JsonRpcException(JsonRpcException::METHOD_NOT_FOUND);
        }

        return [$serviceName, $method];
    }

    /**
     * @param $serviceName
     * @param $methodName
     * @param $request
     * @return mixed
     * @throws JsonRpcException
     * @throws \ReflectionException
     */
    public function processMethod($serviceName, $methodName, JsonRpcRequest $request)
    {
        $service = $this->services[$serviceName];

        if (!\is_callable(array($service, $methodName))) {
            throw new JsonRpcException(JsonRpcException::METHOD_NOT_FOUND);
        }

        $reader = new \ReflectionMethod($service, $methodName);
        $isAssociative = array_keys($request->getParams()) ? array_keys($request->getParams())[0] !== 0 : false;
        $requestParams = $request->getParams();
        $requestParamId = 0;
        $callingParams = [];
        foreach ($reader->getParameters() as $i => $param) {
            if ($param->getClass()) {
                if ($param->getClass()->getName() === JsonRpcRequest::class || $param->getClass()->isSubclassOf(JsonRpcRequest::class)) {
                    $callingParams[$i] = $request;
                } else {
                    throw new JsonRpcException(JsonRpcException::INTERNAL_ERROR, 'Method definition is incorrect');
                }
            } else {
                if ($isAssociative) {
                    if (isset($requestParams[$param->getName()])) {
                        $callingParams[$i] = $requestParams[$param->getName()];
                    } elseif (!$param->isOptional()) {
                        throw new JsonRpcException(JsonRpcException::INTERNAL_ERROR, 'Undefined parameter: ' . $param->getName());
                    }
                } elseif (isset($requestParams[$requestParamId])) {
                    $callingParams[$i] = $requestParams[$requestParamId];
                    $requestParamId++;
                } elseif (!$param->isOptional()) {
                    throw new JsonRpcException(JsonRpcException::INTERNAL_ERROR, 'Not enough parameters');
                }
            }
        }
        if ($requestParamId > 0 && isset($requestParams[$requestParamId])) {
            throw new JsonRpcException(JsonRpcException::INTERNAL_ERROR, 'Too many parameters');
        }

        return \call_user_func_array([ $service, $methodName ], $callingParams);
    }

    public function execute(Request $request)
    {
        $jsonRpcRequest = new JsonRpcRequest($request);

        $response = new JsonRpcResponse();
        $stopWatch = new Stopwatch();
        $stopWatch->start('api');
        $response->setStopwatch($stopWatch);

        try {
            $response->setRequest($jsonRpcRequest);
            $jsonRpcRequest->parseRequest();

            list($serviceName, $methodName) = $this->getServiceAndMethodName($jsonRpcRequest->getMethod());

            if (!array_key_exists($serviceName, $this->services)) {
                throw new JsonRpcException(JsonRpcException::METHOD_NOT_FOUND);
            }

            $response->setResult($this->processMethod($serviceName, $methodName, $jsonRpcRequest));

        } catch (JsonRpcException $e) {
            $response->setError($e);
            $stopWatch->stop('api');
        }

        return $response->getHttpResponse();
    }

    /**
     * @param $service
     * @throws \ReflectionException
     */
    public function addService($service)
    {
        $serviceReflectionClass = new \ReflectionClass(get_class($service));
        $serviceClass = lcfirst($serviceReflectionClass->getShortName());
        $serviceClass = u($serviceClass)->snake()->toString();
        $this->services[$serviceClass] = $service;
    }

}
