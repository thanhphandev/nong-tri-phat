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
    if (Test-Path $STARTUP_LINK) { return "BAT" } else { return "TAT" }
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
        } catch {
            # File corrupted, use defaults
        }
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
        $notifyIcon.ShowBalloonTip(2000, "Nong Tri Phat", "Dang tien hanh backup du lieu...", [System.Windows.Forms.ToolTipIcon]::Info)
    }
    Write-BackupLog "========== BAT DAU BACKUP =========="
    
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
        
        # Update last run time
        $script:backupConfig.lastRun = (Get-Date -Format "yyyy-MM-dd HH:mm:ss")
        Save-BackupConfig
        
        if ($exitCode -eq 0) {
            Write-BackupLog "BACKUP THANH CONG!" "SUCCESS"
            if (-not $Silent) {
                $notifyIcon.ShowBalloonTip(3000, "Nong Tri Phat", "Backup du lieu thanh cong!`nThu muc: storage\app\backups", [System.Windows.Forms.ToolTipIcon]::Info)
            }
        } else {
            Write-BackupLog "BACKUP THAT BAI! (Exit code: $exitCode)" "ERROR"
            $notifyIcon.ShowBalloonTip(3000, "Nong Tri Phat", "Backup THAT BAI! Xem log de biet chi tiet.", [System.Windows.Forms.ToolTipIcon]::Error)
        }
    } catch {
        Write-BackupLog "LOI: $($_.Exception.Message)" "ERROR"
        $notifyIcon.ShowBalloonTip(3000, "Nong Tri Phat", "Backup LOI: $($_.Exception.Message)", [System.Windows.Forms.ToolTipIcon]::Error)
    }
    
    Write-BackupLog "========== KET THUC BACKUP =========="
}

function Check-BackupSchedule {
    if (-not $script:backupConfig.enabled) { return }
    
    $now = Get-Date
    $targetTime = [DateTime]::ParseExact($script:backupConfig.time, "HH:mm", $null)
    $scheduledToday = $now.Date.Add($targetTime.TimeOfDay)
    
    # Check if we're within the backup window (target time ± 2 minutes)
    $diff = [Math]::Abs(($now - $scheduledToday).TotalMinutes)
    if ($diff -gt 2) { return }
    
    # Check if already ran today
    if ($script:backupConfig.lastRun) {
        try {
            $lastRun = [DateTime]::ParseExact($script:backupConfig.lastRun, "yyyy-MM-dd HH:mm:ss", $null)
            if ($lastRun.Date -eq $now.Date) { return }
        } catch { }
    }
    
    # Run backup
    Write-BackupLog "Lich backup tu dong kich hoat luc $($script:backupConfig.time)"
    Run-Backup -Silent $true
}

function Get-BackupStatusText {
    if ($script:backupConfig.enabled) {
        $status = "BAT - Hang ngay luc $($script:backupConfig.time)"
    } else {
        $status = "TAT"
    }
    if ($script:backupConfig.lastRun) {
        $status += " (Lan cuoi: $($script:backupConfig.lastRun))"
    }
    return $status
}

function Get-BackupCount {
    if (Test-Path $BACKUP_DIR) {
        $count = (Get-ChildItem $BACKUP_DIR -Filter "*.zip" -ErrorAction SilentlyContinue | Measure-Object).Count
        return $count
    }
    return 0
}

