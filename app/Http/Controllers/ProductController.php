<?php

namespace App\Http\Controllers;

class ProductController extends Controller
{
    public function index()
    {
        return view('product.index');
    }

    public function page($id = null)
    {
        return view('product.page', compact('id'));
    }
}
