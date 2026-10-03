<?php

namespace App\Http\Controllers;

use App\Actions\Ticket\BroadcastActivityAction;
use App\Actions\Ticket\ImportAction;
use App\Exports\Templates\TicketImportTemplate;
use App\Http\Requests\Ticket\TicketImportRequest;
use App\Models\Ticket;
use App\Support\Ticket\TicketImportSheet;
use App\Traits\ApiResponseTrait;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Maatwebsite\Excel\Facades\Excel;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class TicketImportController extends Controller
{
    use ApiResponseTrait;

    public function template(): BinaryFileResponse
    {
        return Excel::download(new TicketImportTemplate(), 'ticket_import_template.xlsx');
    }

    public function upload(Request $request): JsonResponse
    {
        $request->validate(
            ['file' => ['required', 'file', 'max:10240', 'extensions:csv,xlsx,xls']],
            ['file.extensions' => 'Upload an Excel (.xlsx, .xls) or CSV file.', 'file.max' => 'The sheet must be 10 MB or smaller.']
        );

        try {
            $sheet = TicketImportSheet::load($request->file('file'), Auth::id());
        } catch (\Throwable $th) {
            return $this->sendError('This file could not be read: '.$th->getMessage(), [], 422);
        }

        return $this->sendSuccess($sheet + [
            'file_name' => $request->file('file')->getClientOriginalName(),
            'fields' => TicketImportSheet::fields(),
            'max_rows' => TicketImportSheet::MAX_ROWS,
            'header_scan_rows' => TicketImportSheet::HEADER_SCAN_ROWS,
            'statuses' => Ticket::statuses(),
        ], 'Sheet read.');
    }

    public function sheet(Request $request): JsonResponse
    {
        $request->validate([
            'token' => ['required', 'string', 'uuid'],
            'sheet' => ['required', 'string', 'max:255'],
            'header_row' => ['nullable', 'integer', 'min:1', 'max:'.TicketImportSheet::HEADER_SCAN_ROWS],
        ]);

        try {
            $sheet = TicketImportSheet::switchSheet(Auth::id(), $request->input('token'), $request->input('sheet'), $request->integer('header_row') ?: null);
        } catch (\Throwable $th) {
            return $this->sendError('This sheet could not be read: '.$th->getMessage(), [], 422);
        }

        if ($sheet === null) {
            return $this->sendError('This upload has expired — upload the sheet again.', [], 422);
        }

        return $this->sendSuccess($sheet, 'Sheet read.');
    }

    public function review(TicketImportRequest $request): JsonResponse
    {
        $rows = TicketImportSheet::rows(Auth::id(), $request->input('token'));
        if ($rows === null) {
            return $this->sendError('This upload has expired — upload the sheet again.', [], 422);
        }

        $reviewed = TicketImportSheet::review($rows, $request->input('mappings', []), $request->input('options', []));

        return $this->sendSuccess([
            'summary' => TicketImportSheet::summary($reviewed),
            'rows' => $reviewed,
        ], 'Rows checked.');
    }

    public function commit(TicketImportRequest $request, ImportAction $action): JsonResponse
    {
        $rows = TicketImportSheet::rows(Auth::id(), $request->input('token'));
        if ($rows === null) {
            return $this->sendError('This upload has expired — upload the sheet again.', [], 422);
        }

        $response = $action->execute($rows, $request->input('mappings', []), $request->input('options', []), Auth::id());
        if (! $response['success']) {
            return $this->sendError($response['message'], [], 422);
        }

        TicketImportSheet::forget(Auth::id(), $request->input('token'));
        app(BroadcastActivityAction::class)->execute('imported');

        return $this->sendSuccess($response['data'], $response['message']);
    }
}
