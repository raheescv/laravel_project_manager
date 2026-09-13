<?php

namespace App\Exceptions;

use RuntimeException;

/** A checkout the customer has to fix or retry. The message is shown on the storefront as-is. */
class StorefrontCheckoutException extends RuntimeException {}
