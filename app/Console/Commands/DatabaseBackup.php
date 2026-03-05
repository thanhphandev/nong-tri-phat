<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Carbon\Carbon;

class DatabaseBackup extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'backup:run';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Chạy tiến trình backup database hàng ngày và giữ lại 10 bản sao an toàn gần nhất';

    protected $backupsPath;
    
    // All collections to backup
    protected $collections = [
        'cong_no', 'cong_no_ncc', 'dm_dia_chi', 'don_hang', 'don_vi_tinh',
        'hang_hoa', 'khach_hang', 'loai_hang', 'logs', 'nha_cung_cap',
        'nhap_hang', 'users', 'xuat_hang', 'tra_hang_khach', 'tra_hang_ncc'
    ];

    /**
     * Create a new command instance.
     *
     * @return void
     */
    public function __construct()
    {
        parent::__construct();
        $this->backupsPath = storage_path('app/backups');
        if (!is_dir($this->backupsPath)) {
            mkdir($this->backupsPath, 0755, true);
        }
    }

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle()
    {
        $this->info('Starting database backup process...');
        
        try {
            $timestamp = Carbon::now()->format('Y-m-d_H-i-s');
            $backupDir = $this->backupsPath . '/' . $timestamp;
            mkdir($backupDir, 0755, true);

            $mongodb = app('db')->connection('mongodb')->getMongoDB();
            $totalDocs = 0;
            $manifest = [
                'timestamp' => $timestamp,
                'created_at' => Carbon::now()->toIso8601String(),
                'database' => env('DB_DATABASE'),
                'collections' => []
            ];

            foreach ($this->collections as $collectionName) {
                try {
                    $collection = $mongodb->selectCollection($collectionName);
                    $documents = $collection->find()->toArray();
                    $count = count($documents);
                    $totalDocs += $count;

                    // Convert BSON to JSON
                    $jsonData = [];
                    foreach ($documents as $doc) {
                        $jsonData[] = $this->bsonToArray($doc);
                    }

                    $filePath = $backupDir . '/' . $collectionName . '.json';
                    file_put_contents($filePath, json_encode($jsonData, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));

                    $manifest['collections'][$collectionName] = ['count' => $count, 'size' => filesize($filePath)];
                } catch (\Exception $e) {
                    $manifest['collections'][$collectionName] = ['error' => $e->getMessage()];
                }
            }

            $manifest['total_documents'] = $totalDocs;
            file_put_contents($backupDir . '/manifest.json', json_encode($manifest, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));

            // Create ZIP
            $this->createZip($backupDir, $timestamp);
            
            // Cleanup folders after zipping
            $this->deleteDir($backupDir);
            
            // Cleanup old backups (keep last 10)
            $this->cleanupOldBackups(10);

            $this->info('Backup successful! Total: ' . number_format($totalDocs) . ' documents saved in ' . $timestamp . '.zip');
        } catch (\Exception $e) {
            $this->error('Backup error: ' . $e->getMessage());
            // Optionally log error here: \Log::error($e->getMessage());
        }
        
        return 0;
    }

    // === Helper Methods ===

    protected function bsonToArray($doc)
    {
        $result = [];
        foreach ($doc as $key => $value) {
            if ($value instanceof \MongoDB\BSON\ObjectId) {
                $result[$key] = ['$oid' => (string)$value];
            } elseif ($value instanceof \MongoDB\BSON\UTCDateTime) {
                $result[$key] = ['$date' => $value->toDateTime()->format('c')];
            } elseif (is_object($value) || is_array($value)) {
                $result[$key] = $this->bsonToArray($value);
            } else {
                $result[$key] = $value;
            }
        }
        return $result;
    }

    protected function createZip($backupDir, $timestamp)
    {
        $zipPath = $this->backupsPath . '/' . $timestamp . '.zip';
        $zip = new \ZipArchive();
        if ($zip->open($zipPath, \ZipArchive::CREATE | \ZipArchive::OVERWRITE) === true) {
            $files = new \RecursiveIteratorIterator(new \RecursiveDirectoryIterator($backupDir), \RecursiveIteratorIterator::LEAVES_ONLY);
            foreach ($files as $file) {
                if (!$file->isDir()) {
                    $zip->addFile($file->getRealPath(), substr($file->getRealPath(), strlen($backupDir) + 1));
                }
            }
            $zip->close();
        }
    }

    protected function cleanupOldBackups($keep = 10)
    {
        $items = glob($this->backupsPath . '/*.zip');
        usort($items, fn($a, $b) => filemtime($b) - filemtime($a));

        $toDelete = array_slice($items, $keep);
        foreach ($toDelete as $zipFile) {
            if (file_exists($zipFile)) {
                unlink($zipFile);
                $this->info('Cleaned up old backup: ' . basename($zipFile));
            }
        }
    }

    protected function deleteDir($dir)
    {
        if (!is_dir($dir)) return;
        foreach (scandir($dir) as $item) {
            if ($item === '.' || $item === '..') continue;
            $path = $dir . '/' . $item;
            is_dir($path) ? $this->deleteDir($path) : unlink($path);
        }
        rmdir($dir);
    }
}
