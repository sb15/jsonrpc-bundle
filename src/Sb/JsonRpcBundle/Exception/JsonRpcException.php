<?php

namespace Sb\JsonRpcBundle\Exception;

class JsonRpcException extends \Exception
{
    const PARSE_ERROR = -32700;
    const INVALID_REQUEST = -32600;
    const METHOD_NOT_FOUND = -32601;
    const INVALID_PARAMS = -32602;
    const INTERNAL_ERROR = -32603;

    private $data;

    private static $exceptionMap = [
        self::PARSE_ERROR => 'Parse error',
        self::INVALID_REQUEST => 'Invalid Request',
        self::METHOD_NOT_FOUND => 'Method not found',
        self::INVALID_PARAMS => 'Invalid params',
        self::INTERNAL_ERROR => 'Internal error',
    ];

    public function __construct($code, $message = null, $data = null)
    {
        if (null === $message && array_key_exists($code, self::$exceptionMap)) {
            $message = self::$exceptionMap[$code];
        }

        $this->data = $data;

        parent::__construct($message, $code);
    }

    public function getData()
    {
        return $this->data;
    }
}