<?php

namespace App\Console\Commands;

use App\Enums\NotificationStyles;
use App\Models\User;
use App\Notifications\GenericNotification;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;

#[Signature('app:test-notifications')]
#[Description('Command description')]
class TestNotifications extends Command
{
    /**
     * Execute the console command.
     */
    public function handle(): void
    {
        $user = User::find(1);

        $user->notify(new GenericNotification("My title", "My body", NotificationStyles::ERROR));
    }
}
