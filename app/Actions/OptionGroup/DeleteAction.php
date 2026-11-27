<?php

namespace App\Actions\OptionGroup;

use App\Models\OptionGroup;
use App\Models\OptionGroupItem;

/**
 * Deletes an option group that no product asks and no option opens. Past sales
 * keep their own copy of what was picked.
 */
class DeleteAction
{
    /** @return array{success: bool, message: string, data?: mixed} */
    public function execute(int $id): array
    {
        try {
            $model = OptionGroup::find($id);
            if (! $model) {
                throw new \Exception("Option group not found with the specified ID: {$id}.", 1);
            }
            $products = $model->products()->count();
            if ($products) {
                throw new \Exception("{$model->name} is used on {$products} product".($products === 1 ? '' : 's').'. Remove it from them first.', 1);
            }
            $openers = OptionGroupItem::where('next_group_id', $id)->where('option_group_id', '!=', $id)->with('group:id,name')->first();
            if ($openers) {
                throw new \Exception("{$model->name} opens after {$openers->name} in {$openers->group?->name}. Remove that link first.", 1);
            }
            $model->delete();

            $return['success'] = true;
            $return['message'] = "Deleted the option group {$model->name}";
            $return['data'] = [];
        } catch (\Throwable $th) {
            $return['success'] = false;
            $return['message'] = $th->getMessage();
        }

        return $return;
    }
}
