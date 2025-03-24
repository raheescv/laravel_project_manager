<?php

namespace App\Events;

use App\Models\User;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class BranchUpdated
{
    use Dispatchable, SerializesModels;

    public $user;

    public $branch_id;

    public function __construct(User $user, $branch_id)
    {
        $this->user = $user;
        $this->branch_id = $branch_id;
    }
}
