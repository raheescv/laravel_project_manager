<?php

namespace App\Exceptions;

use RuntimeException;

/** Something the parent has to fix or retry. The message is shown in the parent portal as-is. */
class ParentPortalException extends RuntimeException
{
    /**
     * @param  array<string, mixed>  $data  Context the portal renders beside the message,
     *                                      e.g. `retry_at` for a top-up it must count down to.
     */
    public function __construct(string $message, public readonly array $data = [])
    {
        parent::__construct($message);
    }
}
