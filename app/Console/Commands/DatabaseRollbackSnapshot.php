<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;

class DatabaseRollbackSnapshot extends Command
{
    protected $signature = 'db:rollback-snapshot';
    protected $description = 'Rollback database.sqlite ke snapshot_dev.sqlite';

    public function handle(): int
    {
        $snapshot = storage_path('backups/snapshot_dev.sqlite');
        $dbPath = database_path('database.sqlite');

        if (!File::exists($snapshot)) {
            $this->error("Snapshot tidak ditemukan: $snapshot");
            $this->error('Jalankan dulu: php artisan db:snapshot');
            return self::FAILURE;
        }

        // Safety backup sebelum ditimpa
        if (File::exists($dbPath)) {
            $safety = storage_path('backups/before_rollback_' . now()->format('Ymd_His') . '.sqlite');
            File::copy($dbPath, $safety);
            $this->info("💾 Database saat ini disimpan sebagai: " . basename($safety));
        }

        File::copy($snapshot, $dbPath);

        $this->call('optimize:clear');

        $this->info("🎯 Database berhasil di-rollback ke snapshot_dev.sqlite");

        return self::SUCCESS;
    }
}
