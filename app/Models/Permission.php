<?php

namespace App\Models;

use Spatie\Permission\Models\Permission as SpatiePermission;

/**
 * One shared catalogue for every tenant — permissions carry no tenant_id.
 */
class Permission extends SpatiePermission {}
