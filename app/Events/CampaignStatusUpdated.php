<?php

namespace App\Events;

use App\Models\Campaign;
use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class CampaignStatusUpdated implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public int $campaignId;

    public function __construct(Campaign $campaign)
    {
        $this->campaignId = $campaign->id;
    }

    public function broadcastOn(): array
    {
        return [
            new Channel('campaigns'),
        ];
    }

    public function broadcastWith(): array
    {
        $campaign = Campaign::with(['contacts'])->find($this->campaignId);
        
        if (!$campaign) {
            return [];
        }

        $statusCounts = $campaign->contacts->groupBy('status')->map->count();
        
        return [
            'campaign' => [
                'id' => $campaign->id,
                'name' => $campaign->name,
                'status' => $campaign->status,
                'total_contacts' => $campaign->total_contacts,
                'started_at' => $campaign->started_at,
                'completed_at' => $campaign->completed_at,
                'status_counts' => [
                    'pending' => $statusCounts->get('pending', 0),
                    'calling_ringing' => $statusCounts->get('calling_ringing', 0) + $statusCounts->get('calling', 0),
                    'attended' => $statusCounts->get('attended', 0),
                    'failed' => $statusCounts->get('failed', 0) + $statusCounts->get('busy', 0) + $statusCounts->get('not_answered', 0),
                    '1_pressed' => $statusCounts->get('1_pressed', 0),
                    'successful' => $statusCounts->get('successful', 0),
                ],
            ],
        ];
    }
}