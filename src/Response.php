<?php

namespace Emarka\Sms;

class Response
{
    private $responseRaw;
    private $response;
    private $timestamp;

    private $statusCode;
    private $statusMessage;

    public function __construct($response_raw)
    {
        $this->responseRaw = $response_raw;
        $this->timestamp   = time();
        $this->response    = simplexml_load_string($response_raw);
    }

    public function isSuccess()
    {
        if (false !== $this->response) {
            return 200 == $this->getStatusCode();
        }
        return false;
    }

    public function getStatusCode()
    {
        return $this->response->status->code;
    }

    public function getStatusMessage()
    {
        return $this->response->status->message;
    }

    public function getAttribute($node)
    {
        if (isset($this->response->$node)) {
            return is_object($this->response->{$node}) ? $this->response->{$node} : $this->response->{$node};
        }
    }

    public function __call($name, $arguments)
    {
        if (0 === strpos($name, 'get')) {
            return $this->getAttribute(strtolower(substr($name, 3)));
        }
    }
}
