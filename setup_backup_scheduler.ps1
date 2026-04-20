# ============================================================================
# NongTriPhat - CÀI ĐẶT BACKUP TỰ ĐỘNG (Windows Task Scheduler)
# Chạy script này với quyền Administrator để tạo lịch backup tự động
# ============================================================================

param(
    [string]$BackupTime = "00:00",
    [switch]$Remove,
    [switch]$RunNow
)

$TASK_NAME = "NongTriPhat_AutoBackup"
$PROJECT_DIR = $PSScriptRoot
$BACKUP_SCRIPT = "$PROJECT_DIR\auto_backup.ps1"

# --- XÓA TASK CŨ ---
if ($Remove) {
    try {
        Unregister-ScheduledTask -TaskName $TASK_NAME -Confirm:$false -ErrorAction Stop
        Write-Host "[OK] Da xoa lich backup tu dong: $TASK_NAME" -ForegroundColor Green
    } catch {
        Write-Host "[!] Khong tim thay task: $TASK_NAME" -ForegroundColor Yellow
    }
    Exit
}

# --- CHAY NGAY LẬP TỨC ---
if ($RunNow) {
    Write-Host "[*] Dang chay backup ngay..." -ForegroundColor Cyan
    & $BACKUP_SCRIPT
    Exit
}

# --- KIỂM TRA ---
if (-not (Test-Path $BACKUP_SCRIPT)) {
    Write-Host "[LOI] Khong tim thay file: $BACKUP_SCRIPT" -ForegroundColor Red
    Exit 1
}

# --- TẠO SCHEDULED TASK ---
Write-Host "============================================" -ForegroundColor Cyan
Write-Host "  NongTriPhat - CAI DAT BACKUP TU DONG" -ForegroundColor Cyan
Write-Host "============================================" -ForegroundColor Cyan
Write-Host ""
Write-Host "Thu muc du an : $PROJECT_DIR" -ForegroundColor White
Write-Host "Script backup : $BACKUP_SCRIPT" -ForegroundColor White
Write-Host "Gio chay      : $BackupTime (hang ngay)" -ForegroundColor White
Write-Host ""

# Xóa task cũ nếu tồn tại
$existingTask = Get-ScheduledTask -TaskName $TASK_NAME -ErrorAction SilentlyContinue
if ($existingTask) {
    Unregister-ScheduledTask -TaskName $TASK_NAME -Confirm:$false
    Write-Host "[*] Da xoa task cu: $TASK_NAME" -ForegroundColor Yellow
}

# Tạo action
$action = New-ScheduledTaskAction `
    -Execute "powershell.exe" `
    -Argument "-NoProfile -ExecutionPolicy Bypass -WindowStyle Hidden -File `"$BACKUP_SCRIPT`"" `
    -WorkingDirectory $PROJECT_DIR

# Tạo trigger (hàng ngày)
$trigger = New-ScheduledTaskTrigger -Daily -At $BackupTime

# Tạo settings
$settings = New-ScheduledTaskSettingsSet `
    -AllowStartIfOnBatteries `
    -DontStopIfGoingOnBatteries `
    -StartWhenAvailable `
    -WakeToRun:$false `
    -ExecutionTimeLimit (New-TimeSpan -Minutes 30) `
    -RestartCount 2 `
    -RestartInterval (New-TimeSpan -Minutes 5)

# Đăng ký task
try {
    Register-ScheduledTask `
        -TaskName $TASK_NAME `
        -Action $action `
        -Trigger $trigger `
        -Settings $settings `
        -Description "Tu dong backup du lieu NongTriPhat hang ngay luc $BackupTime" `
        -RunLevel Limited `
        -ErrorAction Stop | Out-Null

    Write-Host ""
    Write-Host "============================================" -ForegroundColor Green
    Write-Host "  CAI DAT THANH CONG!" -ForegroundColor Green
    Write-Host "============================================" -ForegroundColor Green
    Write-Host ""
    Write-Host "  Task Name : $TASK_NAME" -ForegroundColor White
    Write-Host "  Lich chay : Hang ngay luc $BackupTime" -ForegroundColor White
    Write-Host "  Backup luu tai: $PROJECT_DIR\storage\app\backups\" -ForegroundColor White
    Write-Host "  Log file  : $PROJECT_DIR\storage\logs\backup_scheduler.log" -ForegroundColor White
    Write-Host ""
    Write-Host "  Cac lenh quan ly:" -ForegroundColor Cyan
    Write-Host "    Chay ngay : .\setup_backup_scheduler.ps1 -RunNow" -ForegroundColor Gray
    Write-Host "    Doi gio   : .\setup_backup_scheduler.ps1 -BackupTime '06:00'" -ForegroundColor Gray
    Write-Host "    Xoa lich  : .\setup_backup_scheduler.ps1 -Remove" -ForegroundColor Gray
    Write-Host "    Xem task  : Get-ScheduledTask -TaskName '$TASK_NAME'" -ForegroundColor Gray
    Write-Host ""
} catch {
    Write-Host ""
    Write-Host "[LOI] Khong the tao scheduled task!" -ForegroundColor Red
    Write-Host "Chi tiet: $($_.Exception.Message)" -ForegroundColor Red
    Write-Host ""
    Write-Host "Hay thu chay lai voi quyen Administrator:" -ForegroundColor Yellow
    Write-Host "  Right-click PowerShell -> Run as Administrator" -ForegroundColor Yellow
    Write-Host "  cd `"$PROJECT_DIR`"" -ForegroundColor Yellow
    Write-Host "  .\setup_backup_scheduler.ps1" -ForegroundColor Yellow
}
