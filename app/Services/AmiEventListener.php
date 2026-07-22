<?php

namespace App\Services;

use App\Jobs\ProcessCampaignCall;
use App\Models\Campaign;
use App\Models\CampaignContact;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

class AmiEventListener
{
    protected $socket;

    protected array $calls = [];

    protected array $bridgedCalls = [];

    protected array $answeredCalls = [];

    public function connect(): void
    {
        $host = config('ami.host');
        $port = config('ami.port');
        $user = config('ami.username');
        $secret = config('ami.secret');

        Log::info('AMI CONNECTION ATTEMPT', [
            'host' => $host,
            'port' => $port,
            'user' => $user,
        ]);

        $this->socket = fsockopen($host, $port, $errno, $errstr, 10);

        if (! $this->socket) {
            Log::error('AMI CONNECTION FAILED', [
                'errno' => $errno,
                'errstr' => $errstr,
            ]);
            throw new \Exception("AMI Connection Failed: {$errstr}");
        }

        Log::info('AMI SOCKET CONNECTED');

        $login =
            "Action: Login\r\n".
            "Username: {$user}\r\n".
            "Secret: {$secret}\r\n".
            "Events: on\r\n\r\n";

        fwrite($this->socket, $login);

        $loginResponse = '';
        while (! feof($this->socket)) {
            $line = trim(fgets($this->socket));
            $loginResponse .= $line."\n";

            if ($line === '') {
                break;
            }

            Log::info('AMI LOGIN RESPONSE', ['line' => $line]);
        }

        Log::info('AMI LOGIN COMPLETE', ['response' => $loginResponse]);
    }

