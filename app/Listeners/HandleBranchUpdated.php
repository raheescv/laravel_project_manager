<?php

namespace App\Listeners;

use App\Events\BranchUpdated;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Support\Facades\Auth;

class HandleBranchUpdated implements ShouldQueue
{
    use InteractsWithQueue;

    public function handle(BranchUpdated $event)
    {
        if (Auth::check() && Auth::id() === $event->user->id) {
            session(['branch_id' => $event->branch_id]);
            session(['branch_code' => $event->user->branch?->code]);
            session(['branch_name' => $event->user->branch?->code]);
        }
    }
}
