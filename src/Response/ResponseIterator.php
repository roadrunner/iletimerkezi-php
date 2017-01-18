<?php

namespace Emarka\Sms\Response;

use Emarka\Sms\Client;

abstract class ResponseIterator
{
    /**
     * @var Emarka\Sms\Client
     */
    private $client;

    // private $currentPage;
    // private $pageSize;
    private $options;

    private $lastPoll;


    public function __construct(Client $client, $options = [])
    {
        $this->_setOptions(array_merge(['page' => 1, 'page_size' => 1000], $options));
        $this->_setClient($client);
    }

    protected function isPollRequired()
    {
        if (empty($this->lastPoll)) {
            return true;
        }

        if ($this->lastPoll != $this->page) {
            return true;
        }
        return false;
    }

    protected function isNextAvailable()
    {
        if ($this->isPollRequired()) {
            return true;
        }
        $current_result = $this->count();

        return ($current_result > 0 && $current_result >= $this->page_size);
    }

    public function pollIfRequired()
    {
        if ($this->isPollRequired()) {
            $this->poll();
        }
    }

    protected function poll()
    {
        $this->pollPage($this->page, $this->page_size);
        $this->lastPoll = $this->page;
    }

    public function next()
    {
        if ($this->isNextAvailable()) {
            if (!empty($this->lastPoll)) { // not first poll
                $this->page++;
            }
            $this->poll();
            return true;
        }
        return false;
    }

    public function each(\Closure $callback)
    {
        $this->pollIfRequired();

        $xpath_result = $this->getClient()->getLastResponse()->xpath($this->childXpathString());

        $this->eachIterate($xpath_result, $callback);
    }

    abstract protected function pollPage($page, $page_size);
    abstract protected function eachIterate($xpath_result, \Closure $callback);
    abstract protected function childXpathString();

    public function count()
    {
        $this->pollIfRequired();
        return count($this->getClient()->getLastResponse()->xpath($this->childXpathString()));
    }

    /**
     * Gets the value of client.
     *
     * @return Emarka\Sms\Client
     */
    public function getClient()
    {
        return $this->client;
    }

    /**
     * Sets the value of client.
     *
     * @param Emarka\Sms\Client $client the client
     *
     * @return self
     */
    private function _setClient(Client $client)
    {
        $this->client = $client;
        return $this;
    }

    /**
     * Gets the value of options.
     *
     * @return array
     */
    public function getOption($key, $default = null)
    {
        return isset($this->options[$key]) ? $this->options[$key] : $default;
    }

    /**
     * Sets the value of options.
     *
     * @param array $options the options
     *
     * @return self
     */
    protected function _setOption($key, $value)
    {
        $this->options[$key] = $value;
        return $this;
    }

    protected function _setOptions($options)
    {
        $this->options = $options;
    }

    public function __get($key)
    {
        return $this->getOption($key);
    }

    public function __set($key, $value)
    {
        $this->_setOption($key, $value);
    }
}
