<?php

namespace Tonysm\GlobalId\Commands;

use Illuminate\Console\Command;

class GlobalIdCommand extends Command
{
    public $signature = 'globalid-laravel';

    public $description = 'My command';

    public function handle()
    {
        $this->comment('All done');
    }
}
