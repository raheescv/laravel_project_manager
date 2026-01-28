<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Maatwebsite\Excel\Facades\Excel;

class NotificationController extends Controller
{
    public function index()
    {
        return view('notification.index');
    }

    /**
     * Render an Excel file from storage in the browser as an HTML table.
     * Path must be relative to the public disk (e.g. exports/product_errors-123.xlsx).
     */
    public function excelView(Request $request): \Illuminate\Contracts\View\View|\Illuminate\Http\RedirectResponse
    {
        $path = $request->query('path');
        if (! $path || str_contains($path, '..')) {
            return redirect()->route('notification::index')->with('error', 'Invalid file path.');
        }

        $fullPath = Storage::disk('public')->path($path);
        if (! is_file($fullPath)) {
            return redirect()->route('notification::index')->with('error', 'File not found.');
        }

        $extension = strtolower(pathinfo($path, PATHINFO_EXTENSION));
        if (! in_array($extension, ['xlsx', 'xls', 'csv'], true)) {
            return redirect()->route('notification::index')->with('error', 'Only Excel or CSV files can be viewed.');
        }

        try {
            $sheets = Excel::toArray(new \stdClass(), $fullPath);
        } catch (\Throwable $e) {
            return redirect()->route('notification::index')->with('error', 'Could not read file: '.$e->getMessage());
        }

        return view('notification.excel-view', [
            'sheets' => $sheets,
            'fileName' => basename($path),
        ]);
    }
}
