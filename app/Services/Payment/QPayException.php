<?php

namespace App\Services\Payment;

use RuntimeException;

/** QPay could not be reached, refused the request, or sent a response that failed verification. Safe to show a parent. */
class QPayException extends RuntimeException {}
