<?php

namespace App\Actions\OptionGroup;

use App\Models\OptionGroup;

/**
 * Saves an option group and its options: rows with an id are updated, new rows
 * created, and rows left out removed. Past sales keep their own copy of a
 * removed option's name and price.
 */
class UpdateAction
{
    /** @return array{success: bool, message: string, data?: OptionGroup} */
    public function execute(array $data, int $id): array
    {
        try {
            $model = OptionGroup::find($id);
            if (! $model) {
                throw new \Exception("Option group not found with the specified ID: {$id}.", 1);
            }
            $data = SavesItems::normalise($data);
            validationHelper(OptionGroup::rules($id), $data);
            SavesItems::validateItems($data['items'] ?? [], $id);

            $model->update($data);
            SavesItems::sync($model, $data['items'] ?? []);

            $return['success'] = true;
            $return['message'] = "Saved the option group {$model->name}";
            $return['data'] = $model->load('items');
        } catch (\Throwable $th) {
            $return['success'] = false;
            $return['message'] = $th->getMessage();
        }

        return $return;
    }
}
