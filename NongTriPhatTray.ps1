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

# --- CLOUD BACKUP (RCLONE) ---
$RCLONE_EXE = "$PSScriptRoot\env\rclone\rclone.exe"
$RCLONE_CONF = "$PSScriptRoot\env\rclone\rclone.conf"

# Default backup config
$script:backupConfig = @{
    enabled      = $true
    time         = "00:00"
    keep         = 10
    lastRun      = ""
    cloudEnabled = $false
    cloudRemote  = "gdrive"
    cloudFolder  = "NongTriPhat-Backups"
    cloudKeep    = 10
    cloudNotify  = $true
    lastCloudRun = ""
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
            # Cloud fields
            if ($null -ne $json.cloudEnabled) { $script:backupConfig.cloudEnabled = [bool]$json.cloudEnabled }
            if ($json.cloudRemote) { $script:backupConfig.cloudRemote = $json.cloudRemote }
            if ($json.cloudFolder) { $script:backupConfig.cloudFolder = $json.cloudFolder }
            if ($null -ne $json.cloudKeep) { $script:backupConfig.cloudKeep = [int]$json.cloudKeep }
            if ($null -ne $json.cloudNotify) { $script:backupConfig.cloudNotify = [bool]$json.cloudNotify } else { $script:backupConfig.cloudNotify = $true }
            if ($json.lastCloudRun) { $script:backupConfig.lastCloudRun = $json.lastCloudRun }
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
            # Auto upload to cloud if enabled
            if ($script:backupConfig.cloudEnabled) {
                Write-BackupLog "Bắt đầu đồng bộ lên cloud..."
                Upload-ToCloud
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

# --- CLOUD BACKUP FUNCTIONS ---
function Test-RcloneReady {
    if (-not (Test-Path $RCLONE_EXE)) { return $false }
    if (-not (Test-Path $RCLONE_CONF)) { return $false }
    $remotes = & $RCLONE_EXE listremotes --config $RCLONE_CONF 2>$null
    return ($remotes -match "$($script:backupConfig.cloudRemote):")
}

function Upload-ToCloud {
    param([string]$ZipFile = "")
    
    if (-not (Test-Path $RCLONE_EXE)) {
        Write-BackupLog "CLOUD: rclone.exe không tồn tại. Chạy setup_cloud_backup.ps1" "ERROR"
        if ($script:backupConfig.cloudNotify) {
            $notifyIcon.ShowBalloonTip(3000, "Nông Trí Phát", "Chưa cài rclone! Chạy setup_cloud_backup.ps1", [System.Windows.Forms.ToolTipIcon]::Warning)
        }
        return $false
    }
    if (-not (Test-Path $RCLONE_CONF)) {
        Write-BackupLog "CLOUD: Chưa cấu hình rclone. Chạy setup_cloud_backup.ps1" "ERROR"
        if ($script:backupConfig.cloudNotify) {
            $notifyIcon.ShowBalloonTip(3000, "Nông Trí Phát", "Chưa thiết lập Google Drive! Chạy setup_cloud_backup.ps1", [System.Windows.Forms.ToolTipIcon]::Warning)
        }
        return $false
    }

    # Find latest zip if not specified
    if (-not $ZipFile -or -not (Test-Path $ZipFile)) {
        if (Test-Path $BACKUP_DIR) {
            $latest = Get-ChildItem $BACKUP_DIR -Filter "*.zip" -ErrorAction SilentlyContinue | Sort-Object LastWriteTime -Descending | Select-Object -First 1
            if ($latest) { $ZipFile = $latest.FullName }
        }
    }

    if (-not $ZipFile -or -not (Test-Path $ZipFile)) {
        Write-BackupLog "CLOUD: Không tìm thấy file backup để upload" "ERROR"
        return $false
    }

    $remote = $script:backupConfig.cloudRemote
    $folder = $script:backupConfig.cloudFolder
    $fileName = [System.IO.Path]::GetFileName($ZipFile)

    Write-BackupLog "CLOUD: Đang upload $fileName lên ${remote}:${folder}/..."
    if ($script:backupConfig.cloudNotify) {
        $notifyIcon.ShowBalloonTip(2000, "Nông Trí Phát", "☁ Đang đồng bộ lên cloud: $fileName", [System.Windows.Forms.ToolTipIcon]::Info)
    }

    try {
        $processInfo = New-Object System.Diagnostics.ProcessStartInfo
        $processInfo.FileName = $RCLONE_EXE
        $rcloneDest = "${remote}:${folder}/"
        $processInfo.Arguments = "copy `"$ZipFile`" `"$rcloneDest`" --config `"$RCLONE_CONF`" --no-traverse"
        $processInfo.RedirectStandardOutput = $true
        $processInfo.RedirectStandardError = $true
        $processInfo.UseShellExecute = $false
        $processInfo.CreateNoWindow = $true

        $process = New-Object System.Diagnostics.Process
        $process.StartInfo = $processInfo
        $process.Start() | Out-Null
        $process.WaitForExit(600000)  # 10 phút timeout

        $output = $process.StandardOutput.ReadToEnd()
        $errorOutput = $process.StandardError.ReadToEnd()
        $exitCode = $process.ExitCode

        if ($output) { Write-BackupLog "CLOUD Output: $output" }
        if ($errorOutput) { Write-BackupLog "CLOUD Stderr: $errorOutput" "WARN" }

        if ($exitCode -eq 0) {
            Write-BackupLog "CLOUD: Upload $fileName thành công!" "SUCCESS"
            $script:backupConfig.lastCloudRun = (Get-Date -Format "yyyy-MM-dd HH:mm:ss")
            Save-BackupConfig

            # Cleanup old cloud backups
            Cleanup-CloudBackups

            if ($script:backupConfig.cloudNotify) {
                $notifyIcon.ShowBalloonTip(3000, "Nông Trí Phát", "☁ Đồng bộ cloud thành công: $fileName", [System.Windows.Forms.ToolTipIcon]::Info)
            }
            return $true
        } else {
            Write-BackupLog "CLOUD: Upload thất bại! (Mã lỗi: $exitCode)" "ERROR"
            if ($script:backupConfig.cloudNotify) {
                $notifyIcon.ShowBalloonTip(3000, "Nông Trí Phát", "☁ Upload cloud THẤT BẠI!", [System.Windows.Forms.ToolTipIcon]::Error)
            }
            return $false
        }
    } catch {
        Write-BackupLog "CLOUD LỖI: $($_.Exception.Message)" "ERROR"
        if ($script:backupConfig.cloudNotify) {
            $notifyIcon.ShowBalloonTip(3000, "Nông Trí Phát", "☁ Lỗi cloud: $($_.Exception.Message)", [System.Windows.Forms.ToolTipIcon]::Error)
        }
        return $false
    }
}

function Cleanup-CloudBackups {
    $remote = $script:backupConfig.cloudRemote
    $folder = $script:backupConfig.cloudFolder
    $keep = $script:backupConfig.cloudKeep

    try {
        $rclonePath = "${remote}:${folder}/"
        $listOutput = & $RCLONE_EXE lsf $rclonePath --config $RCLONE_CONF --files-only 2>$null
        if (-not $listOutput) { return }

        $files = $listOutput -split "`n" | Where-Object { $_ -match "\.zip$" } | Sort-Object -Descending
        if ($files.Count -le $keep) { return }

        $toDelete = $files | Select-Object -Skip $keep
        foreach ($f in $toDelete) {
            $f = $f.Trim()
            if ($f) {
                $delPath = "${remote}:${folder}/$f"
                & $RCLONE_EXE delete $delPath --config $RCLONE_CONF 2>$null
                Write-BackupLog "CLOUD: Đã xóa bản cũ trên cloud: $f"
            }
        }
        Write-BackupLog "CLOUD: Giữ lại $keep bản mới nhất trên cloud"
    } catch {
        Write-BackupLog "CLOUD Cleanup lỗi: $($_.Exception.Message)" "WARN"
    }
}

function Get-CloudBackupCount {
    if (-not (Test-Path $RCLONE_EXE) -or -not (Test-Path $RCLONE_CONF)) { return "N/A" }
    try {
        $cRemote = $script:backupConfig.cloudRemote
        $cFolder = $script:backupConfig.cloudFolder
        $cPath = "${cRemote}:${cFolder}/"
        $listOutput = & $RCLONE_EXE lsf $cPath --config $RCLONE_CONF --files-only 2>$null
        if (-not $listOutput) { return 0 }
        return ($listOutput -split "`n" | Where-Object { $_ -match "\.zip$" }).Count
    } catch { return "?" }
}

function Open-CloudSetup {
    if (-not (Test-Path $RCLONE_EXE)) {
        [System.Windows.Forms.MessageBox]::Show("Chưa cài rclone!`n`nHãy chạy file setup_cloud_backup.ps1 trong thư mục ứng dụng.", "Thiết lập Cloud", "OK", "Warning")
        return
    }
    $processInfo = New-Object System.Diagnostics.ProcessStartInfo
    $processInfo.FileName = $RCLONE_EXE
    $processInfo.Arguments = "config --config `"$RCLONE_CONF`""
    $processInfo.UseShellExecute = $true
    [System.Diagnostics.Process]::Start($processInfo) | Out-Null
}

function Show-BackupConfigDialog {
    $form = New-Object System.Windows.Forms.Form
    $form.Text = "Cấu hình Sao lưu"
    $form.Size = New-Object System.Drawing.Size(400, 520)
    $form.StartPosition = "CenterScreen"
    $form.FormBorderStyle = "FixedDialog"
    $form.MaximizeBox = $false
    $form.MinimizeBox = $false
    $form.TopMost = $true
    $form.Font = New-Object System.Drawing.Font("Segoe UI", 9)
    $form.BackColor = [System.Drawing.Color]::White
    
    # === LOCAL BACKUP SECTION ===
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
    $lblInfo.Text = "Số bản local: $(Get-BackupCount) | Lần cuối: $(if($script:backupConfig.lastRun){$script:backupConfig.lastRun}else{'Chưa có'})"
    $lblInfo.Location = New-Object System.Drawing.Point(20, 150)
    $lblInfo.Size = New-Object System.Drawing.Size(350, 20)
    $lblInfo.ForeColor = [System.Drawing.Color]::Gray
    $form.Controls.Add($lblInfo)

    # === SEPARATOR ===
    $sep = New-Object System.Windows.Forms.Label
    $sep.BorderStyle = "Fixed3D"
    $sep.Location = New-Object System.Drawing.Point(15, 180)
    $sep.Size = New-Object System.Drawing.Size(355, 2)
    $form.Controls.Add($sep)

    # === CLOUD BACKUP SECTION ===
    $lblCloudHeader = New-Object System.Windows.Forms.Label
    $lblCloudHeader.Text = "☁ ĐỒNG BỘ LÊN CLOUD"
    $lblCloudHeader.Font = New-Object System.Drawing.Font("Segoe UI", 11, [System.Drawing.FontStyle]::Bold)
    $lblCloudHeader.Location = New-Object System.Drawing.Point(15, 192)
    $lblCloudHeader.AutoSize = $true
    $form.Controls.Add($lblCloudHeader)

    $chkCloud = New-Object System.Windows.Forms.CheckBox
    $chkCloud.Text = " Tự động upload lên cloud sau khi sao lưu"
    $chkCloud.Location = New-Object System.Drawing.Point(20, 225)
    $chkCloud.AutoSize = $true
    $chkCloud.Checked = $script:backupConfig.cloudEnabled
    $form.Controls.Add($chkCloud)

    $lblCloudFolder = New-Object System.Windows.Forms.Label
    $lblCloudFolder.Text = "Thư mục cloud:"
    $lblCloudFolder.Location = New-Object System.Drawing.Point(20, 258)
    $lblCloudFolder.AutoSize = $true
    $form.Controls.Add($lblCloudFolder)

    $txtCloudFolder = New-Object System.Windows.Forms.TextBox
    $txtCloudFolder.Location = New-Object System.Drawing.Point(140, 255)
    $txtCloudFolder.Size = New-Object System.Drawing.Size(220, 25)
    $txtCloudFolder.Text = $script:backupConfig.cloudFolder
    $form.Controls.Add($txtCloudFolder)

    $lblCloudKeep = New-Object System.Windows.Forms.Label
    $lblCloudKeep.Text = "Giữ trên cloud:"
    $lblCloudKeep.Location = New-Object System.Drawing.Point(20, 293)
    $lblCloudKeep.AutoSize = $true
    $form.Controls.Add($lblCloudKeep)

    $numCloudKeep = New-Object System.Windows.Forms.NumericUpDown
    $numCloudKeep.Location = New-Object System.Drawing.Point(140, 289)
    $numCloudKeep.Size = New-Object System.Drawing.Size(80, 25)
    $numCloudKeep.Minimum = 1; $numCloudKeep.Maximum = 100; $numCloudKeep.Value = $script:backupConfig.cloudKeep
    $form.Controls.Add($numCloudKeep)

    $chkCloudNotify = New-Object System.Windows.Forms.CheckBox
    $chkCloudNotify.Text = " Hiện thông báo khi upload cloud"
    $chkCloudNotify.Location = New-Object System.Drawing.Point(20, 323)
    $chkCloudNotify.AutoSize = $true
    $chkCloudNotify.Checked = $script:backupConfig.cloudNotify
    $form.Controls.Add($chkCloudNotify)

    # Cloud status
    $cloudStatus = if (Test-RcloneReady) { "Đã kết nối" } else { "Chưa thiết lập" }
    $lblCloudInfo = New-Object System.Windows.Forms.Label
    $lblCloudInfo.Text = "Trạng thái: $cloudStatus | Cloud cuối: $(if($script:backupConfig.lastCloudRun){$script:backupConfig.lastCloudRun}else{'Chưa có'})"
    $lblCloudInfo.Location = New-Object System.Drawing.Point(20, 355)
    $lblCloudInfo.Size = New-Object System.Drawing.Size(350, 20)
    $lblCloudInfo.ForeColor = if (Test-RcloneReady) { [System.Drawing.Color]::Green } else { [System.Drawing.Color]::OrangeRed }
    $form.Controls.Add($lblCloudInfo)

    $btnSetupCloud = New-Object System.Windows.Forms.Button
    $btnSetupCloud.Text = "Thiết lập Google Drive..."
    $btnSetupCloud.Location = New-Object System.Drawing.Point(20, 383)
    $btnSetupCloud.Size = New-Object System.Drawing.Size(170, 28)
    $btnSetupCloud.FlatStyle = "Flat"
    $btnSetupCloud.add_Click({ Open-CloudSetup })
    $form.Controls.Add($btnSetupCloud)

    # === BUTTONS ===
    $btnSave = New-Object System.Windows.Forms.Button
    $btnSave.Text = "Lưu"
    $btnSave.Location = New-Object System.Drawing.Point(170, 435)
    $btnSave.Size = New-Object System.Drawing.Size(100, 32)
    $btnSave.BackColor = [System.Drawing.Color]::FromArgb(0, 120, 215)
    $btnSave.ForeColor = [System.Drawing.Color]::White
    $btnSave.FlatStyle = "Flat"
    $btnSave.add_Click({
        $script:backupConfig.enabled = $chkEnabled.Checked
        $script:backupConfig.time = $timePicker.Value.ToString("HH:mm")
        $script:backupConfig.keep = [int]$numKeep.Value
        $script:backupConfig.cloudEnabled = $chkCloud.Checked
        $script:backupConfig.cloudFolder = $txtCloudFolder.Text
        $script:backupConfig.cloudKeep = [int]$numCloudKeep.Value
        $script:backupConfig.cloudNotify = $chkCloudNotify.Checked
        Save-BackupConfig
        $btnBackupAuto.Checked = $script:backupConfig.enabled
        $form.Close()
    })
    $form.Controls.Add($btnSave)
    
    $btnCancel = New-Object System.Windows.Forms.Button
    $btnCancel.Text = "Hủy"
    $btnCancel.Location = New-Object System.Drawing.Point(280, 435)
    $btnCancel.Size = New-Object System.Drawing.Size(80, 32)
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
$btnCloudUpload = $contextMenu.Items.Add("☁ Upload cloud ngay")
$btnCloudSetup = $contextMenu.Items.Add("☁ Thiết lập Google Drive...")
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

$btnCloudUpload.add_Click({
    if ([System.Windows.Forms.MessageBox]::Show("Upload bản backup mới nhất lên Google Drive?", "Xác nhận", "YesNo", "Question") -eq "Yes") {
        Upload-ToCloud
    }
})
$btnCloudSetup.add_Click({ Open-CloudSetup })


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
