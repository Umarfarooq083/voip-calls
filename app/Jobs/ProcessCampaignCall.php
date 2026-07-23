<?php

namespace App\Jobs;

use App\Models\Campaign;
use App\Models\CampaignContact;
use App\Services\AmiService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class ProcessCampaignCall implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $contactId;

    public int $campaignId;

    public function __construct(int $contactId, int $campaignId)
    {
        $this->contactId = $contactId;
        $this->campaignId = $campaignId;
        $this->onQueue = 'calls';
    }

    public function handle(): void
    {
        $campaign = Campaign::find($this->campaignId);
        $contact = CampaignContact::find($this->contactId);

        if (! $campaign || ! $contact) {
            return;
        }

        // Log::info('PROCESSING CAMPAIGN CALL JOB', [
        //     'contact_id' => $contact->id,
        //     'campaign_id' => $campaign->id,
        //     'contact_status' => $contact->status,
        //     'active_calls' => Cache::get("campaign_active_calls_{$campaign->id}", 0),
        // ]);

        if (in_array($contact->status, ['called', 'calling_ringing', 'attended', '1_pressed', 'rejected', 'not_answered', 'success', 'successful', 'failed', 'skipped'])) {
            return;
        }

        if ($campaign->status !== 'in_progress') {
            return;
        }

        $activeCalls = Cache::get("campaign_active_calls_{$campaign->id}", 0);

        if ($activeCalls >= $campaign->no_of_calls) {
            $contact->update(['status' => 'queued']);

            return;
        }

        $contact->update([
            'status' => 'calling',
            'called_at' => now(),
        ]);

        Cache::put("campaign_active_calls_{$campaign->id}", $activeCalls + 1, 300);
        $campaign->increment('called_contacts');

        $callUuid = (string) Str::uuid();
        $source = $campaign->trunk_channalId;
        $destination = '0'.$contact->phone_number;

        $contact->update([
            'call_uuid' => $callUuid,
            'channel' => "SIP/{$source}/{$destination}",
        ]);

        $result = app(AmiService::class)->originateCall([
            'channel' => "SIP/{$source}/{$destination}",
            'context' => 'ivr-'.$campaign->ivr_id,
            'extension' => 's',
            'priority' => 1,
            'caller_id' => $contact->phone_number,
            'async' => true,
            'variables' => [
                'CALL_UUID' => $callUuid,
                'CAMPAIGN_ID' => $campaign->id,
                'CONTACT_ID' => $contact->id,
                'PHONE' => $destination,
            ],
        ]);

        // Log::info('AMI Result', [
        //     'number' => $destination,
        //     'response' => $result,
        //     'contact_id' => $contact->id,
        //     'campaign_id' => $campaign->id,
        //     'call_uuid' => $callUuid,
        // ]);

        if (! $result) {
            Cache::put("campaign_active_calls_{$campaign->id}", max(0, $activeCalls - 1), 300);
            $contact->update(['status' => 'failed']);
            $campaign->increment('failed_calls');

            $this->processNextInQueue($campaign);
        }
    }

    public function processNextInQueue(Campaign $campaign): void
    {
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

            return;
        }else{
            $campaign->update(['status' => 'completed']);
        }

        $pendingContacts = CampaignContact::where('campaign_id', $campaign->id)
            ->where('status', 'pending')
            ->orderBy('id')
            ->limit($campaign->no_of_calls - $activeCalls)
            ->get();

        foreach ($pendingContacts as $contact) {
            $job = new ProcessCampaignCall($contact->id, $campaign->id);
            $job->handle();
        }

        $this->checkCompletion($campaign);
    }

    protected function checkCompletion(Campaign $campaign): void
    {
        $completedCount = CampaignContact::where('campaign_id', $campaign->id)
            ->whereIn('status', ['success', 'successful', 'rejected', 'not_answered', 'failed', 'skipped'])
            ->count();

        if ($completedCount >= $campaign->total_contacts && $campaign->total_contacts > 0) {
            $campaign->update([
                'status' => 'completed',
                'completed_at' => now(),
            ]);
        }
    }
}
