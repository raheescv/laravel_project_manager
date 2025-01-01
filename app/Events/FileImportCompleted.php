<?php

namespace App\Events;

use Illuminate\Queue\SerializesModels;

class FileImportCompleted
{
    use SerializesModels;

    public function __construct(public $userId, public $entity, public $errors) {}
}
