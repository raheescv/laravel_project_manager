<?php

namespace App\Http\Controllers\Settings;

use App\Http\Controllers\Controller;
use App\Models\DocumentType;
use Illuminate\Http\Request;

class DocumentTypeController extends Controller
{
    public function index()
    {
        return view('settings.document-type.index');
    }

    public function get(Request $request)
    {
        $list = (new DocumentType())->getDropDownList($request->all());

        return response()->json($list);
    }
}
