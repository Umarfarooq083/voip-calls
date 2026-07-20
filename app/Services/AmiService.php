<?php

namespace App\Services;

use Illuminate\Support\Facades\Log;
use PHPAMI\Ami;
use Illuminate\Support\Str;

class AmiService
{
    protected ?Ami $client = null;
    protected bool $connected = false;

    protected string $host;
    protected int $port;
    protected string $username;
    protected string $secret;
    protected int $timeout;
    protected string $driver;

    public function __construct()
    {
        $this->host = config('ami.host');
        $this->port = config('ami.port');
        $this->username = config('ami.username');
        $this->secret = config('ami.secret');
        $this->timeout = config('ami.timeout', 10);
        $this->driver = config('ami.driver', 'SIP');
    }

    /**
     * Connect to AMI
     */
    public function connect(): bool
    {
        if ($this->connected && $this->client instanceof Ami) {
            return true;
        }

        try {

            $this->client = new Ami();

            $connected = $this->client->connect(
                "{$this->host}:{$this->port}",
                $this->username,
                $this->secret,
                'on'
            );

            if (!$connected) {

                Log::error('AMI Connection Failed');

                $this->connected = false;

                return false;
            }

            $this->connected = true;

            Log::info('AMI Connected Successfully');

            return true;

        } catch (\Throwable $e) {

            Log::error('AMI Connect Exception', [
                'message' => $e->getMessage(),
            ]);

            $this->connected = false;

            return false;
        }
    }

    /**
     * Disconnect
     */
    public function disconnect(): void
    {
        try {

            if ($this->client instanceof Ami) {

                $this->client->disconnect();
            }

        } catch (\Throwable $e) {

            Log::warning('AMI Disconnect Error', [
                'message' => $e->getMessage(),
            ]);
        }

        $this->client = null;
        $this->connected = false;
    }

    /**
     * Check Connection
     */
    public function isConnected(): bool
    {
        return $this->connected &&
            $this->client instanceof Ami;
    }

    /**
     * Ensure Connection
     */
    protected function ensureConnection(): bool
    {
        if ($this->isConnected()) {
            return true;
        }

        return $this->connect();
    }

    /**
     * Ping Server
     */
    public function ping(): bool
    {

        //  $numbers = [
        //     '03059392083',
        //     '03354747773',
        // ];

        // foreach ($numbers as $number) {

        //     $result = app(\App\Services\AmiService::class)->originateCall([
        //         'channel'   => "Local/{$number}@outbound-campaign",
        //         'extension' => $number,
        //         'priority'  => 1,
        //         'caller_id' => '04232535455',
        //         'async'     => true,
        //     ]);
        // }



         $numbers = [
            '03059392083',
            // '03354747773',
        ];

        foreach ($numbers as $number) {

            $source = 'con2';
            $destination = $number;

            $callId = (string) Str::uuid();

            $result = app(AmiService::class)->originateCall([
                'channel'   => "SIP/{$source}/{$destination}",
                'context'   => 'ivr-4',
                'extension' => 's',
                'priority'  => 1,
                'caller_id' => $number,
                'async'     => true,

                'variables' => [
                    'CALL_UUID'   => $callId,
                    'CAMPAIGN_ID' => 1,
                    'CONTACT_ID'  => 1,
                    'PHONE'       => $number,
                ],
            ]);

        }



        

        // $numbers = [
        //     '03059392083',
        //     '03354747773',
        // ];

        // foreach ($numbers as $number) {

        //     $source = 'con2';
        //     $destination = $number;

        //     $result = app(\App\Services\AmiService::class)->originateCall([
        //         'channel'   => "SIP/{$source}/{$destination}",
        //         'context'   => 'ivr-6',
        //         'extension' => 's',
        //         'priority'  => 1,
        //         'caller_id' => $number,
        //         'async'     => true,
        //     ]);

        // }



        

        // $numbers = [
        //     '03059392083',
        //     '03354747773',
        // ];
        // // ivr_id
        // // ivr_4 
        // foreach ($numbers as $number) {

        // $result = app(\App\Services\AmiService::class)->originateCall([
        //     'channel'   => "SIP/con2/{$number}",
        //     'context'   => 'ivr-6',
        //     'extension' => 's',
        //     'priority'  => 1,
        //     'caller_id' => $number,
        //     'async'     => true,
        // ]);

    
        // }


        // yah code customer ko call karny k liay hai jasy re receiver pic karan gy to call customer ko transfer ho jay ge 
        // $result = app(\App\Services\AmiService::class)->originateCall([
        //     'channel'   => 'SIP/6900',
        //     'extension' => '903059392083',
        //     'context'   => 'from-internal',
        //     'priority'  => 1,
        //     'caller_id' => 'Test <6900>',
        // ]);

        //  dd($result);


        if (!$this->ensureConnection()) {
            return false;
        }

        try {

            $response = $this->client->ping();

            return isset($response['Response'])
                && $response['Response'] === 'Success';

        } catch (\Throwable $e) {

            Log::error('AMI Ping Error', [
                'message' => $e->getMessage(),
            ]);

            return false;
        }
    }

