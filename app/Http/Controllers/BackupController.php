<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Carbon\Carbon;
use Session;

class BackupController extends Controller
{
    protected $backupsPath;
    
    // All collections to backup
    protected $collections = [
        'cong_no', 'cong_no_ncc', 'dm_dia_chi', 'don_hang', 'don_vi_tinh',
        'hang_hoa', 'khach_hang', 'loai_hang', 'logs', 'nha_cung_cap',
        'nhap_hang', 'users', 'xuat_hang', 'tra_hang_khach', 'tra_hang_ncc'
    ];

    public function __construct()
    {
        $this->backupsPath = storage_path('app/backups');
        if (!is_dir($this->backupsPath)) {
            mkdir($this->backupsPath, 0755, true);
        }
    }

    /**
     * Display backup management page
     */
    public function index()
    {
        $backups = $this->getBackupsList();
        return view('Admin.Backup.index', compact('backups'));
    }

    /**
     * Create new backup
     */
    public function create(Request $request)
    {
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
            
            // Cleanup old backups (keep last 10)
            $this->cleanupOldBackups(10);

            Session::flash('msg', 'Backup thành công! Tổng: ' . number_format($totalDocs) . ' documents');
            Session::flash('msg_type', 'success');
        } catch (\Exception $e) {
            Session::flash('msg', 'Lỗi: ' . $e->getMessage());
            Session::flash('msg_type', 'error');
        }

