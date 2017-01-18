<?php

namespace Emarka\Sms\Response;

use Emarka\Sms\Client;

class BlacklistResponseIterator extends ResponseIterator
{
    public function __construct(Client $client, $options = [])
    {
        parent::__construct($client, $options);
    }

    protected function childXpathString()
    {
        return '/response/blacklist/number';
    }

    protected function pollPage($current_page, $page_size)
    {
        $this->getClient()->fetchPageBlacklist($current_page, $page_size);
    }
    protected function eachIterate($xpath_result, \Closure $callback)
    {
        while (list( , $value) = each($xpath_result)) {
            $callback((string)$value);
        }
    }
}
