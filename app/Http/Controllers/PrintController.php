<?php

namespace App\Http\Controllers;

use App\Helpers\Facades\SaleHelper;

class PrintController extends Controller
{
    public function saleInvoice($id)
    {
        return SaleHelper::saleInvoice($id);
    }

    public function daySessionReport($id)
    {
        return SaleHelper::daySessionReport($id);
    }
}
