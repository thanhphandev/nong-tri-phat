# Script tu dong build shortcut ngoai Desktop cho khach hang
# Cach dung: Chuot phai chon "Run with PowerShell"

$WshShell = New-Object -ComObject WScript.Shell

# --- CAU HINH ---
$ShortcutName = "Nong Tri Phat"
$DesktopPath = [Environment]::GetFolderPath("Desktop")
$ShortcutPath = Join-Path $DesktopPath "$ShortcutName.lnk"
$VbsLauncher = "$PSScriptRoot\NTP_Launcher.vbs"
$IconPath = "$PSScriptRoot\launcher_icon.ico"

# --- TAO SHORTCUT ---
try {
    $Shortcut = $WshShell.CreateShortcut($ShortcutPath)
    $Shortcut.TargetPath = "wscript.exe"
    $Shortcut.Arguments = "`"$VbsLauncher`""
    $Shortcut.WorkingDirectory = $PSScriptRoot
    $Shortcut.IconLocation = $IconPath
    $Shortcut.Description = "He thong quan ly Nong Tri Phat"
    $Shortcut.Save()

    # Thong bao thanh cong bang tieng Viet (Console)
    Write-Host "==========================================" -ForegroundColor Green
    Write-Host "   DA TAO ICON NGOAI DESKTOP THANH CONG!  " -ForegroundColor Green
    Write-Host "==========================================" -ForegroundColor Green
    Write-Host "Bay gio khach hang co the mo ung dung truc tiep tu Desktop."
    
    # Hien thi thong bao pop-up
    Add-Type -AssemblyName System.Windows.Forms
    [System.Windows.Forms.MessageBox]::Show("Da tao icon 'Nong Tri Phat' ngoai Desktop thanh cong!", "Cai Dat Hoan Tat")
} catch {
    Write-Host "Co loi xay ra: $_" -ForegroundColor Red
    pause
}
