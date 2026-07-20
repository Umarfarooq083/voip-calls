<?php

use App\Http\Controllers\CampaignController;
use App\Http\Controllers\ExtensionController;
use App\Http\Controllers\ProfileController;
use App\Services\AmiService;
use Illuminate\Foundation\Application;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Route;
use Inertia\Inertia;

Route::get('/', function () {
    return Inertia::render('Welcome', [
        'canLogin' => Route::has('login'),
        'canRegister' => Route::has('register'),
        'laravelVersion' => Application::VERSION,
        'phpVersion' => PHP_VERSION,
    ]);
});

Route::get('/dashboard', function () {
    return Inertia::render('Dashboard');
})->middleware(['auth', 'verified'])->name('dashboard');

Route::middleware('auth')->group(function () {
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');

    Route::get('extensions', [ExtensionController::class,'index'])->name('extensions.index');
    Route::get('extensions/create', [ExtensionController::class,'create'])->name('extensions.create');
    Route::post('extensions', [ExtensionController::class,'store'])->name('extensions.store');
    Route::get('extensions/{extension}/edit', [ExtensionController::class,'edit'])->name('extensions.edit');
    Route::patch('extensions/{extension}/update', [ExtensionController::class,'update'])->name('extensions.update');
    Route::get('extensions/{extension}', [ExtensionController::class,'show'])->name('extensions.show');
    Route::delete('extensions/{extension}', [ExtensionController::class,'destroy'])->name('extensions.destroy');

    Route::get('campaigns', [CampaignController::class,'index'])->name('campaigns.index');
    Route::get('campaigns/create', [CampaignController::class,'create'])->name('campaigns.create');
    Route::post('campaigns', [CampaignController::class,'store'])->name('campaigns.store');
    Route::get('campaigns/{campaign}', [CampaignController::class,'show'])->name('campaigns.show');
    Route::get('campaigns/{campaign}/edit', [CampaignController::class,'edit'])->name('campaigns.edit');
    Route::post('campaigns/{campaign}/update', [CampaignController::class,'update'])->name('campaigns.update');
    Route::delete('campaigns/{campaign}', [CampaignController::class,'destroy'])->name('campaigns.destroy');
    Route::delete('campaign_contacts/{contact}', [CampaignController::class,'destroyContact'])->name('campaign_contacts.destroy');

    Route::get('ami/test', function (AmiService $service) {
        $connected = $service->connect();

        return response()->json([
            'connected' => $connected,
            'host' => config('ami.host'),
            'port' => config('ami.port'),
            'username' => config('ami.username'),
        ]);
    })->name('ami.test');

    Route::get('ami/ping', function (AmiService $service) {
        $result = $service->ping();

        return response()->json([
            'success' => $result,
        ]);
    })->name('ami.ping');

    // get ivr using this end point 
    Route::get('/test-asterisk-db', function () {

    $ivrs = DB::connection('asterisk')
        ->table('ivr_details')
        ->get();

    return response()->json($ivrs);

});



});

require __DIR__.'/auth.php';