    public function listen(): void
    {
        $event = [];
        $lineCount = 0;

        Log::info('AMI LISTENER STARTED');

        while (! feof($this->socket)) {
            $line = trim(fgets($this->socket));
            $lineCount++;

            if ($lineCount > 10000) {
                Log::warning('AMI LISTENER LINE LIMIT REACHED');
                break;
            }

            if ($line === '') {

                if (! empty($event)) {
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

        Log::info('AMI LISTENER STOPPED');
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

            case 'DTMF':
                $this->onDtmf($event);
                break;

            case 'Hangup':
                $this->onHangup($event);
                break;
        }
    }

    protected function onVarSet(array $event): void
    {
        $uid = $event['Uniqueid'] ?? null;
        if (!$uid) return;

        if (!isset($this->calls[$uid])) {
            $this->calls[$uid] = [];
        }

        $this->calls[$uid][$event['Variable']] = $event['Value'];

        // Database save only when all important vars exist
        if (isset(
            $this->calls[$uid]['CAMPAIGN_ID'],
            $this->calls[$uid]['CONTACT_ID'],
            $this->calls[$uid]['CALL_UUID'],
            $this->calls[$uid]['PHONE']
        )) {
            $this->saveCallToDatabase($uid);
        }
    }




   protected function onNewChannel(array $event): void
    {
        $uid = $event['Uniqueid'] ?? null;
        if (!$uid || !isset($this->calls[$uid])) return;
        $call = $this->getCall($uid);
        if (!$call) {
            return;
        }


        $this->logCall('CALL CREATED', [
            'campaign_id' => $call['CAMPAIGN_ID'] ?? null,
            'contact_id'  => $call['CONTACT_ID'] ?? null,
            'phone'       => $call['PHONE'] ?? null,
            'uid'         => $uid,
        ]);
    }

    protected function onNewState(array $event): void
    {
        $uid = $event['Uniqueid'] ?? null;
        if (!$uid || !isset($this->calls[$uid])) return;

        $call = $this->getCall($uid);

        if (!$call) {
            return;
        }
        $state = $event['ChannelStateDesc'] ?? '';

        if ($state === 'Ringing') {
            $this->logCall('CALL RINGING', [
                'campaign_id' => $call['CAMPAIGN_ID'] ?? null,
                'contact_id'  => $call['CONTACT_ID'] ?? null,
                'phone'       => $call['PHONE'] ?? null,
                'state'       => $state,
                'uid'         => $uid,
            ]);

            if (!empty($call['CONTACT_ID'])) {
            CampaignContact::where('id', $call['CONTACT_ID'])
                    ->update([
                        'status' => 'calling_ringing'
                    ]);
            }
        }

        if ($state === 'Up') {
            $this->answeredCalls[$uid] = true;

            $this->logCall('CALL ANSWERED', [
                'campaign_id' => $call['CAMPAIGN_ID'] ?? null,
                'contact_id'  => $call['CONTACT_ID'] ?? null,
                'phone'       => $call['PHONE'] ?? null,
                'state'       => $state,
                'uid'         => $uid,
            ]);

            if (!empty($call['CONTACT_ID'])) {
            CampaignContact::where('id', $call['CONTACT_ID'])
                ->update([
                    'status' => 'attended'
                ]);
            }


        }
    }

    protected function onDialBegin(array $event): void
    {
        Log::info('DIAL BEGIN', $event);
    }

    protected function onDialEnd(array $event): void
    {
        Log::info('DIAL END', $event);
    }

    protected function onBridgeEnter(array $event): void
    {
        $uid = $event['Uniqueid'] ?? null;

        if ($uid && isset($this->calls[$uid])) {
            $this->bridgedCalls[$uid] = true;

            $contactId = $this->calls[$uid]['CONTACT_ID'] ?? null;

            if ($contactId) {
                $contact = CampaignContact::find($contactId);
                if ($contact && in_array($contact->status, ['calling', 'pending', 'attended'])) {
                    $contact->update(['status' => 'success']);
                }
            }
        }

        Log::info('BRIDGE ENTER => onBridgeEnter', $event);
    }

    protected function onBridgeLeave(array $event): void
    {
        // Log::info('BRIDGE LEAVE', $event);
    }

    protected function onChannelRedirect(array $event): void
    {
        // Log::info('CHANNEL REDIRECT', $event);
    }

    protected function onDtmf(array $event): void
    {
        $uid = $event['Uniqueid'] ?? null;
        $digit = $event['Digit'] ?? null;

        if (!$uid || !$digit || !isset($this->calls[$uid])) return;
        $call = $this->getCall($uid);

        if (!$call) {
            return;
        }


        $this->logCall('DTMF RECEIVED', [
            'campaign_id' => $call['CAMPAIGN_ID'] ?? null,
            'contact_id'  => $call['CONTACT_ID'] ?? null,
            'phone'       => $call['PHONE'] ?? null,
            'digit'       => $digit,
            'uid'         => $uid,
        ]);

        if ($digit === '1') {
            if (!empty($call['CONTACT_ID'])) {
                CampaignContact::where('id', $call['CONTACT_ID'])
                    ->update([
                        'status' => '1_pressed'
                    ]);
            }
        }
    }

    protected function onHangup(array $event): void
    {
        $uid = $event['Uniqueid'] ?? null;
        if (!$uid || !isset($this->calls[$uid])) return;

        $call = $this->getCall($uid);
        if (!$call) {
            unset(
                $this->calls[$uid],
                $this->answeredCalls[$uid],
                $this->bridgedCalls[$uid]
            );
            return;
        }


        $cause = $event['Cause'] ?? null;
        $causeTxt = $event['Cause-txt'] ?? null;

        $status = 'failed';

        if (isset($this->answeredCalls[$uid])) {
            $status = 'successful';
        } elseif (in_array($cause, ['17'])) {
            $status = 'busy';
        } elseif (in_array($cause, ['18', '19', '20'])) {
            $status = 'not_answered';
        }

        if (!empty($call['CONTACT_ID'])) {
            CampaignContact::where('id', $call['CONTACT_ID'])
                ->update([
                    'status' => $status
                ]);
        }

        $this->logCall('CALL FINISHED', [
            'campaign_id' => $call['CAMPAIGN_ID'] ?? null,
            'contact_id'  => $call['CONTACT_ID'] ?? null,
            'phone'       => $call['PHONE'] ?? null,
            'cause'       => $cause,
            'cause_txt'   => $causeTxt,
            'state'       => $status,
            'uid'         => $uid,
        ]);

        unset($this->calls[$uid], $this->answeredCalls[$uid]);
    }

    protected function processNextInQueue(int $campaignId): void
    {
        $campaign = Campaign::find($campaignId);
        if (! $campaign) {
            return;
        }

        $activeCalls = Cache::get("campaign_active_calls_{$campaignId}", 0);

        if ($activeCalls >= $campaign->no_of_calls) {
            return;
        }

        $nextContact = CampaignContact::where('campaign_id', $campaignId)
            ->where('status', 'queued')
            ->orderBy('id')
            ->first();

        if ($nextContact) {
            $job = new ProcessCampaignCall($nextContact->id, $campaignId);
            $job->handle();

            return;
        }

        $pendingContacts = CampaignContact::where('campaign_id', $campaignId)
            ->where('status', 'pending')
            ->orderBy('id')
            ->limit($campaign->no_of_calls - $activeCalls)
            ->get();

        foreach ($pendingContacts as $contact) {
            $job = new ProcessCampaignCall($contact->id, $campaignId);
            $job->handle();
        }
    }

    protected function isSuccessfulCall(?string $cause, ?string $causeTxt, bool $isBridged, bool $isAnswered): bool
    {
        $successfulCauses = ['16', '17', '18', '19', '20', '21', '22', '24', '25', '26', '27', '28', '29', '31', '34', '38', '39', '41', '42', '43', '44', '45', '46', '47', '48', '49', '50', '51', '52', '53', '54', '55', '56', '57', '58', '59', '60', '61', '62', '63', '64', '65', '66', '67', '68', '69', '70', '71', '72', '73', '74', '75', '76', '77', '78', '79', '80', '81', '82', '83', '84', '85', '86', '87', '88', '89', '90', '91', '92', '93', '94', '95', '96', '97', '98', '99', '100', '101', '102', '103', '104', '105', '106', '107', '108', '109', '110', '111', '112', '113', '114', '115', '116', '117', '118', '119', '120', '121', '122', '123', '124', '125', '126', '127', '128', '129', '130', '131', '132', '133', '134', '135', '136', '137', '138', '139', '140', '141', '142', '143', '144', '145', '146', '147', '148', '149', '150', '151', '152', '153', '154', '155', '156', '157', '158', '159', '160', '161', '162', '163', '164', '165', '166', '167', '168', '169', '170', '171', '172', '173', '174', '175', '176', '177', '178', '179', '180', '181', '182', '183', '184', '185', '186', '187', '188', '189', '190', '191', '192', '193', '194', '195', '196', '197', '198', '199', '200'];

        $failedCauses = ['0', '1', '2', '3', '4', '5', '6', '7', '8', '9', '10', '11', '12', '13', '14', '15', '30', '32', '33', '35', '36', '37', '40'];

        if (in_array($cause, $failedCauses)) {
            return false;
        }

        if (in_array($cause, $successfulCauses)) {
            if ($cause === '16') {
                return $isBridged;
            }
            return true;
        }

        if (stripos($causeTxt, 'normal') !== false || stripos($causeTxt, 'completed') !== false) {
            return $isBridged;
        }

        return false;
    }

    protected function saveCallToDatabase(string $uid): void
    {
        if (! isset($this->calls[$uid])) {
            return;
        }

        $contactId = $this->calls[$uid]['CONTACT_ID'] ?? null;
        $callUuid = $this->calls[$uid]['CALL_UUID'] ?? null;

        if ($contactId && $callUuid) {
            CampaignContact::where('id', $contactId)
                ->update([
                    'call_uuid' => $callUuid,
                    'channel' => $this->calls[$uid]['channel'] ?? null,
                ]);
        }
    }

    public function updateStaleCalls(): void
    {
        $staleContacts = CampaignContact::where('status', 'calling_ringing')
            ->where('called_at', '<', now()->subMinutes(2))
            ->where('called_at', '>', now()->subMinutes(10))
            ->get();

        foreach ($staleContacts as $contact) {
            $contact->update(['status' => 'not_answered']);
            if ($contact->campaign) {
                $contact->campaign->increment('failed_calls');
                Cache::decrement("campaign_active_calls_{$contact->campaign_id}", 1);

                $campaign = $contact->campaign;
                $activeCalls = Cache::get("campaign_active_calls_{$campaign->id}", 0);

                if ($activeCalls >= $campaign->no_of_calls) {
                    return;
                }

                $nextContact = CampaignContact::where('campaign_id', $campaign->id)
                    ->where('status', 'queued')
                    ->orderBy('id')
                    ->first();

                if ($nextContact) {
                    $job = new ProcessCampaignCall($nextContact->id, $campaign->id);
                    $job->handle();
                }
            }
        }
    }

    public function disconnect(): void
    {
        if ($this->socket) {
            fwrite($this->socket, "Action: Logoff\r\n\r\n");
            fclose($this->socket);
        }
    }

    protected function logCall(string $stage, array $data = []): void
    {
        Log::info($stage, [
            'campaign_id' => $data['campaign_id'] ?? null,
            'contact_id'  => $data['contact_id'] ?? null,
            'phone'       => $data['phone'] ?? null,
            'state'       => $data['state'] ?? null,
            'cause'       => $data['cause'] ?? null,
            'cause_txt'   => $data['cause_txt'] ?? null,
            'digit'       => $data['digit'] ?? null,
            'uid'         => $data['uid'] ?? null,
        ]);
    }


    protected function getCall(string $uid): ?array
    {
        if (!isset($this->calls[$uid])) {
            return null;
        }

        $call = $this->calls[$uid];

        if (
            empty($call['CONTACT_ID']) ||
            empty($call['CAMPAIGN_ID'])
        ) {
            return null;
        }

        return $call;
    }

}