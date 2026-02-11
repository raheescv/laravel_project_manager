<?php

namespace App\Actions\Issue;

use App\Models\Issue;

class GetAction
{
    public function execute(int $id): ?Issue
    {
        return Issue::with(['account:id,name,mobile', 'items.product:id,name,code'])
            ->find($id);
    }
}
