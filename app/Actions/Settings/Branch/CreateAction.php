<?php

namespace App\Actions\Settings\Branch;

use App\Jobs\BranchProductCreationJob;
use App\Models\Branch;
use Illuminate\Support\Facades\Auth;

class CreateAction
{
    public function execute($data)
    {
        try {
            validationHelper(Branch::rules(), $data);
            $model = Branch::create($data);

            BranchProductCreationJob::dispatch($model->id, Auth::id());

            $return['success'] = true;
            $return['message'] = 'Successfully Created Branch';
            $return['data'] = $model;
        } catch (\Throwable $th) {
            $return['success'] = false;
            $return['message'] = $th->getMessage();
        }

        return $return;
    }
}
