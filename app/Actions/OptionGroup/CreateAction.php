<?php

namespace App\Actions\OptionGroup;

use App\Models\OptionGroup;

/**
 * Creates an option group ("Flavour", "Drink base"…) with its options in one go.
 * $data['items'] is a list of ['name', 'price', 'product_id', 'quantity', 'next_group_id', 'is_default'].
 */
class CreateAction
{
    /** @return array{success: bool, message: string, data?: OptionGroup} */
    public function execute(array $data): array
    {
        try {
            $data = SavesItems::normalise($data);
            validationHelper(OptionGroup::rules(), $data);
            SavesItems::validateItems($data['items'] ?? [], 0);

            $model = OptionGroup::create($data);
            SavesItems::sync($model, $data['items'] ?? []);

            $return['success'] = true;
            $return['message'] = "Created the option group {$model->name}";
            $return['data'] = $model->load('items');
        } catch (\Throwable $th) {
            $return['success'] = false;
            $return['message'] = $th->getMessage();
        }

        return $return;
    }
}
