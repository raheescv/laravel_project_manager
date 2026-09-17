<?php

namespace App\Exceptions;

use RuntimeException;

/** Something the parent has to fix or retry. The message is shown in the parent portal as-is. */
class ParentPortalException extends RuntimeException {}
