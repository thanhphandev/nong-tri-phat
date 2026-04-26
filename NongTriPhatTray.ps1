Add-Type -AssemblyName System.Windows.Forms
Add-Type -AssemblyName System.Drawing

# --- CONFIGURATION ---
$PHP_EXE = "$PSScriptRoot\env\php\php.exe"
$PHP_ARTISAN = "$PSScriptRoot\artisan"
$MONGO_EXE = "$PSScriptRoot\env\mongodb\bin\mongod.exe"
$DB_DATA = "$PSScriptRoot\env\data"
$LOG_PATH = "$PSScriptRoot\env\data\mongod.log"
$ICON_PATH = "$PSScriptRoot\launcher_icon.ico"
$WEB_URL = "http://127.0.0.1:8000"
$STARTUP_LINK = "$env:APPDATA\Microsoft\Windows\Start Menu\Programs\Startup\NongTriPhat.lnk"
$VBS_LAUNCHER = "$PSScriptRoot\NTP_Launcher.vbs"

# --- BACKUP CONFIGURATION ---
$BACKUP_CONFIG_PATH = "$PSScriptRoot\backup_config.json"
$BACKUP_LOG_PATH = "$PSScriptRoot\storage\logs\backup_scheduler.log"
$BACKUP_DIR = "$PSScriptRoot\storage\app\backups"

# Default backup config
$script:backupConfig = @{
    enabled  = $true
    time     = "00:00"
    keep     = 10
    lastRun  = ""
}

# --- FUNCTIONS ---
function Get-StartupStatus {
    return (Test-Path $STARTUP_LINK)
}

# --- BACKUP FUNCTIONS ---
function Load-BackupConfig {
    if (Test-Path $BACKUP_CONFIG_PATH) {
        try {
            $json = Get-Content $BACKUP_CONFIG_PATH -Raw -Encoding UTF8 | ConvertFrom-Json
            $script:backupConfig.enabled = [bool]$json.enabled
            $script:backupConfig.time = if ($json.time) { $json.time } else { "00:00" }
            $script:backupConfig.keep = if ($json.keep) { [int]$json.keep } else { 10 }
            $script:backupConfig.lastRun = if ($json.lastRun) { $json.lastRun } else { "" }
        } catch { }
    }
}

function Save-BackupConfig {
    try {
        $script:backupConfig | ConvertTo-Json | Set-Content $BACKUP_CONFIG_PATH -Encoding UTF8
    } catch { }
}

function Write-BackupLog {
    param([string]$Message, [string]$Level = "INFO")
    $timestamp = Get-Date -Format "yyyy-MM-dd HH:mm:ss"
    $logEntry = "[$timestamp] [$Level] $Message"
    try {
        $logDir = Split-Path $BACKUP_LOG_PATH -Parent
        if (-not (Test-Path $logDir)) {
            New-Item -ItemType Directory -Path $logDir -Force | Out-Null
        }
        Add-Content -Path $BACKUP_LOG_PATH -Value $logEntry -Encoding UTF8
    } catch { }
}

