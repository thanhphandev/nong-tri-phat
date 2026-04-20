# ============================================================================
# NongTriPhat - Auto Backup Script
# Chạy tự động bằng Windows Task Scheduler
# Script này sẽ backup database MongoDB thông qua Laravel Artisan command
# ============================================================================

# --- CẤU HÌNH ---
$PROJECT_DIR = $PSScriptRoot
$PHP_EXE = "$PROJECT_DIR\env\php\php.exe"
$ARTISAN = "$PROJECT_DIR\artisan"
$LOG_FILE = "$PROJECT_DIR\storage\logs\backup_scheduler.log"

# Nếu không tìm thấy PHP portable, thử dùng PHP hệ thống
if (-not (Test-Path $PHP_EXE)) {
    $PHP_EXE = "php"
}

# --- HÀM GHI LOG ---
function Write-BackupLog {
    param([string]$Message, [string]$Level = "INFO")
    $timestamp = Get-Date -Format "yyyy-MM-dd HH:mm:ss"
    $logEntry = "[$timestamp] [$Level] $Message"
    
    # Tạo thư mục log nếu chưa có
    $logDir = Split-Path $LOG_FILE -Parent
    if (-not (Test-Path $logDir)) {
        New-Item -ItemType Directory -Path $logDir -Force | Out-Null
    }
    
    Add-Content -Path $LOG_FILE -Value $logEntry -Encoding UTF8
    Write-Host $logEntry
}

# --- KIỂM TRA MONGODB ĐANG CHẠY ---
function Test-MongoDBRunning {
    try {
        $mongoProcess = Get-Process "mongod" -ErrorAction SilentlyContinue
        return ($null -ne $mongoProcess)
    } catch {
        return $false
    }
}

# --- BẮT ĐẦU BACKUP ---
Write-BackupLog "========== BAT DAU BACKUP TU DONG =========="
Write-BackupLog "Thu muc du an: $PROJECT_DIR"
Write-BackupLog "PHP: $PHP_EXE"

# Kiểm tra file cần thiết
if (-not (Test-Path $ARTISAN)) {
    Write-BackupLog "LOI: Khong tim thay file artisan tai $ARTISAN" "ERROR"
    Exit 1
}

# Kiểm tra MongoDB
if (-not (Test-MongoDBRunning)) {
    Write-BackupLog "CANH BAO: MongoDB chua chay. Dang thu khoi dong..." "WARN"
    
    $MONGO_EXE = "$PROJECT_DIR\env\mongodb\bin\mongod.exe"
    $DB_DATA = "$PROJECT_DIR\env\data"
    $MONGO_LOG = "$DB_DATA\mongod.log"
    
    if (Test-Path $MONGO_EXE) {
        # Xóa lock files cũ
        Remove-Item "$DB_DATA\mongod.lock" -Force -ErrorAction SilentlyContinue
        Remove-Item "$DB_DATA\WiredTiger.lock" -Force -ErrorAction SilentlyContinue
        
        $mongoArgs = "--dbpath `"$DB_DATA`" --bind_ip 127.0.0.1 --logpath `"$MONGO_LOG`""
        Start-Process $MONGO_EXE -ArgumentList $mongoArgs -WindowStyle Hidden
        Start-Sleep -Seconds 3
        
        if (Test-MongoDBRunning) {
            Write-BackupLog "MongoDB da khoi dong thanh cong"
            $script:startedMongo = $true
        } else {
            Write-BackupLog "LOI: Khong the khoi dong MongoDB" "ERROR"
            Exit 1
        }
    } else {
        Write-BackupLog "LOI: Khong tim thay mongod.exe tai $MONGO_EXE" "ERROR"
        Exit 1
    }
} else {
    Write-BackupLog "MongoDB dang hoat dong"
}

# Chạy backup command
try {
    Write-BackupLog "Dang chay backup:run..."
    
    $processInfo = New-Object System.Diagnostics.ProcessStartInfo
    $processInfo.FileName = $PHP_EXE
    $processInfo.Arguments = "`"$ARTISAN`" backup:run"
    $processInfo.WorkingDirectory = $PROJECT_DIR
    $processInfo.RedirectStandardOutput = $true
    $processInfo.RedirectStandardError = $true
    $processInfo.UseShellExecute = $false
    $processInfo.CreateNoWindow = $true
    
    $process = New-Object System.Diagnostics.Process
    $process.StartInfo = $processInfo
    $process.Start() | Out-Null
    $process.WaitForExit(300000) # Timeout 5 phút
    
    $output = $process.StandardOutput.ReadToEnd()
    $errorOutput = $process.StandardError.ReadToEnd()
    $exitCode = $process.ExitCode
    
    if ($output) {
        Write-BackupLog "Output: $output"
    }
    if ($errorOutput) {
        Write-BackupLog "Error Output: $errorOutput" "WARN"
    }
    
    if ($exitCode -eq 0) {
        Write-BackupLog "BACKUP THANH CONG! (Exit code: 0)" "SUCCESS"
    } else {
        Write-BackupLog "BACKUP THAT BAI! (Exit code: $exitCode)" "ERROR"
    }
    
} catch {
    Write-BackupLog "LOI NGOAI LE: $($_.Exception.Message)" "ERROR"
}

Write-BackupLog "========== KET THUC BACKUP TU DONG =========="
Write-BackupLog ""
