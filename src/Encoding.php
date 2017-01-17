<?php

namespace Emarka\Sms;

final class Encoding
{
    const GSM8            = 'gsm0338';
    const TURKISH         = 'gsm0338-tr';
    const UNICODE         = 'utf8';
    const ACCOUNT_DEFAULT = 'default';

    private $encoding;

    public function __construct($encoding = '')
    {
        $this->_setEncoding((string)$encoding);
    }

    public function isAccountDefault()
    {
        return self::ACCOUNT_DEFAULT == $this->encoding;
    }

    /**
     * Gets the value of encoding.
     *
     * @return mixed
     */
    public function getEncoding()
    {
        return $this->encoding;
    }

    /**
     * Sets the value of encoding.
     *
     * @param string $encoding the encoding
     *
     * @return self
     */
    private function _setEncoding($encoding)
    {
        switch ($encoding) {
            case 'gsm8':
            case 'gsm0338':
                $encoding = self::GSM8;
                break;
            case 'turkish':
            case 'gsm0338-tr':
            case 'tr':
                $encoding = self::TURKISH;
                break;
            case 'ucs2':
            case 'unicode':
                $encoding = self::UNICODE;
                break;
            default:
                $encoding = self::ACCOUNT_DEFAULT;
                break;
        }
        $this->encoding = $encoding;
        return $this;
    }

    public function __toString()
    {
        return $this->getEncoding();
    }
}
