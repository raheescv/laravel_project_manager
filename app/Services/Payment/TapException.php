<?php

namespace App\Services\Payment;

use RuntimeException;

/** Tap could not be reached, or refused the request. The message is safe to show a customer. */
class TapException extends RuntimeException {}
