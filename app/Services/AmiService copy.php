<?php

namespace App\Services;

use Illuminate\Support\Facades\Log;
use PHPAMI\Ami;
use PHPAMI\Exceptions\AmiException;

class AmiService
{
    protected ?Ami $client = null;
    protected bool $connected = false;

    protected string $host;
    protected int $port;
    protected string $username;
    protected string $secret;
    protected int $timeout;

    public function __construct()
    {
        $this->host = config('ami.host', '127.0.0.1');
        $this->port = config('ami.port', 5038);
        $this->username = config('ami.username', 'myapi');
        $this->secret = config('ami.secret', 'Pakistan@12');
        $this->timeout = config('ami.timeout', 10);
    }

    public function connect(): bool
    {
        if ($this->connected && $this->client !== null) {
            return true;
        }
        
        try {
            $this->client = new Ami();

            $result = $this->client->connect(
                "{$this->host}:{$this->port}",
                $this->username,
                $this->secret,
                'off'
            );

            if ($result === false) {
                Log::error('AMI Connection failed');
                return false;
            }

            $this->connected = true;

            Log::info('AMI Connected Successfully');
// return $this->originateCall([
//     'channel'   => $channel ?? "{$driver}/{$extension}",
//     'extension' => $extension,
//     'caller_id' => $callerId,
//     'context'   => 'from-internal',
// ]);
            return true;
        } catch (\Throwable $e) {
            Log::error('AMI Connection error: ' . $e->getMessage());
            return false;
        }
    }

    public function disconnect(): void
    {
        if ($this->client !== null) {
            try {
                $this->client->disconnect();
            } catch (\Throwable $e) {
                Log::warning('AMI Disconnect error: ' . $e->getMessage());
            }
        }

        $this->client = null;
        $this->connected = false;
    }

    public function isConnected(): bool
    {
        return $this->connected && $this->client !== null;
    }

    public function makeCall(string $extension, string $callerId, string $channel = null): bool
    {
        return $this->originateCall([
            'channel' => $channel ?? "SIP/{$extension}",
            'extension' => $extension,
            'caller_id' => $callerId,
            'context' => 'from-internal',
        ]);
    }

    public function originateCall(array $params): bool
    {
        if (!$this->isConnected() && !$this->connect()) {
            return false;
        }

        try {
            $action = [
                'Action' => 'Originate',
                'Channel' => $params['channel'] ?? "SIP/{$params['extension']}",
            ];

            if (isset($params['extension'])) {
                $action['Exten'] = $params['extension'];
                $action['Context'] = $params['context'] ?? 'from-internal';
                $action['Priority'] = $params['priority'] ?? 1;
            }

            if (!empty($params['caller_id'])) {
                $action['CallerID'] = $params['caller_id'];
            }

            if (isset($params['timeout'])) {
                $action['Timeout'] = $params['timeout'];
            }

            $response = $this->client->send_action_request($action);

            $responseArray = $this->parseResponse($response);

            if (isset($responseArray['Response']) && $responseArray['Response'] === 'Success') {
                return true;
            }

            Log::error('AMI Originate failed: ' . ($responseArray['Message'] ?? 'Unknown error'));
            return false;
        } catch (AmiException $e) {
            Log::error('AMI Originate failed: ' . $e->getMessage());
            return false;
        }
    }

    public function sendAction(array $action): array
    {
        if (!$this->isConnected() && !$this->connect()) {
            return ['success' => false, 'error' => 'Connection failed'];
        }

        try {
            $response = $this->client->send_action_request($action);

            $responseArray = $this->parseResponse($response);

            return [
                'success' => isset($responseArray['Response']) && $responseArray['Response'] !== 'Error',
                'response' => $responseArray,
            ];
        } catch (AmiException $e) {
            return ['success' => false, 'error' => $e->getMessage()];
        }
    }

    protected function parseResponse(string $response): array
    {
        $result = [];

        $lines = explode("\r\n", trim($response));

        foreach ($lines as $line) {
            if (strpos($line, ':') !== false) {
                [$key, $value] = explode(':', $line, 2);
                $result[trim($key)] = trim($value);
            }
        }

        return $result;
    }

    public function ping(): bool
    {
        if (!$this->isConnected() && !$this->connect()) {
            return false;
        }

        try {
            $response = $this->client->send_action_request(['Action' => 'Ping']);

            $responseArray = $this->parseResponse($response);

            return isset($responseArray['Response']) && $responseArray['Response'] === 'Success';
        } catch (AmiException $e) {
            Log::error('AMI Ping failed: ' . $e->getMessage());
            return false;
        }
    }

    public function command(string $command): array
    {
        if (!$this->isConnected() && !$this->connect()) {
            return ['success' => false, 'error' => 'Connection failed'];
        }

        try {
            $response = $this->client->send_action_request([
                'Action' => 'Command',
                'Command' => $command,
            ]);

            $responseArray = $this->parseResponse($response);

            return [
                'success' => true,
                'response' => $responseArray,
            ];
        } catch (AmiException $e) {
            return ['success' => false, 'error' => $e->getMessage()];
        }
    }
}