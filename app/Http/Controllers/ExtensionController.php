<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreExtensionRequest;
use App\Http\Requests\UpdateExtensionRequest;
use App\Models\Extension;
use App\Services\ExtensionService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Redirect;
use Inertia\Inertia;

class ExtensionController extends Controller
{
    public function __construct()
    {
        // $this->middleware('auth');
    }

    public function index(Request $request)
    {
        $filters = $request->only(['active', 'search']);

        $extensions = app(ExtensionService::class)->list($filters);

        return Inertia::render('Extensions/Index', [
            'extensions' => $extensions->items(),
            'filters' => $filters,
            'meta' => [
                'current_page' => $extensions->currentPage(),
                'last_page' => $extensions->lastPage(),
                'per_page' => $extensions->perPage(),
                'total' => $extensions->total(),
            ],
            'links' => [
                'first' => $extensions->url(1),
                'last' => $extensions->url($extensions->lastPage()),
                'prev' => $extensions->url($extensions->currentPage() - 1),
                'next' => $extensions->url($extensions->currentPage() + 1),
            ],
            'success' => session('success'),
        ]);
    }

    public function create()
    {
        return Inertia::render('Extensions/Create');
    }

    public function store(StoreExtensionRequest $request): RedirectResponse
    {
        app(ExtensionService::class)->create($request->validated());

        return Redirect::route('extensions.index')
            ->with('success', 'Extension created successfully.');
    }

    public function show(Extension $extension)
    {
        $extension->load(['pjsipEndpoint', 'pjsipAuth', 'pjsipAor']);

        return Inertia::render('Extensions/Show', [
            'extension' => [
                'id' => $extension->id,
                'name' => $extension->name,
                'extension' => $extension->extension,
                'display_name' => $extension->display_name,
                'secret' => '****',
                'context' => $extension->context,
                'transport' => $extension->transport,
                'caller_id_name' => $extension->caller_id_name,
                'caller_id_num' => $extension->caller_id_num,
                'mailbox' => $extension->mailbox,
                'vm_context' => $extension->vm_context,
                'timeout' => $extension->timeout,
                'is_active' => $extension->is_active,
                'notes' => $extension->notes,
                'last_registered_at' => $extension->last_registered_at,
                'registered' => $extension->pjsipEndpoint ? true : false,
            ],
        ]);
    }

    public function edit(Extension $extension)
    {
        return Inertia::render('Extensions/Edit', [
            'extension' => [
                'id' => $extension->id,
                'name' => $extension->name,
                'extension' => $extension->extension,
                'display_name' => $extension->display_name,
                'context' => $extension->context,
                'transport' => $extension->transport,
                'caller_id_name' => $extension->caller_id_name,
                'caller_id_num' => $extension->caller_id_num,
                'mailbox' => $extension->mailbox,
                'vm_context' => $extension->vm_context,
                'timeout' => $extension->timeout,
                'is_active' => $extension->is_active,
                'notes' => $extension->notes,
            ],
        ]);
    }

    public function update(UpdateExtensionRequest $request, Extension $extension): RedirectResponse
    {
        app(ExtensionService::class)->update($extension, $request->validated());

        return Redirect::route('extensions.index')
            ->with('success', 'Extension updated successfully.');
    }

    public function destroy(Extension $extension): RedirectResponse
    {
        app(ExtensionService::class)->delete($extension);

        return Redirect::route('extensions.index')
            ->with('success', 'Extension deleted successfully.');
    }
}