function Show-BackupConfigDialog {
    $form = New-Object System.Windows.Forms.Form
    $form.Text = "Cau hinh Backup Tu Dong"
    $form.Size = New-Object System.Drawing.Size(420, 320)
    $form.StartPosition = "CenterScreen"
    $form.FormBorderStyle = "FixedDialog"
    $form.MaximizeBox = $false
    $form.MinimizeBox = $false
    $form.TopMost = $true
    $form.Font = New-Object System.Drawing.Font("Segoe UI", 9)
    
    # Header
    $lblHeader = New-Object System.Windows.Forms.Label
    $lblHeader.Text = "CAU HINH BACKUP TU DONG"
    $lblHeader.Font = New-Object System.Drawing.Font("Segoe UI", 11, [System.Drawing.FontStyle]::Bold)
    $lblHeader.Location = New-Object System.Drawing.Point(15, 15)
    $lblHeader.AutoSize = $true
    $form.Controls.Add($lblHeader)
    
    # Enable checkbox
    $chkEnabled = New-Object System.Windows.Forms.CheckBox
    $chkEnabled.Text = "  Bat backup tu dong hang ngay"
    $chkEnabled.Location = New-Object System.Drawing.Point(20, 55)
    $chkEnabled.AutoSize = $true
    $chkEnabled.Checked = $script:backupConfig.enabled
    $form.Controls.Add($chkEnabled)
    
    # Time label
    $lblTime = New-Object System.Windows.Forms.Label
    $lblTime.Text = "Gio chay backup:"
    $lblTime.Location = New-Object System.Drawing.Point(20, 95)
    $lblTime.AutoSize = $true
    $form.Controls.Add($lblTime)
    
    # Time picker
    $timePicker = New-Object System.Windows.Forms.DateTimePicker
    $timePicker.Format = "Custom"
    $timePicker.CustomFormat = "HH:mm"
    $timePicker.ShowUpDown = $true
    $timePicker.Location = New-Object System.Drawing.Point(160, 91)
    $timePicker.Size = New-Object System.Drawing.Size(80, 25)
    try {
        $t = [DateTime]::ParseExact($script:backupConfig.time, "HH:mm", $null)
        $timePicker.Value = $t
    } catch {
        $timePicker.Value = [DateTime]::Today
    }
    $form.Controls.Add($timePicker)
    
    # Keep count label
    $lblKeep = New-Object System.Windows.Forms.Label
    $lblKeep.Text = "Giu lai so ban backup:"
    $lblKeep.Location = New-Object System.Drawing.Point(20, 130)
    $lblKeep.AutoSize = $true
    $form.Controls.Add($lblKeep)
    
    # Keep count
    $numKeep = New-Object System.Windows.Forms.NumericUpDown
    $numKeep.Location = New-Object System.Drawing.Point(160, 126)
    $numKeep.Size = New-Object System.Drawing.Size(80, 25)
    $numKeep.Minimum = 3
    $numKeep.Maximum = 50
    $numKeep.Value = $script:backupConfig.keep
    $form.Controls.Add($numKeep)

    # Info label
    $lblInfo = New-Object System.Windows.Forms.Label
    $backupCount = Get-BackupCount
    $infoText = "Thu muc backup: storage\app\backups\"
    if ($script:backupConfig.lastRun) {
        $infoText += "`nLan backup cuoi: $($script:backupConfig.lastRun)"
    }
    $infoText += "`nSo ban backup hien co: $backupCount"
    $lblInfo.Text = $infoText
    $lblInfo.Location = New-Object System.Drawing.Point(20, 170)
    $lblInfo.Size = New-Object System.Drawing.Size(370, 55)
    $lblInfo.ForeColor = [System.Drawing.Color]::Gray
    $form.Controls.Add($lblInfo)
    
    # Save button
    $btnSave = New-Object System.Windows.Forms.Button
    $btnSave.Text = "Luu cau hinh"
    $btnSave.Location = New-Object System.Drawing.Point(150, 235)
    $btnSave.Size = New-Object System.Drawing.Size(120, 35)
    $btnSave.BackColor = [System.Drawing.Color]::FromArgb(15, 128, 184)
    $btnSave.ForeColor = [System.Drawing.Color]::White
    $btnSave.FlatStyle = "Flat"
    $btnSave.add_Click({
        $script:backupConfig.enabled = $chkEnabled.Checked
        $script:backupConfig.time = $timePicker.Value.ToString("HH:mm")
        $script:backupConfig.keep = [int]$numKeep.Value
        Save-BackupConfig
        
        # Update menu text
        $script:btnBackupStatus.Text = "Backup tu dong: $(Get-BackupStatusText)"
        
        $notifyIcon.ShowBalloonTip(2000, "Nong Tri Phat", "Da luu cau hinh backup!", [System.Windows.Forms.ToolTipIcon]::Info)
        $form.Close()
    })
    $form.Controls.Add($btnSave)
    
    # Cancel button
    $btnCancel = New-Object System.Windows.Forms.Button
    $btnCancel.Text = "Huy"
    $btnCancel.Location = New-Object System.Drawing.Point(280, 235)
    $btnCancel.Size = New-Object System.Drawing.Size(100, 35)
    $btnCancel.FlatStyle = "Flat"
    $btnCancel.add_Click({ $form.Close() })
    $form.Controls.Add($btnCancel)
    
    $form.ShowDialog() | Out-Null
}

