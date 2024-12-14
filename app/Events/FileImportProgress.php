<?php

namespace App\Events;

use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class FileImportProgress implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public function __construct(public $user_id, public $type, public $progress) {}

    public function broadcastOn()
    {
        return new Channel('file-import-channel-'.$this->user_id);
    }

    public function broadcastAs()
    {
        return 'file-import-event-'.$this->user_id;
    }
}
