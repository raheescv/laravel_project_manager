<?php

namespace App\Actions\Property\PropertyLead;

use App\Models\PropertyLead;
use App\Models\User;

/**
 * Appends one note to a lead's `remarks` list, in the same {date, note, user}
 * shape the lead edit page writes.
 */
class AddNoteAction
{
    public function execute($id, array $data, $userId): array
    {
        try {
            /** @var PropertyLead|null $model */
            $model = PropertyLead::find($id);
            if (! $model) {
                throw new \Exception("Lead not found with the specified ID: $id.", 1);
            }

            $data['note'] = trim((string) ($data['note'] ?? ''));
            validationHelper([
                'note' => ['required', 'string', 'max:2000'],
                'date' => ['nullable', 'date'],
            ], $data);

            $notes = is_array($model->remarks) ? $model->remarks : (json_decode((string) $model->remarks, true) ?: []);
            $notes[] = [
                'date' => ($data['date'] ?? null) ?: now()->format('Y-m-d'),
                'note' => $data['note'],
                'user' => User::find($userId)?->name,
            ];

            $model->update(['remarks' => $notes, 'updated_by' => $userId]);

            return [
                'success' => true,
                'message' => 'Note added',
                'data' => ['id' => $model->id, 'model' => $model],
            ];
        } catch (\Throwable $th) {
            return [
                'success' => false,
                'message' => $th->getMessage(),
            ];
        }
    }
}
