<?php

namespace Emarka\Sms\Response;

use Emarka\Sms\StateInterface;

class ResponseStatus implements StateInterface
{
    const TEXT_ENROUTE               = 110;
    const TEXT_DELIVRD               = 111;
    const TEXT_FAILED                = 112;

    const ORDER_ENROUTE              = 113;
    const ORDER_COMPLETED            = 114;
    const ORDER_FAILED               = 115;

    const OK                         = 200;
    const MALFORMED_REQUEST          = 400;
    const AUTH_FAILED                = 401;
    const INSUFFICIENT_BALANCE       = 402;
    const METHOD_NOT_FOUND           = 404;
    const SENDER_NOT_APPROVED        = 450;
    const DUPLICATE_SUBMIT           = 451;
    const INVALID_RECIPIENTS         = 452;
    const EXCEEDED_MAX_SUBMIT_LENGTH = 453;
    const EMPTY_TEXT_BODY            = 454;
    const ORDER_NOT_FOUND            = 455;
    const FUTURE_DELIVERY            = 456;
    const INVALID_DATE_FORMAT        = 457;
    const SERVER_ERROR               = 503;
    const TIMEOUT_ERROR              = 28; // curl error
    const UNKNOWN_ERROR              = 999;

    static protected $responseCodeMap = [
        self::TEXT_ENROUTE               => 'TEXT_ENROUTE',
        self::TEXT_DELIVRD               => 'TEXT_DELIVRD',
        self::TEXT_FAILED                => 'TEXT_FAILED',

        self::ORDER_ENROUTE              => 'ORDER_ENROUTE',
        self::ORDER_COMPLETED            => 'ORDER_COMPLETED',
        self::ORDER_FAILED               => 'ORDER_FAILED',

        self::OK                         => 'OK',
        self::MALFORMED_REQUEST          => 'MALFORMED_REQUEST',
        self::AUTH_FAILED                => 'AUTH_FAILED',
        self::INSUFFICIENT_BALANCE       => 'INSUFFICIENT_BALANCE',
        self::METHOD_NOT_FOUND           => 'METHOD_NOT_FOUND',
        self::SENDER_NOT_APPROVED        => 'SENDER_NOT_APPROVED',
        self::DUPLICATE_SUBMIT           => 'DUPLICATE_SUBMIT',
        self::INVALID_RECIPIENTS         => 'INVALID_RECIPIENTS',
        self::EXCEEDED_MAX_SUBMIT_LENGTH => 'EXCEEDED_MAX_SUBMIT_LENGTH',
        self::EMPTY_TEXT_BODY            => 'EMPTY_TEXT_BODY',
        self::ORDER_NOT_FOUND            => 'ORDER_NOT_FOUND',
        self::FUTURE_DELIVERY            => 'FUTURE_DELIVERY',
        self::INVALID_DATE_FORMAT        => 'INVALID_DATE_FORMAT',
        self::SERVER_ERROR               => 'SERVER_ERROR',
        self::TIMEOUT_ERROR              => 'TIMEOUT_ERROR',
        self::UNKNOWN_ERROR              => 'UNKNOWN_ERROR',
    ];

    static protected $responseCodeDescriptions = [
        self::TEXT_ENROUTE               => 'Message is being sent or waiting for delivery report.',
        self::TEXT_DELIVRD               => 'Message has been delivered.',
        self::TEXT_FAILED                => 'Message has failed.',

        self::ORDER_ENROUTE              => 'Order not completed, waiting for delivery reports.',
        self::ORDER_COMPLETED            => 'Order has been successfully completed.',
        self::ORDER_FAILED               => 'Order has failed to sent',

        self::OK                         => 'Operation successful.',
        self::MALFORMED_REQUEST          => 'Malformed request. Please report.',
        self::AUTH_FAILED                => 'Authentication failed.',
        self::INSUFFICIENT_BALANCE       => 'Insufficient balance.',
        self::METHOD_NOT_FOUND           => 'Requested method not found.',
        self::SENDER_NOT_APPROVED        => 'Sender not approved.',
        self::DUPLICATE_SUBMIT           => 'Duplicate submit.',
        self::INVALID_RECIPIENTS         => 'Invalid recipients.',
        self::EXCEEDED_MAX_SUBMIT_LENGTH => 'You have exceeded max submit length.',
        self::EMPTY_TEXT_BODY            => 'Empty text body given.',
        self::ORDER_NOT_FOUND            => 'Order not found.',
        self::FUTURE_DELIVERY            => 'Requested order is future delivery, not there yet.',
        self::INVALID_DATE_FORMAT        => 'Invalid date format given.',
        self::SERVER_ERROR               => 'Server error occurred. Please report.',
        self::TIMEOUT_ERROR              => 'Request timeout. Please check the connectivity.',
        self::UNKNOWN_ERROR              => 'Unkown error occurred.',
    ];

    static protected $registry = [];

    private $responseCode;

    private function __construct($responseCode)
    {
        $this->responseCode = $responseCode;
    }

    public function isSuccess()
    {
        return $this->code() > self::TEXT_ENROUTE && $this->code() <= self::OK;
    }

    public function code()
    {
        return $this->responseCode;
    }

    public function state()
    {
        return self::$responseCodeMap[$this->code()];
    }

    public function description()
    {
        return self::$responseCodeDescriptions[$this->code()];
    }

    public function __toString()
    {
        return $this->state();
    }

    public static function get($responseCode)
    {
        $responseCode = (int) $responseCode;
        if (!isset(self::$registry[$responseCode])) {
            if (!isset(self::$responseCodeMap[$responseCode])) {
                $responseCode = self::UNKNOWN_ERROR;
            }
            self::$registry[$responseCode] = new self($responseCode);
        }
        return self::$registry[$responseCode];
    }
}
