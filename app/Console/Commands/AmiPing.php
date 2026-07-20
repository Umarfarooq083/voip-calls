<?php

namespace App\Console\Commands;

use App\Services\AmiService;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;

#[Signature('app:ami-ping')]
#[Description('Command description')]
class AmiPing extends Command
{    /**
     * Execute the console command.
     */
    public function handle(AmiService $ami)
    {
        //    dd($ami->getChannels());
           dd($ami->getChannels());
    }
}
