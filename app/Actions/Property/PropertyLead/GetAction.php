<?php

namespace App\Actions\Property\PropertyLead;

use App\Models\PropertyLead;

class GetAction
{
    public function execute(array $data): array
    {
        $list = PropertyLead::with([
            'assignee:id,name',
            'group:id,name',
            'country:id,name',
        ]);

        $list = $list->when($data['assigned_to'] ?? '', fn ($q, $v) => $q->where('assigned_to', $v));
        $list = $list->when($data['nationality'] ?? '', fn ($q, $v) => $q->where('nationality', $v));
        $list = $list->when($data['location'] ?? '', fn ($q, $v) => $q->where('location', $v));
        $list = $list->when($data['country_id'] ?? '', fn ($q, $v) => $q->where('country_id', $v));
        $list = $list->when($data['source'] ?? '', fn ($q, $v) => $q->where('source', $v));
        $list = $list->when($data['type'] ?? '', fn ($q, $v) => $q->where('type', $v));
        $list = $list->when($data['from_date'] ?? '', fn ($q, $v) => $q->whereDate('created_at', '>=', $v));
        $list = $list->when($data['to_date'] ?? '', fn ($q, $v) => $q->whereDate('created_at', '<=', $v));
        $list = $list->when($data['property_group_id'] ?? '', fn ($q, $v) => $q->where('property_group_id', $v));
        $list = $list->when($data['status'] ?? '', fn ($q, $v) => $q->where('status', $v));
        $list = $list->when($data['search'] ?? '', function ($q, $v): void {
            $q->where(function ($qq) use ($v): void {
                $qq->where('name', 'like', "%{$v}%")
                    ->orWhere('mobile', 'like', "%{$v}%")
                    ->orWhere('email', 'like', "%{$v}%")
                    ->orWhere('company_name', 'like', "%{$v}%");
            });
        });

        if (session('branch_id')) {
            $list = $list->currentBranch();
        }

        return [
            'list' => $list,
        ];
    }
}
