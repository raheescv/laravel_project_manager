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

    public function page(string $type = 'issue'): View
    {
        $entryType = in_array($type, ['issue', 'return'], true) ? $type : 'issue';

        return view('issue.page', ['id' => null,'type' => $entryType]);
    }


    public function edit(string $id): View
    {
        $issue = Issue::findOrFail((int) $id);
        return view('issue.page', ['id' => (int) $id, 'type' => 'issue']);
    }

    public function view(string $id): View
    {
        return view('issue.view', ['id' => (int) $id]);
    }

    public function print(string $id): View
    {
        $model = Issue::with(['account:id,name,mobile', 'items.product:id,name,code', 'createdBy:id,name'])
            ->findOrFail((int) $id);

        return view('issue.print', compact('model'));
    }
}
