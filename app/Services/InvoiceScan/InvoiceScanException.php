<?php

namespace App\Services\InvoiceScan;

use RuntimeException;

/** An upload we could not read — the message is shown to the user as is. */
class InvoiceScanException extends RuntimeException {}
