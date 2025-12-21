<?php

namespace App\Http\Controllers;

use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;

class BackupController extends Controller
{
    public function index()
    {
        $files = Storage::allFiles(config('app.name'));

        $files = collect($files)->map(function ($file) {
            return [
                'name' => basename($file),
                'size' => round(Storage::size($file) / (1024 * 1024), 2).' MB',
                'last_modified' => Storage::lastModified($file),
            ];
        })->sortByDesc('last_modified')->values();

        return view('backups.index', compact('files'));
    }

    public function store()
    {
        Artisan::call('backup:run', ['--only-db' => true, '--disable-notifications' => true]);

        $output = Artisan::output();
        Log::error('Backup Output: '.$output);

        session()->flash('backup_result', $output);

        return redirect()->back()->with('success', 'Backup process completed.');
    }

    public function get($file)
    {
        $filePath = "app/private/{$file}";

        return response()->download(storage_path('app/private/'.config('app.name').'/'.$file));
    }
}
