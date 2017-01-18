<?php

namespace Emarka\Sms\Response;

use Emarka\Sms\Client;
use Emarka\Sms\DeliveryState;

class ReportResponseIterator extends ResponseIterator
{
    public function __construct(Client $client, $options = [])
    {
        parent::__construct($client, $options);
    }

    public function getOrder()
    {
        $this->pollIfRequired();
        return $this->getClient()->getLastResponse()->getChildNode('order');
    }

    public function getId()
    {
        return (int) $this->getOrder()->id;
    }

    public function status()
    {
        return ResponseStatus::get((string)$this->getOrder()->status);
    }
    public function totalRecipients()
    {
        return (int) $this->getOrder()->total;
    }
    public function totalDelivered()
    {
        return (int) $this->getOrder()->delivered;
    }
    public function totalFailed()
    {
        return (int) $this->getOrder()->undelivered;
    }
    public function totalEnroute()
    {
        return (int) $this->getOrder()->waiting;
    }
    public function submitAt($format = \DATE_RFC2822)
    {
        return $this->getClient()->formatFromApiDate((string) $this->getOrder()->submitAt, $format);
    }
    public function sendAt($format = \DATE_RFC2822)
    {
        return $this->getClient()->formatFromApiDate((string) $this->getOrder()->sendAt, $format);
    }
    public function sender()
    {
        return (string) $this->getOrder()->sender;
    }
    public function price()
    {
        return (string) $this->getOrder()->price;
    }

    protected function childXpathString()
    {
        return '/response/order/message';
    }

    protected function pollPage($current_page, $page_size)
    {
        $this->getClient()->fetchPageReport($this->id, $current_page, $page_size);
    }
    protected function eachIterate($xpath_result, \Closure $callback)
    {
        while (list( , $value) = each($xpath_result)) {
            $callback((string)$value->number, ResponseStatus::get((string)$value->status));
            // print_r($value);
        }
    }
}
