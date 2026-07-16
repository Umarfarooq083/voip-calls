<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreCampaignRequest;
use App\Http\Requests\UpdateCampaignRequest;
use App\Models\Campaign;
use App\Models\CampaignContact;
use App\Services\CampaignService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Redirect;
use Inertia\Inertia;

class CampaignController extends Controller
{
    public function __construct()
    {
    }

    public function index(Request $request)
    {
        $filters = $request->only(['status', 'search']);

        $campaigns = app(CampaignService::class)->list($filters);

        return Inertia::render('Campaigns/Index', [
            'campaigns' => $campaigns->items(),
            'filters' => $filters,
            'meta' => [
                'current_page' => $campaigns->currentPage(),
                'last_page' => $campaigns->lastPage(),
                'per_page' => $campaigns->perPage(),
                'total' => $campaigns->total(),
            ],
            'links' => [
                'first' => $campaigns->url(1),
                'last' => $campaigns->url($campaigns->lastPage()),
                'prev' => $campaigns->url($campaigns->currentPage() - 1),
                'next' => $campaigns->url($campaigns->currentPage() + 1),
            ],
            'success' => session('success'),
        ]);
    }

    public function create()
    {
        $voiceMessages = app(CampaignService::class)->getVoiceMessages();
        $extensions = app(CampaignService::class)->getExtensions();

        return Inertia::render('Campaigns/Create', [
            'voiceMessages' => $voiceMessages,
            'extensions' => $extensions,
        ]);
    }

    public function store(StoreCampaignRequest $request): RedirectResponse
    {
        app(CampaignService::class)->create($request->validated());

        return Redirect::route('campaigns.index')
            ->with('success', 'Campaign created successfully.');
    }

    public function show(Campaign $campaign)
    {
        $campaign->load(['extension', 'voiceMessage', 'contacts']);

        return Inertia::render('Campaigns/Show', [
            'campaign' => [
                'id' => $campaign->id,
                'name' => $campaign->name,
                'extension' => $campaign->extension,
                'voice_message' => $campaign->voiceMessage,
                'status' => $campaign->status,
                'total_contacts' => $campaign->total_contacts,
                'called_contacts' => $campaign->called_contacts,
                'successful_calls' => $campaign->successful_calls,
                'failed_calls' => $campaign->failed_calls,
                'notes' => $campaign->notes,
                'started_at' => $campaign->started_at,
                'completed_at' => $campaign->completed_at,
            ],
            'contacts' => $campaign->contacts,
            'success' => session('success'),
        ]);
    }

    public function edit(Campaign $campaign)
    {
        $voiceMessages = app(CampaignService::class)->getVoiceMessages();
        $extensions = app(CampaignService::class)->getExtensions();

        return Inertia::render('Campaigns/Edit', [
            'campaign' => [
                'id' => $campaign->id,
                'name' => $campaign->name,
                'extension_id' => $campaign->extension_id,
                'voice_message_id' => $campaign->voice_message_id,
                'notes' => $campaign->notes,
            ],
            'voiceMessages' => $voiceMessages,
            'extensions' => $extensions,
        ]);
    }

    public function update(UpdateCampaignRequest $request, Campaign $campaign): RedirectResponse
    {
        app(CampaignService::class)->update($campaign, $request->validated());

        return Redirect::route('campaigns.index')
            ->with('success', 'Campaign updated successfully.');
    }

    public function destroy(Campaign $campaign): RedirectResponse
    {
        app(CampaignService::class)->delete($campaign);

        return Redirect::route('campaigns.index')
            ->with('success', 'Campaign deleted successfully.');
    }

    public function destroyContact(CampaignContact $contact): RedirectResponse
    {
        $campaignId = $contact->campaign_id;
        $contact->delete();

        return Redirect::route('campaigns.show', $campaignId)
            ->with('success', 'Contact deleted successfully.');
    }
}