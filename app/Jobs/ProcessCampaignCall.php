<?php

namespace App\Jobs;

use App\Events\CampaignStatusUpdated;
use App\Events\ContactStatusUpdated;
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

        if (in_array($contact->status, ['called', 'calling', 'calling_ringing', 'attended', '1_pressed', 'rejected', 'not_answered', 'success', 'successful', 'failed', 'skipped', 'busy', 'queued'])) {
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

        event(new ContactStatusUpdated($contact));

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

        if (! $result) {
            Cache::put("campaign_active_calls_{$campaign->id}", max(0, $activeCalls - 1), 300);
            $contact->update(['status' => 'failed']);
            event(new ContactStatusUpdated($contact));
            $campaign->increment('failed_calls');

            $this->processNextInQueue($campaign);
        }
    }

    protected function processNextInQueue(Campaign $campaign): void
    {
        $activeCalls = Cache::get("campaign_active_calls_{$campaign->id}", 0);

        if ($activeCalls >= $campaign->no_of_calls) {
            $this->checkCompletion($campaign);
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
            ->whereIn('status', ['success', 'successful', 'rejected', 'not_answered', 'failed', 'skipped', 'busy'])
            ->count();

        if ($completedCount >= $campaign->total_contacts && $campaign->total_contacts > 0) {
            $campaign->update([
                'status' => 'completed',
                'completed_at' => now(),
            ]);

            event(new CampaignStatusUpdated($campaign));
        }
    }

    public static function handleContactCompletion(CampaignContact $contact): void
    {
        $campaign = $contact->campaign;
        if (! $campaign || $campaign->status !== 'in_progress') {
            return;
        }

        Cache::decrement("campaign_active_calls_{$campaign->id}", 1);

        if ($contact->isSubContact()) {
            $mainContact = $contact->parentContact;
            if ($mainContact && $mainContact->status !== 'completed') {
                $subContactsCompleted = $mainContact->subContacts()
                    ->whereIn('status', ['success', 'successful', 'rejected', 'not_answered', 'failed', 'skipped', 'busy'])
                    ->count();

                $totalSubContacts = $mainContact->subContacts()->count();

                if ($subContactsCompleted >= $totalSubContacts && $totalSubContacts > 0) {
                    $mainContact->update(['status' => 'completed']);
                    event(new CampaignStatusUpdated($mainContact->campaign));
                }
            }
        }

        $activeCalls = Cache::get("campaign_active_calls_{$campaign->id}", 0);

        if ($activeCalls >= $campaign->no_of_calls) {
            self::checkCampaignCompletion($campaign);
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

        self::checkCampaignCompletion($campaign);
    }

    protected static function checkCampaignCompletion(Campaign $campaign): void
    {
        if ($campaign->status !== 'in_progress') {
            return;
        }

        $completedCount = CampaignContact::where('campaign_id', $campaign->id)
            ->whereIn('status', ['success', 'successful', 'rejected', 'not_answered', 'failed', 'skipped', 'busy', 'completed'])
            ->count();

        if ($completedCount >= $campaign->total_contacts && $campaign->total_contacts > 0) {
            $campaign->update([
                'status' => 'completed',
                'completed_at' => now(),
            ]);

            event(new CampaignStatusUpdated($campaign));
        }
    }
}
