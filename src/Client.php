<?php

namespace Emarka\Sms;

use Emarka\Sms\Response\Response;
use Emarka\Sms\Response\ReportResponseIterator;
use Emarka\Sms\Response\BlacklistResponseIterator;

class Client
{
    private $version;
    private $config;
    private $curlHandler;

    private $lastResponse;
    private $apiTimezone;
    private $clientTimezone;

    private function __construct(Config $config)
    {
        $ch = curl_init();
        if (false === $ch) {
            throw new \Exception('Unable to initialize curl session.');
        }

        curl_setopt($ch, CURLOPT_POST, 1);
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 2);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, 0);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type' => 'application/xml']);
        curl_setopt($ch, CURLOPT_HEADER, 0);
        curl_setopt($ch, CURLOPT_FOLLOWLOCATION, 1);
        curl_setopt($ch, CURLOPT_TIMEOUT, $config->getRequestTimeout());

        if ($config->isDebug()) {
            curl_setopt($ch, CURLOPT_VERBOSE, true);
        }
        $this->curlHandler = $ch;
        $this->_setConfig($config);
        $this->apiTimezone = new \DateTimeZone($config->getApiTimezone());
        $this->localTimezone = new \DateTimeZone($config->getLocalTimezone());
    }

    /**
     * Get the current balance of the account
     * @return AccountBalance
     */
    public function balance()
    {
        $result = $this->_executeRequestAndReturn('get-balance', [], 'balance');
        if (!$result) {
            return new AccountBalance(0, 0);
        }
        return new AccountBalance($result->amount, $result->sms);
    }

    /**
     * Request a new originator. Note that, since manual approval required,
     * requesting new originator will not be show up in the originators query result immediately.
     * @param  string $originator  11 character alpha-numeric originator name
     * @param  string $description Description for the originator
     * @return bool
     */
    public function newOriginator($originator, $description)
    {
        return $this->_executeRequest('add-sender', [
            'originator' => [
                'sender'      => $originator,
                'description' => $description
            ]
        ])->status();
    }

    /**
     * Returns only approved originators
     * @return array list of approved originators
     */
    public function originators()
    {
        $result = $this->_executeRequestAndReturn('get-sender', [], 'senders');
        if (!$result || !isset($result->sender)) {
            return [];
        }
        return (array) $result->sender;
    }

    public function senders()
    {
        return $this->originators(); // alias of originators
    }

    public function settings()
    {
        return $this->_executeRequestAndReturn('get-settings', [], 'settings');
    }

    public function addToBlacklist($number)
    {
        $xml_body = '<blacklist><number>'.htmlspecialchars($number, \ENT_XML1).'</number></blacklist>';
        return $this->_executeRequest('add-blacklist', $xml_body)->status();
    }

    public function removeFromBlacklist($number)
    {
        $xml_body = '<blacklist><number>'.htmlspecialchars($number, \ENT_XML1).'</number></blacklist>';
        return $this->_executeRequest('delete-blacklist', $xml_body)->status();
    }

    public function reportIterator($id, $page = 1, $page_size = 1000)
    {
        return new ReportResponseIterator($this, ['id' => $id, 'page' => $page, 'page_size' => $page_size]);
        // return new Report($this, ['id' => $id, 'page' => $page, 'page_size' => $page_size]);
    }

    public function blacklistIterator($page = 1, $page_size = 1000)
    {
        return new BlacklistResponseIterator($this, ['page' => $page, 'page_size' => $page_size]);
    }

    public function fetchPageBlacklist($page = 1, $page_size = 1000)
    {
        return $this->_executeRequestAndReturn(
            'get-blacklist',
            [
                'blacklist' => [
                    'page'     => $page,
                    'rowCount' => $page_size
                ]
            ],
            'blacklist'
        );
    }

    public function fetchPageReport($id, $page = 1, $page_size = 1000)
    {
        return $this->_executeRequestAndReturn(
            'get-report',
            [
                'order' => [
                    'id'       => $id,
                    'page'     => $page,
                    'rowCount' => $page_size
                ]
            ],
            'order'
        );
    }

    public function send($recipients, $message = null, $options = [], \Clousre $callback = null)
    {
        $recipients = (array) $recipients;

        $sender     = isset($options['sender']) ? $options['sender'] : $this->config->getSender();
        $encoding   = new Encoding($this->config->getEncoding(isset($options['encoding']) ? $options['encoding'] : null));
        $send_at    = isset($options['sent_at']) ? $this->resolveTime($options['send_at']) : null;

        if (empty($recipients)) {
            throw new \Exception('Recipient list can\'t be empty.');
        }

        if (empty($sender)) {
            throw new \Exception('Sender can\'t be empty.');
        }

        $xml_order  = '<order><sender>'.htmlspecialchars($sender, \ENT_XML1).'</sender>';
        $xml_order .= ($send_at === null ? '' : '<sendDateTime>'.htmlspecialchars($send_at, \ENT_XML1).'</sendDateTime>');
        $xml_order .= ($encoding->isAccountDefault() ? '' : '<encoding>'.htmlspecialchars($encoding->getEncoding(), \ENT_XML1).'</encoding>');

        if (!empty($message)) { // single message context
            $xml_order .= $this->_createMessageContext($recipients, $message);
        } else { // multi & split message context
            foreach ($recipients as $recipient => $message_c) {
                $xml_order .= $this->_createMessageContext($recipient, $message_c);
            }
        }

        $xml_order .= '</order>';

        $result = $this->_executeRequestAndReturn('send-sms', $xml_order, 'order');

        if ($result) {
            return (string) $result->id;
        }
        return false;
    }

    private function _createMessageContext($recipients, $text)
    {
        $recipients_xml = implode('</number><number>', (array) $recipients);
        return <<<XML
 <message>
    <text><![CDATA[{$text}]]></text>
    <receipents>
        <number>{$recipients_xml}</number>
    </receipents>
</message>
XML;
    }

    private function _executeRequestAndReturn($endpoint, $request, $return_node)
    {
        $response = $this->_executeRequest($endpoint, $request);

        if ($response->isSuccess()) {
            return $response->getChildNode($return_node);
        }
        return [];
    }

    private function _executeRequest($endpoint, $request)
    {
        $post_xml = $this->_createRequestXml($request);
        $config = $this->getConfig();

        if ($config->isDebug()) {
            print_r($post_xml);
        }

        curl_setopt($this->curlHandler, \CURLOPT_URL, $config->getRequestUrl($endpoint));
        curl_setopt($this->curlHandler, \CURLOPT_POSTFIELDS, $post_xml);

        if ($config->getTimeout() > 0) {
            curl_setopt($this->curlHandler, \CURLOPT_TIMEOUT, $config->getTimeout());
        }
        $response_raw = curl_exec($this->curlHandler);
        $curl_errno   = curl_errno($this->curlHandler);
        $curl_error   = curl_error($this->curlHandler);

        if ($curl_errno > 0) {
            $response_raw = "<response>\n\t<status>\n\t\t<code>'.$curl_errno.'</code><message><![CDATA['.$curl_error.']]></message></status></response>";
        }

        $response = new Response($response_raw);

        if ($config->isDebug()) {
            print_r($response_raw);
        }

        $this->_setLastResponse($response);
        return $response;
    }

    private function _createRequestXml($request, $cdata_fields = [])
    {
        return sprintf(
            "<request>\n%s\n\t%s\n</request>\n",
            $this->_getRequestHeaderXml(),
            is_string($request) ? $request : $this->_createRequestXmlNode('', $request, '', $cdata_fields)
        );
    }

    private function _createRequestXmlNode($node, $value, $parent, $cdata_fields)
    {
        if (is_array($value) || is_object($value)) {
            $node_xml = empty($node) ? '' : '<'.$node.'>';
            foreach ((array)$value as $key => $val) {
                $node_xml .= $this->_createRequestXmlNode($key, $val, empty($parent) ? $node : $parent.'.'.$node, $cdata_fields);
            }
            return $node_xml.(empty($node) ? '' : '</'.$node.'>');
        }
        $cdata_field = isset($cdata_fields[$parent.'.'.$node]);
        return '<'.$node.'>'.($cdata_field ? '<![CDATA[' : '').$value.($cdata_field ? ']]>' : '').'</'.$node.'>';
    }

    private function _getRequestHeaderXml()
    {
        return sprintf(
            "\t<authentication>\n\t\t<key>%s</key>\n\t\t<hash>%s</hash>\n\t</authentication>",
            htmlspecialchars($this->config->getApiKey(), \ENT_XML1),
            htmlspecialchars(hash_hmac('sha256', $this->config->getApiKey(), $this->config->getSecret()), \ENT_XML1)
        );
    }

    /**
     * Creates the Sms api client
     * @param  array|Emarka\Sms\Config $config
     * @return Emarka\Sms\Client
     */
    public static function createClient($config)
    {
        if (is_array($config)) {
            $config = new Config($config);
        }

        if (!$config instanceof Config) {
            throw new \Exception("Invalid configuration given", 1);
        }

        $config->validate();
        return new self($config);
    }

    public function formatFromApiDate($date, $format = \DATE_RFC2822)
    {
        // $date = new \DateTime('Thu, 31 Mar 2011 02:05:59 GMT');
        $datetime = new \DateTime($this->getConfig()->getApiDateWithTimezoneOffset($date), $this->apiTimezone);
        $datetime->setTimezone($this->localTimezone);
        return $datetime->format($format);
    }

    /**
     * Gets the value of lastResponse.
     *
     * @return mixed
     */
    public function getLastResponse()
    {
        return $this->lastResponse;
    }

    /**
     * Sets the value of lastResponse.
     *
     * @param mixed $lastResponse the last response
     *
     * @return self
     */
    private function _setLastResponse($lastResponse)
    {
        $this->lastResponse = $lastResponse;
        return $this;
    }

    /**
     * Gets the configuration
     *
     * @return Emarka\Sms\Config
     */
    public function getConfig()
    {
        return $this->config;
    }

    /**
     * Sets the configuration
     *
     * @param Emarka\Sms\Config $config the config
     *
     * @return self
     */
    private function _setConfig($config)
    {
        $this->config = $config;
        return $this;
    }

    public function __destruct()
    {
        if ($this->curlHandler) {
            curl_close($this->curlHandler); // free curl handler resource
        }
    }
}
