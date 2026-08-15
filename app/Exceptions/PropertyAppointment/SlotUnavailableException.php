<?php

namespace App\Exceptions\PropertyAppointment;

/**
 * The chosen slot is no longer bookable.
 *
 * Distinct from a generic failure so the caller can offer the customer the
 * "pick another time" recovery instead of a plain error — whether the clash
 * was caught by the availability pre-check or by the database's unique index.
 */
class SlotUnavailableException extends \Exception {}
