<?php

namespace App\Exceptions;

use Carbon\CarbonInterface;
use RuntimeException;

/**
 * An earlier top-up for the same student has no result yet, so a new one is refused
 * (QPay certification: a parent who closed the QPay page must not be able to pay twice).
 *
 * `retryAt` is the moment that earlier payment becomes old enough to be inquired, so
 * the portal can count down to it instead of leaving the parent watching the clock.
 */
class TopupInProgressException extends RuntimeException
{
    public function __construct(string $message, public readonly CarbonInterface $retryAt)
    {
        parent::__construct($message, 1);
    }
}
