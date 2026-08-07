<?php

namespace App\Console\Commands;

use App\Jobs\ProcessCampaignCall;
use App\Models\Campaign;
use App\Models\CampaignContact;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

#[Signature('campaign:process-queue')]
#[Description('Process campaign call queue')]
class ProcessCampaignQueue extends Command
{
    public function handle(): int
    {
        $campaigns = Campaign::where('status', 'in_progress')->get();

        foreach ($campaigns as $campaign) {
            $this->processCampaign($campaign);
        }

        return self::SUCCESS;
    }

    protected function processCampaign(Campaign $campaign): void
    {
        $specialStatuses = ['calling', 'called', 'calling_ringing', 'attended', '1_pressed'];

        $activeContactCount = CampaignContact::where('campaign_id', $campaign->id)
            ->whereIn('status', $specialStatuses)
            ->count();

        // Log::info('PROCESS_CAMPAIGN_QUEUE', [
        //     'campaign_id' => $campaign->id,
        //     'active_contacts' => $activeContactCount,
        //     'no_of_calls' => $campaign->no_of_calls,
        // ]);

        if ($activeContactCount >= $campaign->no_of_calls) {
            return;
        }

        $availableSlots = $campaign->no_of_calls - $activeContactCount;

        $queuedContacts = CampaignContact::where('campaign_id', $campaign->id)
            ->where('status', 'queued')
            ->orderBy('id')
            ->limit($availableSlots)
            ->get();

        foreach ($queuedContacts as $contact) {
            $job = new ProcessCampaignCall($contact->id, $campaign->id);
            $job->handle();
        }

        if ($queuedContacts->isEmpty() && $activeContactCount < $campaign->no_of_calls) {
            $pendingContacts = CampaignContact::where('campaign_id', $campaign->id)
                ->where('status', 'pending')
                ->orderBy('id')
                ->limit($availableSlots)
                ->get();

            foreach ($pendingContacts as $contact) {
                $job = new ProcessCampaignCall($contact->id, $campaign->id);
                $job->handle();
            }
        }

        $this->checkStaleCalls($campaign);
        $this->checkCompletion($campaign);
    }

    protected function checkStaleCalls(Campaign $campaign): void
    {
        $staleContacts = CampaignContact::where('campaign_id', $campaign->id)
            ->whereIn('status', ['calling', 'calling_ringing'])
            ->where('called_at', '<', now()->subMinutes(2))
            ->get();

        foreach ($staleContacts as $contact) {
            $contact->update(['status' => 'not_answered']);
            $campaign->increment('failed_calls');
            Cache::decrement("campaign_active_calls_{$campaign->id}", 1);

            $specialStatuses = ['calling', 'called', 'calling_ringing', 'attended', '1_pressed'];

            $activeContactCount = CampaignContact::where('campaign_id', $campaign->id)
                ->whereIn('status', $specialStatuses)
                ->count();

            if ($activeContactCount >= $campaign->no_of_calls) {
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

    protected function checkCompletion(Campaign $campaign): void
    {
        $completedCount = CampaignContact::where('campaign_id', $campaign->id)
            ->whereIn('status', ['success', 'successful', 'rejected', 'not_answered', 'failed', 'skipped', 'busy', 'completed'])
            ->count();

        if ($completedCount >= $campaign->total_contacts && $campaign->total_contacts > 0) {
            $campaign->update([
                'status' => 'completed',
                'completed_at' => now(),
            ]);
        }
    }
}
