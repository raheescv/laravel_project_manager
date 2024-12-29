<?php

namespace App\Http\Controllers;

class ProductController extends Controller
{
    public function index()
    {
        return view('product.index');
    }

    public function create()
    {
        return view('product.create');
    }

    public function edit($id)
    {
        return view('product.edit');
    }
}
