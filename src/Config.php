<?php

namespace Emarka\Sms;

class Config
{
    protected $scheme            = 'https';
    protected $domain            = 'api.iletimerkezi.com';
    protected $apiVersion        = 'v1';
    protected $apiKey;
    protected $secret;
    protected $sender;
    protected $encoding          = Encoding::ACCOUNT_DEFAULT;
    protected $countryCode;
    protected $requestTimeout    = 60;
    protected $apiTimezoneOffset = 3; // GMT+3
    protected $localTimezone;
    protected $timeout;
    protected $debug             = false;

    public function __construct(array $config = [])
    {
        foreach ($config as $key => $value) {
            switch ($key) {
                case 'scheme':
                    $this->setScheme($value);
                    break;
                case 'domain':
                    $this->setDomain($value);
                    break;
                case 'api_key':
                case 'apiKey':
                case 'username':
                    $this->setApiKey($value);
                    break;
                case 'secret':
                case 'password':
                    $this->setSecret($value);
                    break;
                case 'sender':
                    $this->setSender($value);
                    break;
                case 'encoding':
                    $this->setEncoding($value);
                    break;
                case 'countryCode':
                case 'country_code':
                    $this->setCountryCode($value);
                    break;
                // case 'api_timezone_offset':
                //     $this->setApiTimezoneOffset($value);
                //     break;
                case 'local_timezone':
                    $this->setLocalTimezone($value);
                    break;
                case 'timeout':
                    $this->setTimeout($value);
                    break;
                case 'debug':
                    $this->debug = (bool) $value;
                    break;
                default:
                    throw new \Exception(sprintf("Invalid parameter %s given.", $key), 1);
                    break;
            }
        }
    }

    public function getRequestUrl($endpoint)
    {
        return sprintf(
            "%s://%s/%s/%s",
            $this->getScheme(),
            $this->getDomain(),
            $this->getApiVersion(),
            $endpoint
        );
    }

    public function getRequestTimeout()
    {
        return $this->requestTimeout;
    }

    public function getApiVersion()
    {
        return $this->apiVersion;
    }

    /**
     * Gets the value of scheme.
     *
     * @return mixed
     */
    public function getScheme()
    {
        return $this->scheme;
    }

    /**
     * Sets the value of scheme.
     *
     * @param mixed $scheme the scheme
     *
     * @return self
     */
    public function setScheme($scheme)
    {
        if (!($scheme == 'http' || $scheme == 'https')) {
            throw new \Exception('Unsupported protocol scheme');
        }
        $this->scheme = $scheme;
        return $this;
    }

    /**
     * Gets the value of domain.
     *
     * @return mixed
     */
    public function getDomain()
    {
        return $this->domain;
    }

    /**
     * Sets the value of domain.
     *
     * @param mixed $domain the domain
     *
     * @return self
     */
    public function setDomain($domain)
    {
        $this->domain = $domain;
        return $this;
    }

    /**
     * Gets the value of apiKey.
     *
     * @return mixed
     */
    public function getApiKey()
    {
        return $this->apiKey;
    }

    /**
     * Sets the value of apiKey.
     *
     * @param mixed $apiKey the api key
     *
     * @return self
     */
    public function setApiKey($apiKey)
    {
        if (!(strlen($apiKey) >= 16 && strlen($apiKey) <= 40)) {
            throw new \Exception('Api key must be >= 16 and <= 40 characters');
        }
        $this->apiKey = $apiKey;
        return $this;
    }

    /**
     * Gets the value of secret.
     *
     * @return mixed
     */
    private function getSecret()
    {
        return $this->secret;
    }

    /**
     * Sets the value of secret.
     *
     * @param mixed $secret the secret
     *
     * @return self
     */
    public function setSecret($secret)
    {
        if (!(strlen($secret) >= 40 && strlen($secret) <= 120)) {
            throw new \Exception('Api secret must be >= 40 and <= 120 characters');
        }
        $this->secret = $secret;
        return $this;
    }

    public function getSecretHmac()
    {
        return hash_hmac('sha256', $this->getApiKey(), $this->getSecret());
    }

    /**
     * Gets the value of sender.
     *
     * @return mixed
     */
    public function getSender()
    {
        return $this->sender;
    }

    /**
     * Sets the value of sender.
     *
     * @param mixed $sender the sender
     *
     * @return self
     */
    public function setSender($sender)
    {
        if (empty($sender)) {
            $this->sender = null;
            return $this;
        }
        if (strlen($sender) > 11) {
            throw new \Exception('Sender name can\t be longer than 11 characters.');
        }
        if (preg_match('/^[\d\s]+$/', $sender)) {
            throw new \Exception('Sender must include alpha-numeric character.');
        }
        $this->sender = $sender;
        return $this;
    }

    /**
     * Gets the value of encoding.
     *
     * @return mixed
     */
    public function getEncoding($user_encoding = null)
    {
        return !empty($user_encoding) ? $user_encoding : $this->encoding;
    }

    /**
     * Sets the value of encoding.
     *
     * @param mixed $encoding the encoding
     *
     * @return self
     */
    public function setEncoding($encoding)
    {
        $this->encoding = $encoding;
        return $this;
    }



    /**
     * Gets the value of countryCode.
     *
     * @return mixed
     */
    public function getCountryCode()
    {
        return $this->countryCode;
    }

    /**
     * Sets the value of countryCode.
     *
     * @param mixed $countryCode the country code
     *
     * @return self
     */
    public function setCountryCode($countryCode)
    {
        $this->countryCode = $countryCode;
        return $this;
    }



    public function validate()
    {
        return true;
    }

    /**
     * Gets the value of debug.
     *
     * @return mixed
     */
    public function isDebug()
    {
        return $this->debug;
    }

    /**
     * Gets the value of apiTimezone.
     *
     * @return mixed
     */
    public function getApiTimezone()
    {
        return 'Etc/GMT'.($this->apiTimezoneOffset > 0 ? '+' : '').$this->apiTimezoneOffset;
    }

    public function getApiDateWithTimezoneOffset($apiDate)
    {
        return $apiDate.' GMT'.($this->apiTimezoneOffset > 0 ? '+' : '').$this->apiTimezoneOffset;
    }
    /**
     * Sets the value of apiTimezone.
     *
     * @param mixed $apiTimezone the api timezone
     *
     * @return self
     */
    protected function setApiTimezoneOffset($apiTimezoneOffset)
    {
        $this->apiTimezoneOffset = $apiTimezoneOffset;
        return $this;
    }

    /**
     * Gets the value of localTimezone.
     *
     * @return mixed
     */
    public function getLocalTimezone()
    {
        return @isset($this->localTimezone) ? $this->localTimezone : date_default_timezone_get();
    }

    /**
     * Sets the value of localTimezone.
     *
     * @param mixed $localTimezone the local timezone
     *
     * @return self
     */
    protected function setLocalTimezone($localTimezone)
    {
        $this->localTimezone = $localTimezone;

        return $this;
    }

    /**
     * Gets the value of timeout.
     *
     * @return mixed
     */
    public function getTimeout()
    {
        return $this->timeout;
    }

    /**
     * Sets the value of timeout.
     *
     * @param mixed $timeout the timeout
     *
     * @return self
     */
    protected function setTimeout($timeout)
    {
        $this->timeout = (bool) $timeout;
        return $this;
    }
}
