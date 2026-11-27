<?php

namespace App\Http\Controllers;

use Illuminate\View\View;

/** Inventory → Option Groups: the reusable choices (base, flavour, add-ons) the till asks. */
class OptionGroupController extends Controller
{
    public function index(): View
    {
        return view('product.option-group.index');
    }
}
