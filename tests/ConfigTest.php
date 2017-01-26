<?php

namespace Emarka\Test;

use Emarka\Sms\Config;

class ConfigTest extends \PHPUnit_Framework_TestCase
{
    protected function setUp()
    {
        // nothing to setup (for now)
    }

    public function testTimezoneOffset()
    {
        $options = [];
        $config  = new Config($options);
        $this->assertSame('Etc/GMT+3', $config->getApiTimezone());
        $this->assertSame('2017-01-20 12:12:12 GMT+3', $config->getApiDateWithTimezoneOffset('2017-01-20 12:12:12'));
    }

    public function schemeProvider()
    {
        return [
            ['http', false],
            ['https', false],
            ['ftp', true],
        ];
    }

    /**
     * @dataProvider schemeProvider
     */
    public function testScheme($scheme, $expectException)
    {
        if ($expectException) {
            $this->setExpectedException(\Exception::class);
        }

        $config = new Config(['scheme' => $scheme]);

        $this->assertSame(
            $scheme.'://'.$config->getDomain().'/'.$config->getApiVersion().'/call-remote',
            $config->getRequestUrl('call-remote')
        );
    }

    public function senderProvider()
    {
        return [
            ['foo', 'foo', false],
            ['bar', 'bar', false],
            ['baz', 'baz', false],
            ['', null, false],
            [null, null, false],
            ['12233', false, true], // must include at least 1 alpha
            ['longerthn 11', false, true],
        ];
    }

    /**
     * @dataProvider senderProvider
     */
    public function testSender($given, $expected, $expectException)
    {
        if ($expectException) {
            $this->setExpectedException(\Exception::class);
        }
        $config = new Config(['sender' => $given]);
        $this->assertSame($expected, $config->getSender());
    }


    public function apiKeysProvider()
    {
        return [
            [str_repeat('a', 15), 'less than 40 characters', true],
            [str_repeat('a', 16), str_repeat('b', 40), false], // api key >= 16
            [str_repeat('a', 32), str_repeat('b', 60), false],  // current implementation
        ];
    }

    /**
     * @dataProvider apiKeysProvider
     */
    public function testApiKeys($apiKey, $secret, $expectException)
    {
        if ($expectException) {
            $this->setExpectedException(\Exception::class);
        }
        $config = new Config(['api_key' => $apiKey, 'secret' => $secret]);
        $this->assertSame(hash_hmac('sha256', $apiKey, $secret), $config->getSecretHmac());
    }
}
