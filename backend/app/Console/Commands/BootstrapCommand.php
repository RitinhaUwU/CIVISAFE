<?php

namespace App\Console\Commands;

use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;
use Schema;

#[Signature('app:bootstrap')]
#[Description('Command description')]
class BootstrapCommand extends Command
{
    /**
     * Execute the console command.
     */
    public function handle(): void
    {
        if(!Schema::hasTable('users'))
        {
            $this->runCommand('migrate', ['--force' => true], $this->output);
            $this->runCommand('db:seed --class=ProdSeeder', ['--force' => true], $this->output);
        }

        $this->runCommand('migrate', ['--force' => true], $this->output);
    }
}