function Run-Backup {
    param([bool]$Silent = $false)
    if (-not $Silent) {
        $notifyIcon.ShowBalloonTip(2000, "Nông Trí Phát", "Đang tiến hành sao lưu dữ liệu...", [System.Windows.Forms.ToolTipIcon]::Info)
    }
    Write-BackupLog "========== BẮT ĐẦU SAO LƯU =========="
    try {
        $processInfo = New-Object System.Diagnostics.ProcessStartInfo
        $processInfo.FileName = $PHP_EXE
        $processInfo.Arguments = "`"$PHP_ARTISAN`" backup:run"
        $processInfo.WorkingDirectory = $PSScriptRoot
        $processInfo.RedirectStandardOutput = $true
        $processInfo.RedirectStandardError = $true
        $processInfo.UseShellExecute = $false
        $processInfo.CreateNoWindow = $true
        
        $process = New-Object System.Diagnostics.Process
        $process.StartInfo = $processInfo
        $process.Start() | Out-Null
        $process.WaitForExit(300000)
        
        $output = $process.StandardOutput.ReadToEnd()
        $errorOutput = $process.StandardError.ReadToEnd()
        $exitCode = $process.ExitCode
        
        if ($output) { Write-BackupLog "Output: $output" }
        if ($errorOutput) { Write-BackupLog "Stderr: $errorOutput" "WARN" }
        
        $script:backupConfig.lastRun = (Get-Date -Format "yyyy-MM-dd HH:mm:ss")
        Save-BackupConfig
        
        if ($exitCode -eq 0) {
            Write-BackupLog "SAO LƯU THÀNH CÔNG!" "SUCCESS"
            if (-not $Silent) {
                $notifyIcon.ShowBalloonTip(3000, "Nông Trí Phát", "Sao lưu dữ liệu thành công!", [System.Windows.Forms.ToolTipIcon]::Info)
            }
        } else {
            Write-BackupLog "SAO LƯU THẤT BẠI! (Mã lỗi: $exitCode)" "ERROR"
            $notifyIcon.ShowBalloonTip(3000, "Nông Trí Phát", "Sao lưu THẤT BẠI! Kiểm tra nhật ký.", [System.Windows.Forms.ToolTipIcon]::Error)
        }
    } catch {
        Write-BackupLog "LỖI: $($_.Exception.Message)" "ERROR"
        $notifyIcon.ShowBalloonTip(3000, "Nông Trí Phát", "Lỗi sao lưu: $($_.Exception.Message)", [System.Windows.Forms.ToolTipIcon]::Error)
    }
    Write-BackupLog "========== KẾT THÚC SAO LƯU =========="
}

function Check-BackupSchedule {
    if (-not $script:backupConfig.enabled) { return }
    $now = Get-Date
    try {
        $targetTime = [DateTime]::ParseExact($script:backupConfig.time, "HH:mm", $null)
        $scheduledToday = $now.Date.Add($targetTime.TimeOfDay)
        $diff = [Math]::Abs(($now - $scheduledToday).TotalMinutes)
        if ($diff -gt 1) { return } 
        if ($script:backupConfig.lastRun) {
            $lastRun = [DateTime]::ParseExact($script:backupConfig.lastRun, "yyyy-MM-dd HH:mm:ss", $null)
            if ($lastRun.Date -eq $now.Date) { return }
        }
        Write-BackupLog "Kích hoạt sao lưu tự động lúc $($script:backupConfig.time)"
        Run-Backup -Silent $true
    } catch { }
}

function Get-BackupCount {
    if (Test-Path $BACKUP_DIR) {
        return (Get-ChildItem $BACKUP_DIR -Filter "*.zip" -ErrorAction SilentlyContinue | Measure-Object).Count
    }
    return 0
}

function Show-BackupConfigDialog {
    $form = New-Object System.Windows.Forms.Form
    $form.Text = "Cấu hình Sao lưu"
    $form.Size = New-Object System.Drawing.Size(380, 300)
    $form.StartPosition = "CenterScreen"
    $form.FormBorderStyle = "FixedDialog"
    $form.MaximizeBox = $false
    $form.MinimizeBox = $false
    $form.TopMost = $true
    $form.Font = New-Object System.Drawing.Font("Segoe UI", 9)
    $form.BackColor = [System.Drawing.Color]::White
    
    $lblHeader = New-Object System.Windows.Forms.Label
    $lblHeader.Text = "SAO LƯU TỰ ĐỘNG"
    $lblHeader.Font = New-Object System.Drawing.Font("Segoe UI", 11, [System.Drawing.FontStyle]::Bold)
    $lblHeader.Location = New-Object System.Drawing.Point(15, 15)
    $lblHeader.AutoSize = $true
    $form.Controls.Add($lblHeader)
    
    $chkEnabled = New-Object System.Windows.Forms.CheckBox
    $chkEnabled.Text = " Kích hoạt tự động hàng ngày"
    $chkEnabled.Location = New-Object System.Drawing.Point(20, 50)
    $chkEnabled.AutoSize = $true
    $chkEnabled.Checked = $script:backupConfig.enabled
    $form.Controls.Add($chkEnabled)
    
    $lblTime = New-Object System.Windows.Forms.Label
    $lblTime.Text = "Giờ thực hiện:"
    $lblTime.Location = New-Object System.Drawing.Point(20, 85)
    $lblTime.AutoSize = $true
    $form.Controls.Add($lblTime)
    
    $timePicker = New-Object System.Windows.Forms.DateTimePicker
    $timePicker.Format = "Custom"
    $timePicker.CustomFormat = "HH:mm"
    $timePicker.ShowUpDown = $true
    $timePicker.Location = New-Object System.Drawing.Point(140, 81)
    $timePicker.Size = New-Object System.Drawing.Size(80, 25)
    try { $timePicker.Value = [DateTime]::ParseExact($script:backupConfig.time, "HH:mm", $null) } catch { $timePicker.Value = [DateTime]::Today }
    $form.Controls.Add($timePicker)
    
    $lblKeep = New-Object System.Windows.Forms.Label
    $lblKeep.Text = "Số bản giữ lại:"
    $lblKeep.Location = New-Object System.Drawing.Point(20, 120)
    $lblKeep.AutoSize = $true
    $form.Controls.Add($lblKeep)
    
    $numKeep = New-Object System.Windows.Forms.NumericUpDown
    $numKeep.Location = New-Object System.Drawing.Point(140, 116)
    $numKeep.Size = New-Object System.Drawing.Size(80, 25)
    $numKeep.Minimum = 1; $numKeep.Maximum = 100; $numKeep.Value = $script:backupConfig.keep
    $form.Controls.Add($numKeep)

    $lblInfo = New-Object System.Windows.Forms.Label
    $lblInfo.Text = "Số bản hiện có: $(Get-BackupCount)`nLần cuối: $(if($script:backupConfig.lastRun){$script:backupConfig.lastRun}else{'Chưa có'})"
    $lblInfo.Location = New-Object System.Drawing.Point(20, 155)
    $lblInfo.Size = New-Object System.Drawing.Size(330, 45)
    $lblInfo.ForeColor = [System.Drawing.Color]::Gray
    $form.Controls.Add($lblInfo)
    
    $btnSave = New-Object System.Windows.Forms.Button
    $btnSave.Text = "Lưu"
    $btnSave.Location = New-Object System.Drawing.Point(160, 215)
    $btnSave.Size = New-Object System.Drawing.Size(90, 30)
    $btnSave.BackColor = [System.Drawing.Color]::FromArgb(0, 120, 215)
    $btnSave.ForeColor = [System.Drawing.Color]::White
    $btnSave.FlatStyle = "Flat"
    $btnSave.add_Click({
        $script:backupConfig.enabled = $chkEnabled.Checked
        $script:backupConfig.time = $timePicker.Value.ToString("HH:mm")
        $script:backupConfig.keep = [int]$numKeep.Value
        Save-BackupConfig
        $btnBackupAuto.Checked = $script:backupConfig.enabled
        $form.Close()
    })
    $form.Controls.Add($btnSave)
    
    $btnCancel = New-Object System.Windows.Forms.Button
    $btnCancel.Text = "Hủy"
    $btnCancel.Location = New-Object System.Drawing.Point(260, 215)
    $btnCancel.Size = New-Object System.Drawing.Size(80, 30)
    $btnCancel.FlatStyle = "Flat"
    $btnCancel.add_Click({ $form.Close() })
    $form.Controls.Add($btnCancel)
    
    $form.ShowDialog() | Out-Null
}

# MAIN
Load-BackupConfig
if (-not (Test-Path $DB_DATA)) { try { New-Item -ItemType Directory -Path $DB_DATA -ErrorAction Stop | Out-Null } catch { Exit } }
Remove-Item "$DB_DATA\mongod.lock", "$DB_DATA\WiredTiger.lock" -Force -ErrorAction SilentlyContinue

$mongoProc = Start-Process $MONGO_EXE -ArgumentList "--dbpath `"$DB_DATA`" --bind_ip 127.0.0.1 --logpath `"$LOG_PATH`"" -WindowStyle Hidden -PassThru
Start-Sleep -Seconds 2
$phpProc = Start-Process $PHP_EXE -ArgumentList "`"$PHP_ARTISAN`" serve --host=127.0.0.1 --port=8000" -WindowStyle Hidden -PassThru -WorkingDirectory $PSScriptRoot

$notifyIcon = New-Object System.Windows.Forms.NotifyIcon
$notifyIcon.Icon = if (Test-Path $ICON_PATH) { try { New-Object System.Drawing.Icon($ICON_PATH) } catch { [System.Drawing.SystemIcons]::Application } } else { [System.Drawing.SystemIcons]::Application }
$notifyIcon.Text = "Nông Trí Phát"
$notifyIcon.Visible = $true

$contextMenu = New-Object System.Windows.Forms.ContextMenuStrip
$contextMenu.Font = New-Object System.Drawing.Font("Segoe UI", 9)

$btnWeb = $contextMenu.Items.Add("Mở Trang quản lý")
$btnFolder = $contextMenu.Items.Add("Thư mục ứng dụng")
$btnLog = $contextMenu.Items.Add("Nhật ký CSDL")
$contextMenu.Items.Add("-") | Out-Null

$btnStartup = New-Object System.Windows.Forms.ToolStripMenuItem("Khởi động cùng Windows")
$btnStartup.Checked = Get-StartupStatus
$contextMenu.Items.Add($btnStartup) | Out-Null

$btnBackupAuto = New-Object System.Windows.Forms.ToolStripMenuItem("Tự động sao lưu")
$btnBackupAuto.Checked = $script:backupConfig.enabled
$contextMenu.Items.Add($btnBackupAuto) | Out-Null
$contextMenu.Items.Add("-") | Out-Null

$btnBackupNow = $contextMenu.Items.Add("Sao lưu ngay")
$btnBackupConfig = $contextMenu.Items.Add("Cấu hình sao lưu...")
$btnBackupFolder = $contextMenu.Items.Add("Thư mục sao lưu")
$btnBackupLog = $contextMenu.Items.Add("Nhật ký sao lưu")
$contextMenu.Items.Add("-") | Out-Null
$btnExit = $contextMenu.Items.Add("Thoát")

$btnWeb.add_Click({ Start-Process $WEB_URL })
$btnFolder.add_Click({ Start-Process "explorer.exe" -ArgumentList "$PSScriptRoot" })
$btnLog.add_Click({ Start-Process "notepad.exe" -ArgumentList "$LOG_PATH" })

$btnStartup.add_Click({
    if (Test-Path $STARTUP_LINK) {
        Remove-Item $STARTUP_LINK
        $btnStartup.Checked = $false
    } else {
        $shell = New-Object -ComObject WScript.Shell
        $shortcut = $shell.CreateShortcut($STARTUP_LINK)
        $shortcut.TargetPath = "wscript.exe"; $shortcut.Arguments = "`"$VBS_LAUNCHER`""; $shortcut.Save()
        $btnStartup.Checked = $true
    }
})

