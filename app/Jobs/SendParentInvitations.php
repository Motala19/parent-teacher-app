<?php

namespace App\Jobs;

use App\Models\User;
use App\Notifications\ParentInvitation;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Password;

class SendParentInvitations implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $timeout = 1800;
    public int $tries   = 1;

           public function __construct(public array $userIds, public bool $force = false)
    {
    }

    public function handle(): void
    {
        User::role('parent')
            ->whereIn('id', $this->userIds)
            ->when(! $this->force, fn ($q) => $q->whereNull('invited_at'))
            ->get()
            ->each(function (User $user) {
                if (! $user->realEmail()) {
                    return;
                }

                $token = Password::broker()->createToken($user);

                $user->notify(new ParentInvitation($token));
                $user->forceFill(['invited_at' => now()])->save();
            });
    }
}