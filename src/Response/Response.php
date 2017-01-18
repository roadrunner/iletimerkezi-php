<?php

namespace Emarka\Sms\Response;

class Response
{
    private $responseRaw;
    private $response;
    private $timestamp;

    public function __construct($response_raw)
    {
        $this->responseRaw = $response_raw;
        $this->timestamp   = time();
        $this->response    = simplexml_load_string($response_raw);
    }

    public function isSuccess()
    {
        if (false !== $this->response) {
            return 200 == $this->statusCode();
        }
        return false;
    }

    public function status()
    {
        return ResponseStatus::get($this->statusCode());
    }
    public function statusCode()
    {
        return $this->response->status->code;
    }

    public function statusMessage()
    {
        return $this->response->status->message;
    }

    public function getChildNode($node)
    {
        if (isset($this->response->$node)) {
            return $this->response->$node;
        }
    }

    public function xpath($xpath_query)
    {
        return $this->response->xpath($xpath_query);
    }

    // public function __call($name, $arguments)
    // {
    //     if (0 === strpos($name, 'get')) {
    //         return $this->getChildNode(strtolower(substr($name, 3)));
    //     }
    // }
}