$btnBackupAuto.add_Click({
    $script:backupConfig.enabled = -not $script:backupConfig.enabled
    Save-BackupConfig
    $btnBackupAuto.Checked = $script:backupConfig.enabled
})

$btnBackupNow.add_Click({
    if ([System.Windows.Forms.MessageBox]::Show("Bạn muốn sao lưu dữ liệu ngay?", "Xác nhận", "YesNo", "Question") -eq "Yes") {
        Run-Backup -Silent $false
    }
})

$btnBackupConfig.add_Click({ Show-BackupConfigDialog })
$btnBackupFolder.add_Click({ if (-not (Test-Path $BACKUP_DIR)) { New-Item -ItemType Directory -Path $BACKUP_DIR -Force | Out-Null }; Start-Process "explorer.exe" -ArgumentList "$BACKUP_DIR" })
$btnBackupLog.add_Click({ Start-Process "notepad.exe" -ArgumentList "$BACKUP_LOG_PATH" })

$btnExit.add_Click({
    if ($phpProc) { Stop-Process -Id $phpProc.Id -Force -ErrorAction SilentlyContinue }
    if ($mongoProc) { Stop-Process -Id $mongoProc.Id -Force -ErrorAction SilentlyContinue }
    $backupTimer.Stop(); $notifyIcon.Visible = $false; [System.Windows.Forms.Application]::Exit(); Exit
})

$notifyIcon.ContextMenuStrip = $contextMenu
$backupTimer = New-Object System.Windows.Forms.Timer
$backupTimer.Interval = 60000; $backupTimer.add_Tick({ Check-BackupSchedule }); $backupTimer.Start()
$notifyIcon.ShowBalloonTip(3000, "Nông Trí Phát", "Hệ thống đã sẵn sàng!", [System.Windows.Forms.ToolTipIcon]::Info)
[System.Windows.Forms.Application]::Run()
