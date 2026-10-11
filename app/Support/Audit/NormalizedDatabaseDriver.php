<?php

namespace App\Support\Audit;

use OwenIt\Auditing\Contracts\Audit;
use OwenIt\Auditing\Contracts\Auditable;
use OwenIt\Auditing\Drivers\Database;

class NormalizedDatabaseDriver extends Database
{
    /**
     * Store the audit without number-format-only changes, and skip an
     * update entirely when nothing real changed.
     */
    public function audit(Auditable $model): ?Audit
    {
        $data = $model->toAudit();

        [$data['old_values'], $data['new_values']] = AuditChanges::withoutEquivalentValues(
            (array) $data['old_values'],
            (array) $data['new_values'],
        );

        if ($data['event'] === 'updated' && $data['old_values'] === [] && $data['new_values'] === []) {
            return null;
        }

        return call_user_func([get_class($model->audits()->getModel()), 'create'], $data);
    }
}
