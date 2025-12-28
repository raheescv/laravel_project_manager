<?php

namespace App\Http\Controllers;

class PackageController extends Controller
{
    public function index()
    {
        return view('package.index');
    }

    public function create()
    {
        return view('package.page', ['id' => null]);
    }

    public function edit($id)
    {
        return view('package.page', ['id' => $id]);
    }
}
