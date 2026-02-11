<?php

namespace App\Http\Controllers;

use App\Models\Issue;
use Illuminate\View\View;

class IssueController extends Controller
{
    public function index(): View
    {
        return view('issue.index');
    }

    public function page(?string $id = null): View
    {
        return view('issue.page', ['id' => $id ? (int) $id : null]);
    }

    public function view(string $id): View
    {
        return view('issue.view', ['id' => (int) $id]);
    }

    public function print(string $id): View
    {
        $model = Issue::with(['account:id,name,mobile', 'items.product:id,name,code'])
            ->findOrFail((int) $id);

        return view('issue.print', compact('model'));
    }
}