        return redirect()->back();
    }

    /**
     * Download backup
     */
    public function download($filename)
    {
        if (strpos($filename, '..') !== false) abort(403);
        
        $zipPath = $this->backupsPath . '/' . $filename . '.zip';
        if (file_exists($zipPath)) {
            $headers = [
                'Content-Type' => 'application/zip',
                'Content-Disposition' => 'attachment; filename="' . $filename . '.zip"',
            ];
            return response()->download($zipPath, $filename . '.zip', $headers);
        }
        
        Session::flash('msg', 'File không tồn tại');
        return redirect()->back();
    }

    /**
     * Restore from backup
     */
    public function restore(Request $request, $filename)
    {
        if (strpos($filename, '..') !== false) abort(403);

        $backupDir = $this->backupsPath . '/' . $filename;
        $zipPath = $this->backupsPath . '/' . $filename . '.zip';

        try {
            // Extract if needed
            if (!is_dir($backupDir) && file_exists($zipPath)) {
                $zip = new \ZipArchive();
                if ($zip->open($zipPath) === true) {
                    $zip->extractTo($this->backupsPath);
                    $zip->close();
                }
            }

            $manifestPath = $backupDir . '/manifest.json';
            if (!file_exists($manifestPath)) {
                throw new \Exception('Không tìm thấy manifest.json');
            }

            $manifest = json_decode(file_get_contents($manifestPath), true);
            $mongodb = app('db')->connection('mongodb')->getMongoDB();
            $totalRestored = 0;

            foreach ($manifest['collections'] as $collectionName => $info) {
                if (isset($info['error'])) continue;
                
                $filePath = $backupDir . '/' . $collectionName . '.json';
                if (!file_exists($filePath)) continue;

                $jsonData = json_decode(file_get_contents($filePath), true);
                if (empty($jsonData)) continue;

                $collection = $mongodb->selectCollection($collectionName);
                $collection->drop();

                $documents = [];
                foreach ($jsonData as $doc) {
                    $documents[] = $this->arrayToBson($doc);
                }
                $collection->insertMany($documents);
                $totalRestored += count($documents);
            }

            Session::flash('msg', 'Khôi phục thành công! Tổng: ' . number_format($totalRestored) . ' documents');
            Session::flash('msg_type', 'success');
        } catch (\Exception $e) {
            Session::flash('msg', 'Lỗi: ' . $e->getMessage());
            Session::flash('msg_type', 'error');
        }

        return redirect()->back();
    }

    /**
     * Delete backup
     */
    public function delete($filename)
    {
        if (strpos($filename, '..') !== false) abort(403);

        $folderPath = $this->backupsPath . '/' . $filename;
        $zipPath = $this->backupsPath . '/' . $filename . '.zip';

        if (is_dir($folderPath)) $this->deleteDir($folderPath);
        if (file_exists($zipPath)) unlink($zipPath);

        Session::flash('msg', 'Đã xóa backup: ' . $filename);
        return redirect()->back();
    }

    /**
     * Upload backup from local machine
     */
    public function upload(Request $request)
    {
        try {
            if (!$request->hasFile('backup_file')) {
                throw new \Exception('Vui lòng chọn file backup');
            }

            $file = $request->file('backup_file');
            
            // Validate file
            $extension = strtolower($file->getClientOriginalExtension());
            if ($extension !== 'zip') {
                throw new \Exception('Chỉ chấp nhận file ZIP');
            }

            // Get original filename without extension
            $originalName = pathinfo($file->getClientOriginalName(), PATHINFO_FILENAME);
            
            // Sanitize filename
            $safeName = preg_replace('/[^a-zA-Z0-9_\-]/', '_', $originalName);
            if (empty($safeName)) {
                $safeName = 'uploaded_' . Carbon::now()->format('Y-m-d_H-i-s');
            }

            // Check if already exists
            $zipPath = $this->backupsPath . '/' . $safeName . '.zip';
            $counter = 1;
            while (file_exists($zipPath)) {
                $safeName = $originalName . '_' . $counter;
                $zipPath = $this->backupsPath . '/' . $safeName . '.zip';
                $counter++;
            }

            // Move uploaded file
            $file->move($this->backupsPath, $safeName . '.zip');

            // Extract to verify and create folder
            $backupDir = $this->backupsPath . '/' . $safeName;
            $zip = new \ZipArchive();
            if ($zip->open($zipPath) === true) {
                $zip->extractTo($this->backupsPath);
                $zip->close();
            } else {
                unlink($zipPath);
                throw new \Exception('Không thể giải nén file ZIP');
            }

            // Check manifest exists
            $manifestPath = $backupDir . '/manifest.json';
            if (!file_exists($manifestPath)) {
                // Try to find manifest in subdirectory (common ZIP structure)
                $subdirs = glob($this->backupsPath . '/*', GLOB_ONLYDIR);
                foreach ($subdirs as $subdir) {
                    if (file_exists($subdir . '/manifest.json')) {
                        $manifestPath = $subdir . '/manifest.json';
                        $backupDir = $subdir;
                        break;
                    }
                }
            }

            if (file_exists($manifestPath)) {
                $manifest = json_decode(file_get_contents($manifestPath), true);
                $docCount = $manifest['total_documents'] ?? 0;
                Session::flash('msg', 'Upload thành công! File: ' . $safeName . '.zip (' . number_format($docCount) . ' documents)');
            } else {
                Session::flash('msg', 'Upload thành công: ' . $safeName . '.zip (Không tìm thấy manifest)');
            }
            Session::flash('msg_type', 'success');

        } catch (\Exception $e) {
            Session::flash('msg', 'Lỗi upload: ' . $e->getMessage());
            Session::flash('msg_type', 'error');
        }

        return redirect()->back();
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

    protected function arrayToBson($data)
    {
        $result = [];
        foreach ($data as $key => $value) {
            if (is_array($value)) {
                if (isset($value['$oid'])) {
                    $result[$key] = new \MongoDB\BSON\ObjectId($value['$oid']);
                } elseif (isset($value['$date'])) {
                    $result[$key] = new \MongoDB\BSON\UTCDateTime(strtotime($value['$date']) * 1000);
                } else {
                    $result[$key] = $this->arrayToBson($value);
                }
            } else {
                $result[$key] = $value;
            }
        }
        return $result;
    }

    protected function getBackupsList()
    {
        $backups = [];
        if (!is_dir($this->backupsPath)) return $backups;

        $items = scandir($this->backupsPath);
        $processed = [];

        foreach ($items as $item) {
            if ($item === '.' || $item === '..') continue;
            $baseName = preg_replace('/\.zip$/', '', $item);
            if (in_array($baseName, $processed)) continue;
            $processed[] = $baseName;

            $folderPath = $this->backupsPath . '/' . $baseName;
            $zipPath = $this->backupsPath . '/' . $baseName . '.zip';
            $manifestPath = $folderPath . '/manifest.json';

            $backup = [
                'name' => $baseName,
                'created_at' => null,
                'size' => 0,
                'total_documents' => 0,
                'collections_count' => 0,
                'has_zip' => file_exists($zipPath)
            ];

            if (file_exists($manifestPath)) {
                $manifest = json_decode(file_get_contents($manifestPath), true);
                $backup['created_at'] = isset($manifest['created_at']) ? Carbon::parse($manifest['created_at'])->format('d/m/Y H:i') : null;
                $backup['total_documents'] = $manifest['total_documents'] ?? 0;
                $backup['collections_count'] = count($manifest['collections'] ?? []);
            }

            if (file_exists($zipPath)) {
                $backup['size'] = $this->formatBytes(filesize($zipPath));
            }

            $backups[] = $backup;
        }

        usort($backups, fn($a, $b) => strcmp($b['name'], $a['name']));
        return $backups;
    }

    protected function createZip($backupDir, $timestamp)
    {
        $zipPath = $this->backupsPath . '/' . $timestamp . '.zip';
        $zip = new \ZipArchive();
        if ($zip->open($zipPath, \ZipArchive::CREATE | \ZipArchive::OVERWRITE) === true) {
            $files = new \RecursiveIteratorIterator(new \RecursiveDirectoryIterator($backupDir), \RecursiveIteratorIterator::LEAVES_ONLY);
            foreach ($files as $file) {
                if (!$file->isDir()) {
                    $zip->addFile($file->getRealPath(), $timestamp . '/' . substr($file->getRealPath(), strlen($backupDir) + 1));
                }
            }
            $zip->close();
        }
    }

    protected function cleanupOldBackups($keep = 10)
    {
        $items = glob($this->backupsPath . '/*');
        $folders = array_filter($items, fn($i) => is_dir($i));
        usort($folders, fn($a, $b) => filemtime($b) - filemtime($a));

        $toDelete = array_slice($folders, $keep);
        foreach ($toDelete as $folder) {
            $this->deleteDir($folder);
            $zipPath = $folder . '.zip';
            if (file_exists($zipPath)) unlink($zipPath);
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

    protected function formatBytes($bytes)
    {
        $units = ['B', 'KB', 'MB', 'GB'];
        $i = 0;
        while ($bytes >= 1024 && $i < count($units) - 1) {
            $bytes /= 1024;
            $i++;
        }
        return round($bytes, 2) . ' ' . $units[$i];
    }
}
