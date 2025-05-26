<?php

namespace App\Console\Commands\SingleUse;

use App\Jobs\BranchProductCreationJob;
use App\Models\Branch;
use Illuminate\Console\Command;

class BranchWiseProductInventoryCommand extends Command
{
    protected $signature = 'app:branch-wise-product-inventory';

    protected $description = 'To sync the missing product list from the inventory based on the branch';

    public function handle()
    {
        $list = Branch::get();
        foreach ($list as $model) {
            BranchProductCreationJob::dispatch($model->id, 1);
        }
    }
}