# ============================================================================
# MAIN - START SERVICES
# ============================================================================

# Load backup config
Load-BackupConfig

if (-not (Test-Path $DB_DATA)) { 
    try {
        New-Item -ItemType Directory -Path $DB_DATA -ErrorAction Stop | Out-Null 
    } catch {
        [System.Windows.Forms.MessageBox]::Show("Khong the tao thu muc data tai: $DB_DATA", "Loi He Thong")
        Exit
    }
}

# 1.1 CLEANUP STALE LOCKS (Fix Exit Code 100)
Remove-Item "$DB_DATA\mongod.lock" -Force -ErrorAction SilentlyContinue
Remove-Item "$DB_DATA\WiredTiger.lock" -Force -ErrorAction SilentlyContinue

# 2. START SERVICES (HIDDEN)
# Start MongoDB
$mongoArgs = "--dbpath `"$DB_DATA`" --bind_ip 127.0.0.1 --logpath `"$LOG_PATH`""
$mongoProc = Start-Process $MONGO_EXE -ArgumentList $mongoArgs -WindowStyle Hidden -PassThru

# Wait 2 seconds for MongoDB to initialize
Start-Sleep -Seconds 2

# Start PHP Server (Laravel)
$phpArgs = "`"$PHP_ARTISAN`" serve --host=127.0.0.1 --port=8000"
$phpProc = Start-Process $PHP_EXE -ArgumentList $phpArgs -WindowStyle Hidden -PassThru -WorkingDirectory $PSScriptRoot

# 3. CREATE TRAY ICON
$notifyIcon = New-Object System.Windows.Forms.NotifyIcon
if (Test-Path $ICON_PATH) {
    try {
        $notifyIcon.Icon = New-Object System.Drawing.Icon($ICON_PATH)
    } catch {
        $notifyIcon.Icon = [System.Drawing.SystemIcons]::Application
    }
} else {
    $notifyIcon.Icon = [System.Drawing.SystemIcons]::Application
}
$notifyIcon.Text = "NongTriPhat System - Dang Hoat Dong"
$notifyIcon.Visible = $true

# 4. CREATE CONTEXT MENU
$contextMenu = New-Object System.Windows.Forms.ContextMenuStrip
$btnWeb = $contextMenu.Items.Add("Web UI (Mo trinh duyet)")
$btnFolder = $contextMenu.Items.Add("Mo thu muc du lieu")
$btnLog = $contextMenu.Items.Add("Xem Log CSDL (MongoDB)")
$btnStartup = $contextMenu.Items.Add("Chay cung Windows: $(Get-StartupStatus)")
$contextMenu.Items.Add("-") | Out-Null

# --- BACKUP MENU ITEMS ---
$script:btnBackupStatus = $contextMenu.Items.Add("Backup tu dong: $(Get-BackupStatusText)")
$script:btnBackupStatus.Enabled = $false
$script:btnBackupStatus.ForeColor = [System.Drawing.Color]::Gray

$btnBackupNow = $contextMenu.Items.Add("  Backup ngay")
$btnBackupNow.Image = [System.Drawing.SystemIcons]::Information.ToBitmap()
$btnBackupConfig = $contextMenu.Items.Add("  Cau hinh backup...")
$btnBackupFolder = $contextMenu.Items.Add("  Mo thu muc backup")
$btnBackupLog = $contextMenu.Items.Add("  Xem log backup")

$contextMenu.Items.Add("-") | Out-Null
$btnExit = $contextMenu.Items.Add("Thoat he thong (Exit)")

# Click Events
$btnWeb.add_Click({ Start-Process $WEB_URL })
$btnFolder.add_Click({ Start-Process "explorer.exe" -ArgumentList "$PSScriptRoot" })
$btnLog.add_Click({ Start-Process "notepad.exe" -ArgumentList "$LOG_PATH" })

# Startup Toggle Event
$btnStartup.add_Click({
    if (Test-Path $STARTUP_LINK) {
        Remove-Item $STARTUP_LINK
        $notifyIcon.ShowBalloonTip(2000, "Nong Tri Phat", "Da TAT khoi dong cung Windows", [System.Windows.Forms.ToolTipIcon]::Info)
    } else {
        $shell = New-Object -ComObject WScript.Shell
        $shortcut = $shell.CreateShortcut($STARTUP_LINK)
        $shortcut.TargetPath = "wscript.exe"
        $shortcut.Arguments = "`"$VBS_LAUNCHER`""
        $shortcut.WorkingDirectory = $PSScriptRoot
        $shortcut.WindowStyle = 7 # Minimized
        $shortcut.Save()
        $notifyIcon.ShowBalloonTip(2000, "Nong Tri Phat", "Da BAT khoi dong cung Windows", [System.Windows.Forms.ToolTipIcon]::Info)
    }
    $btnStartup.Text = "Chay cung Windows: $(Get-StartupStatus)"
})

