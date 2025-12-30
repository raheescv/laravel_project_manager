<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Session;

class ExpireSessionCommand extends Command
{
    protected $signature = 'session:expire
                            {session_id? : The specific session ID to expire}
                            {--all : Expire all sessions}
                            {--user= : Expire all sessions for a specific user ID}';

    protected $description = 'Manually expire sessions by deleting session files or database records';

    public function handle()
    {
        $driver = config('session.driver');
        $sessionId = $this->argument('session_id');
        $expireAll = $this->option('all');
        $userId = $this->option('user');

        if ($driver === 'file') {
            return $this->expireFileSessions($sessionId, $expireAll);
        } elseif ($driver === 'database') {
            return $this->expireDatabaseSessions($sessionId, $expireAll, $userId);
        } else {
            $this->error("Session driver '{$driver}' is not supported by this command.");
            $this->info("Supported drivers: 'file', 'database'");
            return 1;
        }
    }

    protected function expireFileSessions($sessionId = null, $expireAll = false)
    {
        $sessionPath = storage_path('framework/sessions');

        if (!File::exists($sessionPath)) {
            $this->error("Session directory not found: {$sessionPath}");
            return 1;
        }

        if ($expireAll) {
            $files = File::glob($sessionPath . '/sess_*');
            $count = count($files);
            File::delete($files);
            $this->info("Expired {$count} session file(s).");
            return 0;
        }

        if ($sessionId) {
            $filePath = $sessionPath . '/sess_' . $sessionId;
            if (File::exists($filePath)) {
                File::delete($filePath);
                $this->info("Expired session: {$sessionId}");
                return 0;
            } else {
                $this->error("Session file not found: {$filePath}");
                return 1;
            }
        }

        $this->error("Please provide a session ID or use --all flag.");
        return 1;
    }

    protected function expireDatabaseSessions($sessionId = null, $expireAll = false, $userId = null)
    {
        $table = config('session.table', 'sessions');

        try {
            if ($expireAll) {
                $count = DB::table($table)->count();
                DB::table($table)->delete();
                $this->info("Expired {$count} session(s) from database.");
                return 0;
            }

            if ($userId) {
                $count = DB::table($table)
                    ->where('user_id', $userId)
                    ->count();
                DB::table($table)
                    ->where('user_id', $userId)
                    ->delete();
                $this->info("Expired {$count} session(s) for user ID: {$userId}");
                return 0;
            }

            if ($sessionId) {
                $deleted = DB::table($table)
                    ->where('id', $sessionId)
                    ->delete();

                if ($deleted) {
                    $this->info("Expired session: {$sessionId}");
                    return 0;
                } else {
                    $this->error("Session not found: {$sessionId}");
                    return 1;
                }
            }

            $this->error("Please provide a session ID, use --all flag, or specify --user=USER_ID.");
            return 1;
        } catch (\Exception $e) {
            $this->error("Error expiring sessions: " . $e->getMessage());
            return 1;
        }
    }
}

