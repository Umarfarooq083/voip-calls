<?php

namespace App\Console\Commands;

use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

#[Signature('app:ami-listen')]
#[Description('Command description')]
class AmiListen extends Command
{
    protected array $calls = [];

    public function handle()
    {
        $listener = app(\App\Services\AmiEventListener::class);

        $listener->connect();

        $this->info('AMI Listener Started');

        $listener->listen();

        return self::SUCCESS;
    }

    protected function handleEvent(array $event): void
    {
        if (!isset($event['Event'])) {
            return;
        }

        switch ($event['Event']) {

            case 'VarSet':
                $this->onVarSet($event);
                break;

            case 'Newchannel':
                $this->onNewChannel($event);
                break;

            case 'Newstate':
                $this->onNewState($event);
                break;

            case 'DialBegin':
                $this->onDialBegin($event);
                break;

            case 'DialEnd':
                $this->onDialEnd($event);
                break;

            case 'BridgeEnter':
                $this->onBridgeEnter($event);
                break;

            case 'BridgeLeave':
                $this->onBridgeLeave($event);
                break;

            case 'Hangup':
                $this->onHangup($event);
                break;
        }
    }

    protected function onVarSet(array $event): void
    {
        $uid = $event['Uniqueid'] ?? null;

        if (!$uid) {
            return;
        }

        if (!isset($this->calls[$uid])) {
            $this->calls[$uid] = [];
        }

        $this->calls[$uid][$event['Variable']] = $event['Value'];

        Log::info('CALL MEMORY', [
            'uid' => $uid,
            'data' => $this->calls[$uid],
        ]);
    }

    protected function onNewChannel(array $event)
    {
        Log::info('NEW CHANNEL', $event);
    }

    protected function onNewState(array $event): void
    {
        $uid = $event['Uniqueid'];

        $campaign = $this->calls[$uid]['CAMPAIGN_ID'] ?? null;

        $contact = $this->calls[$uid]['CONTACT_ID'] ?? null;

        Log::info('CALL STATE', [

            'campaign_id' => $campaign,

            'contact_id' => $contact,

            'state' => $event['ChannelStateDesc'],

            'channel' => $event['Channel'],

            'uid' => $uid,

        ]);
    }

    protected function onDialBegin(array $event)
    {
        Log::info('DIAL BEGIN', $event);
    }

    protected function onDialEnd(array $event)
    {
        Log::info('DIAL END', $event);
    }

    protected function onBridgeEnter(array $event)
    {
        Log::info('BRIDGE ENTER', $event);
    }

    protected function onBridgeLeave(array $event)
    {
        Log::info('BRIDGE LEAVE', $event);
    }

    protected function onHangup(array $event): void
    {
        $uid = $event['Uniqueid'];

        Log::info('CALL FINISHED', [

            'campaign_id' => $this->calls[$uid]['CAMPAIGN_ID'] ?? null,

            'contact_id' => $this->calls[$uid]['CONTACT_ID'] ?? null,

            'phone' => $this->calls[$uid]['PHONE'] ?? null,

            'cause' => $event['Cause'] ?? null,

            'cause_txt' => $event['Cause-txt'] ?? null,

        ]);

        unset($this->calls[$uid]);
    }
}
