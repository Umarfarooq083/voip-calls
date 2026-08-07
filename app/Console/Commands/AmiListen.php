<?php

namespace App\Console\Commands;

use App\Services\AmiEventListener;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

#[Signature('app:ami-listen')]
#[Description('Listen for AMI events and process campaign calls')]
class AmiListen extends Command
{
    public function handle(): int
    {
        // Log::info('AMI_LISTENER_COMMAND_STARTED');
        $this->info('Starting AMI Listener...');

        try {
            // Log::info('AMI_LISTENER_INITIALIZING');
            $listener = app(AmiEventListener::class);

            // Log::info('AMI_LISTENER_CONNECTING');
            $this->info('AMI Listener Started');

            $listener->connect();
            Log::info('AMI_LISTENER_CONNECTED');
            $this->info('AMI Connected - Listening for events...');
            $listener->listen();

            return self::SUCCESS;
        } catch (\Exception $e) {
            Log::error('AMI_LISTENER_ERROR', ['message' => $e->getMessage(), 'trace' => $e->getTraceAsString()]);
            $this->error('AMI Listener Error: '.$e->getMessage());

            return self::FAILURE;
        }
    }
}
