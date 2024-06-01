<?php

namespace Norgul\Xmpp;

class Example
{
    protected static $host     = 'host.example.com';
    protected static $port     = 5222;
    protected static $username = 'foo';
    protected static $password = 'bar';

    public static function test()
    {
        $options = new Options();

        $options
            ->setHost(self::$host)
            ->setPort(self::$port)
            ->setUsername(self::$username)
            ->setPassword(self::$password);

        $client = new XmppClient($options);
        $client->connect();

        $client->iq->getRoster();

        $client->message->send('Hello world', 'test@jabber.com');

        // Uncomment if you want to manually enter raw XML (or call a function) and see a server response
//        (new self)->sendRawXML($client);

        do {
            $response = $client->getResponse();
            $client->prettyPrint($response);
        } while (true);

        $client->disconnect();
    }

    public function sendRawXML(XmppClient $client)
    {
        do {
            $response = $client->getResponse();
            $client->prettyPrint($response);

            // if you provide a function name here, (i.e. getRoster ...arg)
            // the function will be called instead of sending raw XML
            $line = readline("\nEnter XML: ");

            if ($line == 'exit') {
                break;
            }

            $parsedLine = explode(' ', $line);

            if (method_exists($client, $parsedLine[0])) {
                if (count($parsedLine) < 2) {
                    $client->{$parsedLine[0]}();
                    continue;
                }
                $client->{$parsedLine[0]}($parsedLine[1]);
                continue;
            }

            if (@simplexml_load_string($line)) {
                $client->send($line);
                continue;
            }

            echo "This is not a method nor a valid XML";
        } while ($line != 'exit');
    }
}

ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

require __DIR__ . '/vendor/autoload.php';
Example::test();
