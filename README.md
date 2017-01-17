# iletimerkezi-php
iletimerkezi.com PHP Client library


## Road map for v01:

* send messages
* deal with errors
* query prev. sended sms reports
* query account balance
* query available senders (aka originator)

## Examples

### Let's create a client:
```php
<?php
require_once 'vendor/autoload.php';
date_default_timezone_set('Europe/Istanbul');

use Emarka\Sms\Client;

$client = Client::createClient([
    'api_key'  => '5321112233',
    'secret'   => '<secret>',
    'sender' => 'Engin Dumlu'
]);
```

### Sending simple message - examples
- simple
```php
$client->send('5321112233', 'Hello World'); // this is the most basic usage of sending sms
```

- simple with multiple recipient
```php
$client->send(['5321112233', '5321112234'], 'Hello World'); // same message to multiple recipient 
```

- multiple recipient with seperated messages
```php
$client->send(
    [
        '05321112233' => 'Hello World',
        '05321112234' => 'Selam Dünya',
        '05321112235' => '你好，世界'
    ],  // recipients with messages
    null, //
    [
        'encoding' => 'unicode', // message encoding
    ]
);
```

### Optional parameters

Message encoding: can we any of gsm8 | turkish | unicode. Or leave it off for account default.
```php
$client->send('5321112233', 'Türkçe sms göndermek bu kadar zor olmamalı.', [
    'encoding' => 'turkish', // message encoding
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

## Open source
We love getting feedback and contributions from the opensource community
Feel free to contribute.
