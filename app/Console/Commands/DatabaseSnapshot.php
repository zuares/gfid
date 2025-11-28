<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;

class DatabaseSnapshot extends Command
{
    protected $signature = 'db:snapshot';
    protected $description = 'Simpan snapshot database.sqlite ke 1 file tetap (checkpoint)';

    public function handle(): int
    {
        $dbPath = database_path('database.sqlite');
        $backupDir = storage_path('backups');

        if (!File::exists($dbPath)) {
            $this->error("Database SQLite tidak ditemukan: $dbPath");
            return self::FAILURE;
        }

        if (!File::exists($backupDir)) {
            File::makeDirectory($backupDir, 0755, true);
        }

        $target = $backupDir . '/snapshot_dev.sqlite';

        File::copy($dbPath, $target);

        $this->info("✅ Snapshot tersimpan sebagai: storage/backups/snapshot_dev.sqlite");

        return self::SUCCESS;
    }
}
