<?php

namespace App\Http\Controllers;

use App\Events\CampaignStatusUpdated;
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
            ->with(['contacts'])
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
                        'calling_ringing' => $statusCounts->get('calling_ringing', 0) + $statusCounts->get('calling', 0),
                        'attended' => $statusCounts->get('attended', 0),
                        'failed' => $statusCounts->get('failed', 0) + $statusCounts->get('busy', 0) + $statusCounts->get('not_answered', 0),
                        '1_pressed' => $statusCounts->get('1_pressed', 0),
                        'successful' => $statusCounts->get('successful', 0),
                    ],
                ];
            });

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

    public function show(Campaign $campaign, Request $request)
    {
        $filters = $request->only(['status', 'search']);

        $contacts = CampaignContact::where('campaign_id', $campaign->id)
            ->when($filters['status'] ?? null, function ($query, $status) {
                return $query->where('status', $status);
            })
            ->when($filters['search'] ?? null, function ($query, $search) {
                return $query->where(function ($q) use ($search) {
                    $q->where('customer_name', 'like', "%{$search}%")
                      ->orWhere('phone_number', 'like', "%{$search}%");
                });
            })
            ->orderBy('id')
            ->paginate(25)
            ->withQueryString();

        $statusCounts = CampaignContact::where('campaign_id', $campaign->id)
            ->selectRaw('status, COUNT(*) as total')
            ->groupBy('status')
            ->pluck('total', 'status');

        $dtmfCount = CampaignContact::where('campaign_id', $campaign->id)
            ->where('dtmf_status', 1)
            ->count();

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

            'contacts' => $contacts->items(),

            'filters' => $filters,

            'meta' => [
                'current_page' => $contacts->currentPage(),
                'last_page' => $contacts->lastPage(),
                'per_page' => $contacts->perPage(),
                'total' => $contacts->total(),
            ],

            'links' => $contacts->linkCollection(),

            'statusCounts' => [
                'pending' => $statusCounts['pending'] ?? 0,
                'calling' => $statusCounts['calling'] ?? 0,
                'ringing' => $statusCounts['calling_ringing'] ?? 0,
                'failed' => $statusCounts['failed'] ?? 0,
                'dtmf_status' => $dtmfCount,
                'success' => ($statusCounts['success'] ?? 0) + ($statusCounts['successful'] ?? 0),
                'busy' => $statusCounts['busy'] ?? 0,
                'not_answered' => $statusCounts['not_answered'] ?? 0,
            ],

            'success' => session('success'),
        ]);
    }

    public function edit(Campaign $campaign)
    {
        $ivrs = DB::connection('asterisk')
            ->table('ivr_details')
            ->get();

        $trunks = DB::connection('asterisk')
            ->table('trunks')
            ->get();

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

        $specialStatuses = ['calling', 'called', 'calling_ringing', 'attended', '1_pressed'];

        $activeContactCount = CampaignContact::where('campaign_id', $campaign->id)
            ->whereIn('status', $specialStatuses)
            ->count();

        Cache::put("campaign_active_calls_{$campaign->id}", $activeContactCount, 300);

        $availableSlots = max(0, $campaign->no_of_calls - $activeContactCount);

        $pendingContacts = CampaignContact::where('campaign_id', $campaign->id)
            ->where('status', 'pending')
            ->orderBy('id')
            ->limit($availableSlots)
            ->get();

        foreach ($pendingContacts as $contact) {
            ProcessCampaignCall::dispatch($contact->id, $campaign->id);
        }

        event(new CampaignStatusUpdated($campaign));

        return Redirect::route('campaigns.index')
            ->with('success', 'Campaign started successfully. Calls are being processed.');
    }

    public function retryFailedCalls(Campaign $campaign)
    {
        $retryStatuses = ['failed', 'busy', 'not_answered'];
        CampaignContact::where('campaign_id', $campaign->id)
            ->whereIn('status', $retryStatuses)->update(['status' => 'pending']);

        $specialStatuses = ['calling', 'called', 'calling_ringing', 'attended', '1_pressed'];

        $activeContactCount = CampaignContact::where('campaign_id', $campaign->id)
            ->whereIn('status', $specialStatuses)
            ->count();

        $availableSlots = max(0, $campaign->no_of_calls - $activeContactCount);

        $retryContacts = CampaignContact::where('campaign_id', $campaign->id)
            ->where('status', 'pending')
            ->orderBy('id')
            ->limit($availableSlots)
            ->get();

        $campaign->update([
            'status' => 'in_progress'
        ]);

        event(new CampaignStatusUpdated($campaign));

        foreach ($retryContacts as $contact) {
            $contact->update(['status' => 'pending']);
            ProcessCampaignCall::dispatch($contact->id, $campaign->id);
        }

        return Redirect::route('campaigns.index', $campaign->id)
            ->with('success', 'Retry initiated for ' . $retryContacts->count() . ' contacts.');
    }

    public function stopCampaign(Campaign $campaign)
    {
        if ($campaign->status !== 'in_progress') {
            return Redirect::route('campaigns.index')
                ->with('error', 'Campaign cannot be stopped. It is not currently in progress.');
        }

        $campaign->update([
            'status' => 'paused',
            'completed_at' => now(),
        ]);

        Cache::forget("campaign_active_calls_{$campaign->id}");

        event(new CampaignStatusUpdated($campaign));

        return Redirect::route('campaigns.index')
            ->with('success', 'Campaign stopped successfully. Pending contacts will not be called.');
    }
}
