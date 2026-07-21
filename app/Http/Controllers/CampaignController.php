<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreCampaignRequest;
use App\Http\Requests\UpdateCampaignRequest;
use App\Models\Campaign;
use App\Models\CampaignContact;
use App\Services\AmiService;
use App\Services\CampaignService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Redirect;
use Inertia\Inertia;
use Illuminate\Support\Str;

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
                'prev' => $campaigns->currentPage() > 1 ? $campaigns->url($campaigns->currentPage() - 1) : null,
                'next' => $campaigns->currentPage() < $campaigns->lastPage() ? $campaigns->url($campaigns->currentPage() + 1) : null,
            ],
            'success' => session('success'),
        ]);
    }

    public function create()
    {
        $extensions = app(CampaignService::class)->getExtensions();
        $ivrs = DB::connection('asterisk')
        ->table('ivr_details')
        ->get();

        $trunks = DB::connection('asterisk')
        ->table('trunks')
        ->get();

        return Inertia::render('Campaigns/Create', [
            'extensions' => $extensions,
            'ivrs' => $ivrs,
            'trunks' => $trunks,
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
        $ivrs = DB::connection('asterisk')
        ->table('ivr_details')
        ->get();

        //  dd($campaign->trunk_channalId);
        $trunks = DB::connection('asterisk')
        ->table('trunks')
        ->get();

        // dd($ivrs);
        return Inertia::render('Campaigns/Edit', [
            'campaign' => [
                'id' => $campaign->id,
                'name' => $campaign->name,
                'no_of_calls' => $campaign->no_of_calls,
                'ivr_id' => $campaign->ivr_id,
                'ivr_name' => $campaign->ivr_name,
                'ivrs' => $ivrs,
                'trunks' => $trunks,
                'trunk_channalId' => $campaign->trunk_channalId,
                'notes' => $campaign->notes,
            ],
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

    public function startCalling(Campaign $campaign)
    {
        $CampaignContact = CampaignContact::where('campaign_id', $campaign->id)->get();
        foreach ($CampaignContact as $number) {
            $source = $campaign->trunk_channalId;
            $destination = '0'.$number->phone_number;     
            $callId = (string) Str::uuid();
            $result = app(AmiService::class)->originateCall([
                'channel'   => "SIP/{$source}/{$destination}",
                'context'   => 'ivr-' . $campaign->ivr_id,
                'extension' => 's',
                'priority'  => 1,
                'caller_id' => $number->phone_number,
                'async'     => true,
                'variables' => [
                    'CALL_UUID'   => $callId,
                    'CAMPAIGN_ID' => $campaign->id,
                    'CONTACT_ID'  => $number->id,
                    'PHONE'       => $destination,
                ],
            ]);

        Log::info("AMI Result", [
            'number' => $destination,
            'response' => $result
        ]);
    
        }
   
        // return Redirect::route('campaigns.index')->with('success', 'Campaign started successfully.');
    }
}