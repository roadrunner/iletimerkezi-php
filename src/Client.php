<?php

namespace Emarka\Sms;

class Client
{
    private $version;
    private $config;
    private $curlHandler;

    private $lastResponse;

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
        $this->config      = $config;
    }

    /**
     * Get the current balance of the account
     * @return AccountBalance
     */
    public function balance()
    {
        $result = $this->_executeRequestAndReturn('get-balance', [], 'balance');
        if (!$result) {
            $result = ['amount' => 0, 'sms' => 0];
        }
        return new AccountBalance($result['amount'], $result['sms']);
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
        ])->isSuccess();
    }

    /**
     * Returns only approved originators
     * @return array list of approved originators
     */
    public function originators()
    {
        $result = $this->_executeRequestAndReturn('get-sender', [], 'senders');
        if (!$result || !isset($result['sender'])) {
            return [];
        }
        return (array) $result['sender'];
    }

    public function settings()
    {
        return $this->_executeRequestAndReturn('get-settings', [], 'settings');
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
            return $response->getAttribute($return_node);
        }
        return [];
    }

    private function _executeRequest($endpoint, $request)
    {
        $post_xml = $this->_createRequestXml($request);
        if ($this->config->isDebug()) {
            print_r($post_xml);
        }

        curl_setopt($this->curlHandler, CURLOPT_URL, $this->config->getRequestUrl($endpoint));
        curl_setopt($this->curlHandler, CURLOPT_POSTFIELDS, $post_xml);

        $response_raw = curl_exec($this->curlHandler);
        $response     = new Response($response_raw);

        $this->_setLastResponse($response);
        return $response;
    }

    private function _createRequestXml($request, $cdata_fields = [])
    {
        return sprintf(
            "<request>\n%s\n%s\n</request>",
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
            '<authentication>
                <username>%s</username>
                <password>%s</password>
            </authentication>',
            htmlspecialchars($this->config->getApiKey(), \ENT_XML1),
            htmlspecialchars($this->config->getSecret(), \ENT_XML1)
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
}
