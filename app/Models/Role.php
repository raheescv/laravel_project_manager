<?php

namespace App\Models;

use Spatie\Permission\Models\Role as SpatieRole;

/**
 * Roles are shared by every tenant; which role a user holds is per tenant
 * because users themselves belong to one tenant.
 */
class Role extends SpatieRole {}
