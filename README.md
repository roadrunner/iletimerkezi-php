# iletimerkezi-php
iletimerkezi.com PHP Client library


## Road map for v01:

* send sms
* deal with errors
* query prev. sended sms reports
* query account balance
* query available senders (aka originator)

## Examples

### Let's create a client:
```php
<?php
require_once 'vendor/autoload.php';

use Emarka\Sms\Client;

$client = Client::createClient([
    'api_key'        => '<xyz-etc>', // generate api key / secret pair from account -> settings
    'secret'         => '<secret>', // don't ever expose this guy.
    'sender'         => 'Engin Dumlu',
    'local_timezone' => 'Europe/Istanbul',
]);
```

### Sending simple message - examples
- simple
```php
$client->send('5321112233', 'Hello World'); // this is the most basic usage of sending sms
```

- simple, to multiple recipients
```php
$client->send(['5321112233', '5321112234'], 'Hello World'); // same text to multiple recipients
```

- multiple recipients each with different text
```php
$client->send(
    [
        '05321112233' => 'Hello World',
        '05321112234' => 'Selam Dünya',
        '05321112235' => '你好，世界'
    ],  // recipients with messages
    null, //
    [
        'encoding' => 'unicode', // text encoding
    ]
);
```

### Optional parameters

Message encoding: can we any of gsm8 | turkish | unicode. Or leave it off for account default.
```php
$client->send('5321112233', 'Türkçe sms göndermek bu kadar zor olmamalı.', [
    'encoding' => 'turkish', // text encoding
]); 
```

Future delivery: can be any future date [TODO: unified and simple date parsing ]
```php
$client->send('5321112233', 'Meet me at the chinese restaurant!', [
    'send_at' => '2 hours later', // deliver sms at 2 hours later
]); 
```

Change the sender name [Aka: originator]
```php
$client->send('5321112233', 'Authentication verify. Please enter the code: '.mt_rand(9999, 99999), [
    'sender' => 'OTP Verify', // change the sender
]); 
```


Basic reporting with tracking id (a.k.a order_id)
```php
$tracking_id = $client->send(['5321112233', '5321112234', '5321112234'], 'Hello reporting..');

$reporter    = $client->reportIterator($tracking_id);
echo 'report id: '.         $reporter->getId().PHP_EOL;
echo 'sender: '.            $reporter->sender().PHP_EOL;
echo 'report status: '.     $reporter->status()->description().PHP_EOL;
echo 'total recipients: '.  $reporter->totalRecipients().PHP_EOL;
echo 'total delivered: '.   $reporter->totalDelivered().PHP_EOL;
echo 'total failed: '.      $reporter->totalFailed().PHP_EOL;
echo 'total enroute: '.     $reporter->totalEnroute().PHP_EOL;
echo 'send at: '.           $reporter->sendAt().PHP_EOL;
echo 'submit at: '.         $reporter->submitAt().PHP_EOL;
```

Iterating over recipient list
```php
$tracking_id = $client->send('5321112233', 'Hello World'); // later, you can query delivery status of the messages

$reporter = $client->reportIterator($tracking_id);

while ($reporter->next()) {
    $reporter->each(function ($number, Emarka\Sms\StateInterface $state) {
        echo $number.' -> '.$state->state().' / '.$state->description().PHP_EOL;
    });
}
```
```
+905321131913 # Message is being sent or waiting for delivery report.
```


Available originators (sender names)
```php
$senders = $client->originators();
print_r($senders);
```
Sample output:
```
Array
(
    [0] => Engin Dumlu
)
```

Query current balance of the account
```php
echo $client->balance()->humanReadable();
```
Sample output:
```
973 TL
```

### Blacklist operations

Add number to blacklist
```php
$status = $client->addToBlacklist('5321112233');
if ($status->isSuccess()) {
    // number added to blacklist
} else {
    // something went wrong;
    echo $status->description().PHP_EOL;
}

```

Remove number from blacklist
```php
$status = $client->removeFromBlacklist('5321112233');
if ($status->isSuccess()) {
    // number removed from blacklist
} else {
    // something went wrong;
    echo $status->description().PHP_EOL;
}
```

Fetch the blacked`list`
```php
$client->blacklistIterator()->each(function ($number) {
    echo $number.' is in the blacklist.'.PHP_EOL;
});
```



## Open source
We love getting feedback and contributions from the opensource community
Feel free to contribute.
