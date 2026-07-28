<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreCampaignRequest;
use App\Http\Requests\UpdateCampaignRequest;
use App\Jobs\ProcessCampaignCall;
use App\Models\Campaign;
use App\Models\CampaignContact;
use App\Services\CampaignService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Redirect;
use Inertia\Inertia;

class CampaignController extends Controller
{
    public function __construct() {}

    public function index(Request $request)
    {
        $filters = $request->only(['status', 'search']);

        $campaigns = app(CampaignService::class)->list($filters);
        $inProgressCampaignsWithCounts = Campaign::where('status', 'in_progress')
            ->with(['extension', 'contacts'])
            ->get()
            ->map(function ($campaign) {
                $statusCounts = $campaign->contacts->groupBy('status')->map->count();
                return [
                    'id' => $campaign->id,
                    'name' => $campaign->name,
                    'status' => $campaign->status,
                    'total_contacts' => $campaign->total_contacts,
                    'started_at' => $campaign->started_at,
                    'status_counts' => [
                        'pending' => $statusCounts->get('pending', 0),
                        'calling_ringing' => $statusCounts->get('calling_ringing', 0),
                        'attended' => $statusCounts->get('attended', 0),
                        'failed' => $statusCounts->get('failed', 0) + $statusCounts->get('busy', 0) + $statusCounts->get('not_answered', 0),
                        '1_pressed' => $statusCounts->get('1_pressed', 0),
                        'successful' => $statusCounts->get('successful', 0),
                    ],
                ];
            });
            // 'pending','calling','called','calling_ringing','rejected','not_answered','attended','1_pressed','success','successful','failed','skipped','busy'
        // dd($inProgressCampaignsWithCounts);
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
            'inProgressCampaigns' => $inProgressCampaignsWithCounts,
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
        $campaign->load(['contacts']);
        $statusCounts = $campaign->contacts->groupBy('status')->map->count();

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
            'statusCounts' => [
                'pending' => $statusCounts->get('pending', 0),
                'calling' => $statusCounts->get('calling', 0),
                'ringing' => $statusCounts->get('calling_ringing', 0),
                'failed' => $statusCounts->get('failed', 0),
                'dtmf_status' => $campaign['contacts']->where('dtmf_status', 1)->count(),
                'success' => $statusCounts->get('success', 0) + $statusCounts->get('successful', 0),
                'busy' => $statusCounts->get('busy', 0),
                'not_answered' => $statusCounts->get('not_answered', 0),
            ],
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
        $campaign->update([
            'status' => 'in_progress',
            'started_at' => now(),
        ]);

        Cache::put("campaign_active_calls_{$campaign->id}", 0, 300);

        $pendingContacts = CampaignContact::where('campaign_id', $campaign->id)
            ->where('status', 'pending')
            ->orderBy('id')
            ->limit($campaign->no_of_calls)
            ->get();

        foreach ($pendingContacts as $contact) {
            ProcessCampaignCall::dispatch($contact->id, $campaign->id);
        }

        return Redirect::route('campaigns.index')
            ->with('success', 'Campaign started successfully. Calls are being processed.');
    }

    public function retryFailedCalls(Campaign $campaign)
    {
        $retryStatuses = ['failed', 'busy', 'not_answered'];
        CampaignContact::where('campaign_id', $campaign->id)
            ->whereIn('status', $retryStatuses)->update(['status'=>'pending']);
        // dd($updateStatusToPending);
        // $retryContacts = CampaignContact::where('campaign_id', $campaign->id)
        //     ->whereIn('status', $retryStatuses)
        //     ->orderBy('id')
        //     ->limit($campaign->no_of_calls)
        //     ->get();

        $retryContacts = CampaignContact::where('campaign_id', $campaign->id)
            ->where('status', 'pending')
            ->orderBy('id')
            ->limit($campaign->no_of_calls)
            ->get();


         $campaign->update([
            'status' => 'in_progress'
         ]);
        foreach ($retryContacts as $contact) {
            $contact->update(['status' => 'pending']);
            ProcessCampaignCall::dispatch($contact->id, $campaign->id);
        }

        return Redirect::route('campaigns.index', $campaign->id)
            ->with('success', 'Retry initiated for ' . $retryContacts->count() . ' contacts.');
    }
}
