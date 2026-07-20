<?php

namespace App\Services;

use Illuminate\Support\Facades\Log;

class AmiEventListener
{
    protected $socket;

    public function connect(): void
    {
        $host = config('ami.host');
        $port = config('ami.port');
        $user = config('ami.username');
        $secret = config('ami.secret');

        $this->socket = fsockopen($host, $port, $errno, $errstr, 10);

        if (!$this->socket) {
            throw new \Exception("AMI Connection Failed: {$errstr}");
        }

        $login =
            "Action: Login\r\n" .
            "Username: {$user}\r\n" .
            "Secret: {$secret}\r\n" .
            "Events: on\r\n\r\n";

        fwrite($this->socket, $login);

        while (!feof($this->socket)) {

            $line = trim(fgets($this->socket));

            if ($line === '') {
                break;
            }

            Log::info('AMI LOGIN', [
                'line' => $line,
            ]);
        }
    }

    public function listen(): void
    {
        $event = [];

        while (!feof($this->socket)) {

            $line = trim(fgets($this->socket));

            if ($line === '') {

                if (!empty($event)) {

                    $this->handleEvent($event);

                    $event = [];
                }

                continue;
            }

            if (str_contains($line, ':')) {

                [$key, $value] = explode(':', $line, 2);

                $event[trim($key)] = trim($value);
            }
        }
    }

    protected function handleEvent(array $event): void
    {
        Log::info('AMI EVENT', $event);
    }

    public function disconnect(): void
    {
        if ($this->socket) {

            fwrite($this->socket, "Action: Logoff\r\n\r\n");

            fclose($this->socket);
        }
    }
}