# --- BACKUP CLICK EVENTS ---
$btnBackupNow.add_Click({
    $result = [System.Windows.Forms.MessageBox]::Show(
        "Ban co muon backup du lieu ngay bay gio?",
        "Xac nhan Backup",
        [System.Windows.Forms.MessageBoxButtons]::YesNo,
        [System.Windows.Forms.MessageBoxIcon]::Question
    )
    if ($result -eq "Yes") {
        Run-Backup -Silent $false
        $script:btnBackupStatus.Text = "Backup tu dong: $(Get-BackupStatusText)"
    }
})

$btnBackupConfig.add_Click({
    Show-BackupConfigDialog
})

$btnBackupFolder.add_Click({
    if (-not (Test-Path $BACKUP_DIR)) {
        New-Item -ItemType Directory -Path $BACKUP_DIR -Force | Out-Null
    }
    Start-Process "explorer.exe" -ArgumentList "$BACKUP_DIR"
})

$btnBackupLog.add_Click({
    if (-not (Test-Path $BACKUP_LOG_PATH)) {
        $logDir = Split-Path $BACKUP_LOG_PATH -Parent
        if (-not (Test-Path $logDir)) {
            New-Item -ItemType Directory -Path $logDir -Force | Out-Null
        }
        Set-Content -Path $BACKUP_LOG_PATH -Value "[LOG] Chua co log backup nao." -Encoding UTF8
    }
    Start-Process "notepad.exe" -ArgumentList "$BACKUP_LOG_PATH"
})

$btnExit.add_Click({
    # Clean Shutdown
    if ($phpProc) { Stop-Process -Id $phpProc.Id -Force -ErrorAction SilentlyContinue }
    if ($mongoProc) { Stop-Process -Id $mongoProc.Id -Force -ErrorAction SilentlyContinue }
    
    $backupTimer.Stop()
    $backupTimer.Dispose()
    $notifyIcon.Visible = $false
    $notifyIcon.Dispose()
    [System.Windows.Forms.Application]::Exit()
    Exit
})

$notifyIcon.ContextMenuStrip = $contextMenu

# 5. BACKUP SCHEDULER TIMER (check every 60 seconds)
$backupTimer = New-Object System.Windows.Forms.Timer
$backupTimer.Interval = 60000  # 1 minute
$backupTimer.add_Tick({ Check-BackupSchedule })
$backupTimer.Start()

# Show Balloon Notification
$startMsg = "He thong da san sang tai cong 8000!"
if ($script:backupConfig.enabled) {
    $startMsg += "`nBackup tu dong: $($script:backupConfig.time) hang ngay"
}
$notifyIcon.ShowBalloonTip(3000, "Nong Tri Phat", $startMsg, [System.Windows.Forms.ToolTipIcon]::Info)

# Run Loop to keep Tray Icon alive
[System.Windows.Forms.Application]::Run()
