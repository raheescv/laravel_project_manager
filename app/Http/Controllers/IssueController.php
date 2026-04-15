<?php

namespace App\Http\Controllers;

use App\Models\Issue;
use App\Traits\UsesBrowsershot;
use Illuminate\View\View;

class IssueController extends Controller
{
    use UsesBrowsershot;

    public function index(): View
    {
        return view('issue.index');
    }

    public function page(string $type = 'issue'): View
    {
        $entryType = in_array($type, ['issue', 'return'], true) ? $type : 'issue';

        return view('issue.page', ['id' => null, 'type' => $entryType]);
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

    public function print(string $id)
    {
        $model = Issue::with(['account:id,name,mobile', 'items.product:id,name,code', 'items.inventory:id,product_id,barcode,batch', 'createdBy:id,name', 'updatedBy:id,name'])
            ->findOrFail((int) $id);

        $html = view('issue.print', compact('model'));
        if (! $model->signature) {
            return $html;
        }
        $html = $html->render();
        $pdf = $this->makeBrowsershot($html)->transparentBackground()->pdf();

        return response($pdf)
            ->header('Content-Type', 'application/pdf')
            ->header('Content-Disposition', 'inline; filename="issue-note-'.time().'.pdf"');
    }
}
