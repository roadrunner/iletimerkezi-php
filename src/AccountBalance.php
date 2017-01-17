<?php

namespace Emarka\Sms;

class AccountBalance
{
    const TYPE_CREDIT = 'credit';
    const TYPE_CASH   = 'cash';

    private $currency = 'TL'; // currenly, TL currency supported

    private $smsCredit;
    private $cash;
    private $type;

    public function __construct($amount, $sms)
    {
        if ($amount > 0) {
            $this->type = self::TYPE_CASH;
            $this->cash = $amount;
        } else {
            $this->type = self::TYPE_CREDIT;
            $this->smsCredit = $sms;
        }
    }

    public function humanReadable()
    {
        return self::TYPE_CASH == $this->getType()
                ? $this->getCash().' '.$this->getCurrency()
                : $this->getSmsCredit().' Credit';
    }

    /**
     * Gets the value of currency.
     *
     * @return mixed
     */
    public function getCurrency()
    {
        return $this->currency;
    }

    /**
     * Gets the value of smsCredit.
     *
     * @return mixed
     */
    public function getSmsCredit()
    {
        return $this->smsCredit;
    }

    /**
     * Gets the value of cash.
     *
     * @return mixed
     */
    public function getCash()
    {
        return $this->cash;
    }

    /**
     * Gets the value of type.
     *
     * @return mixed
     */
    public function getType()
    {
        return $this->type;
    }

    public function __toString()
    {
        return $this->humanReadable();
    }
}