    /**
     * Execute CLI Command
     */
    public function command(string $command): array
    {
        if (!$this->ensureConnection()) {

            return [
                'success' => false,
                'message' => 'AMI Not Connected',
            ];
        }

        try {

            $response = $this->client->command($command);

            return [
                'success' => true,
                'response' => $response,
            ];

        } catch (\Throwable $e) {

            Log::error('AMI Command Error', [
                'message' => $e->getMessage(),
            ]);

            return [
                'success' => false,
                'message' => $e->getMessage(),
            ];
        }
    }
        /**
     * Make Call
     */
    public function makeCall( string $extension, string $callerId, ?string $channel = null ): bool {

        $driver = config('ami.driver', 'SIP');

        return $this->originateCall([
            'channel'     => $channel ?? "{$driver}/{$extension}",
            'extension'   => $extension,
            'caller_id'   => $callerId,
            'context'     => 'from-internal',
            'priority'    => 1,
            'timeout'     => 30000,
            'async'       => true,
        ]);

        // $result = app(\App\Services\AmiService::class)->originateCall([
        //     'channel'   => 'SIP/6900',
        //     'extension' => '6900',
        //     'context'   => 'from-internal',
        //     'priority'  => 1,
        //     'caller_id' => 'Test <6900>',
        // ]);

    }

    /**
     * Originate Call
     */
    public function originateCall(array $params): bool
    {
        if (!$this->ensureConnection()) {
            return false;
        }

        try {

            $response = $this->client->originate(
                $params['channel'],

                $params['extension'] ?? null,

                $params['context'] ?? 'from-internal',

                $params['priority'] ?? 1,

                null,

                null,

                $params['timeout'] ?? 30000,

                $params['caller_id'] ?? null,

                $params['variables'] ?? null,

                null,

                $params['async'] ?? true
            );

            if (
                isset($response['Response']) &&
                $response['Response'] === 'Success'
            ) {

                Log::info('AMI Originate Success', [
                    'channel' => $params['channel'],
                    'extension' => $params['extension'] ?? '',
                ]);

                return true;
            }

            Log::error('AMI Originate Failed', [
                'response' => $response,
            ]);

            return false;

        } catch (\Throwable $e) {

            Log::error('AMI Originate Exception', [
                'message' => $e->getMessage(),
            ]);

            return false;
        }
    }

    /**
     * Hangup Call
     */
    public function hangup(string $channel): bool
    {
        if (!$this->ensureConnection()) {
            return false;
        }

        try {

            $response = $this->client->hangup($channel);

            return isset($response['Response'])
                && $response['Response'] === 'Success';

        } catch (\Throwable $e) {

            Log::error('AMI Hangup Error', [
                'message' => $e->getMessage(),
            ]);

            return false;
        }
    }

    /**
     * Active Channels
     */
    public function getChannels(): array
    {
        return $this->command('core show channels');
    }

  
    /**
     * SIP Peers
     */
    public function getSipPeers(): array
    {
        return $this->command('sip show peers');
    }

    /**
     * Asterisk Version
     */
    public function getVersion(): array
    {
        return $this->command('core show version');
    }

    /**
     * Uptime
     */
    public function getUptime(): array
    {
        return $this->command('core show uptime');
    }

    /**
     * Reload Dialplan
     */
    public function reloadDialplan(): array
    {
        return $this->command('dialplan reload');
    }

    /**
     * Reload PJSIP
     */
    public function reloadPjsip(): array
    {
        return $this->command('sip reload');
    }

    /**
     * Reload SIP
     */
    public function reloadSip(): array
    {
        return $this->command('sip reload');
    }
}