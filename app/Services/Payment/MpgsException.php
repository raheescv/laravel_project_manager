<?php

namespace App\Services\Payment;

use RuntimeException;

/** The Mastercard Gateway could not be reached or refused the request. Safe to show a parent. */
class MpgsException extends RuntimeException {}
