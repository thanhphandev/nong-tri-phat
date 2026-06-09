# ============================================================================
# NongTriPhat - Thiết lập Cloud Backup (Google Drive)
# Chạy script này 1 lần để cài đặt rclone và kết nối Google Drive
# ============================================================================

$RCLONE_DIR = "$PSScriptRoot\env\rclone"
$RCLONE_EXE = "$RCLONE_DIR\rclone.exe"
$RCLONE_CONF = "$RCLONE_DIR\rclone.conf"
$RCLONE_VERSION = "v1.68.2"
$RCLONE_ZIP_URL = "https://downloads.rclone.org/$RCLONE_VERSION/rclone-$RCLONE_VERSION-windows-amd64.zip"

Write-Host ""
Write-Host "============================================" -ForegroundColor Cyan
Write-Host "  THIET LAP CLOUD BACKUP - NONG TRI PHAT   " -ForegroundColor Cyan
Write-Host "============================================" -ForegroundColor Cyan
Write-Host ""

# --- BUOC 1: Tai rclone ---
if (Test-Path $RCLONE_EXE) {
    Write-Host "[OK] rclone da duoc cai dat tai: $RCLONE_EXE" -ForegroundColor Green
    & $RCLONE_EXE version 2>$null | Select-Object -First 1 | ForEach-Object { Write-Host "     $_" -ForegroundColor Gray }
} else {
    Write-Host "[1/3] Dang tai rclone..." -ForegroundColor Yellow

    # Tao thu muc
    if (-not (Test-Path $RCLONE_DIR)) {
        New-Item -ItemType Directory -Path $RCLONE_DIR -Force | Out-Null
    }

    $tempZip = "$env:TEMP\rclone_download.zip"
    $tempExtract = "$env:TEMP\rclone_extract"

    try {
        # Download
        [Net.ServicePointManager]::SecurityProtocol = [Net.SecurityProtocolType]::Tls12
        Write-Host "     Dang tai tu: $RCLONE_ZIP_URL" -ForegroundColor Gray
        Invoke-WebRequest -Uri $RCLONE_ZIP_URL -OutFile $tempZip -UseBasicParsing
        Write-Host "     Tai xong! Dang giai nen..." -ForegroundColor Gray

        # Extract
        if (Test-Path $tempExtract) { Remove-Item $tempExtract -Recurse -Force }
        Expand-Archive -Path $tempZip -DestinationPath $tempExtract -Force

        # Find rclone.exe in extracted folder
        $extractedExe = Get-ChildItem $tempExtract -Recurse -Filter "rclone.exe" | Select-Object -First 1
        if ($extractedExe) {
            Copy-Item $extractedExe.FullName -Destination $RCLONE_EXE -Force
            Write-Host "[OK] rclone da duoc cai dat!" -ForegroundColor Green
        } else {
            throw "Khong tim thay rclone.exe trong file zip"
        }

        # Cleanup
        Remove-Item $tempZip -Force -ErrorAction SilentlyContinue
        Remove-Item $tempExtract -Recurse -Force -ErrorAction SilentlyContinue

    } catch {
        Write-Host "[LOI] Khong the tai rclone: $($_.Exception.Message)" -ForegroundColor Red
        Write-Host ""
        Write-Host "Ban co the tai thu cong tai: https://rclone.org/downloads/" -ForegroundColor Yellow
        Write-Host "Sau do copy rclone.exe vao: $RCLONE_DIR" -ForegroundColor Yellow
        Write-Host ""
        Read-Host "Nhan Enter de thoat"
        Exit 1
    }
}

Write-Host ""

# --- BUOC 2: Kiem tra remote da cau hinh chua ---
Write-Host "[2/3] Kiem tra cau hinh Google Drive..." -ForegroundColor Yellow

$hasRemote = $false
if (Test-Path $RCLONE_CONF) {
    $remotes = & $RCLONE_EXE listremotes --config $RCLONE_CONF 2>$null
    if ($remotes -match "gdrive:") {
        $hasRemote = $true
        Write-Host "[OK] Da co remote 'gdrive' duoc cau hinh!" -ForegroundColor Green

        # Test connection
        Write-Host "     Dang kiem tra ket noi..." -ForegroundColor Gray
        $testResult = & $RCLONE_EXE about gdrive: --config $RCLONE_CONF 2>&1
        if ($LASTEXITCODE -eq 0) {
            Write-Host "[OK] Ket noi Google Drive thanh cong!" -ForegroundColor Green
            $testResult | ForEach-Object { Write-Host "     $_" -ForegroundColor Gray }
        } else {
            Write-Host "[CANH BAO] Ket noi co van de. Can cau hinh lai." -ForegroundColor Yellow
            $hasRemote = $false
        }
    }
}

if (-not $hasRemote) {
    Write-Host ""
    Write-Host "  Can ket noi voi Google Drive cua ban." -ForegroundColor White
    Write-Host "  Trinh duyet se mo de ban dang nhap Google Account." -ForegroundColor White
    Write-Host ""
    
    $confirm = Read-Host "  Nhan Enter de bat dau cau hinh (hoac 'q' de thoat)"
    if ($confirm -eq 'q') { Exit }

    Write-Host ""
    Write-Host "  Dang mo cau hinh rclone..." -ForegroundColor Yellow
    Write-Host "  Hay lam theo huong dan duoi day:" -ForegroundColor Yellow
    Write-Host ""
    Write-Host "  1. Chon 'n' (New remote)" -ForegroundColor White
    Write-Host "  2. Ten: gdrive" -ForegroundColor White
    Write-Host "  3. Storage: Tim so cua 'Google Drive' (thuong la 18)" -ForegroundColor White
    Write-Host "  4. client_id: Enter (bo trong)" -ForegroundColor White
    Write-Host "  5. client_secret: Enter (bo trong)" -ForegroundColor White
    Write-Host "  6. scope: Chon 1 (Full access)" -ForegroundColor White
    Write-Host "  7. service_account_file: Enter (bo trong)" -ForegroundColor White
    Write-Host "  8. Edit advanced config: n" -ForegroundColor White
    Write-Host "  9. Use auto config: y" -ForegroundColor White
    Write-Host "  10. Dang nhap Google tren trinh duyet" -ForegroundColor White
    Write-Host "  11. Configure as team drive: n" -ForegroundColor White
    Write-Host "  12. Confirm: y" -ForegroundColor White
    Write-Host "  13. Chon 'q' (Quit config)" -ForegroundColor White
    Write-Host ""
    Write-Host "============================================" -ForegroundColor Cyan
    Write-Host ""

    & $RCLONE_EXE config --config $RCLONE_CONF

    # Verify
    $remotes = & $RCLONE_EXE listremotes --config $RCLONE_CONF 2>$null
    if ($remotes -match "gdrive:") {
        Write-Host ""
        Write-Host "[OK] Cau hinh Google Drive thanh cong!" -ForegroundColor Green
    } else {
        Write-Host ""
        Write-Host "[LOI] Chua tim thay remote 'gdrive'. Vui long chay lai script nay." -ForegroundColor Red
        Read-Host "Nhan Enter de thoat"
        Exit 1
    }
}

Write-Host ""

# --- BUOC 3: Test upload ---
Write-Host "[3/3] Thu nghiem upload len Google Drive..." -ForegroundColor Yellow

$testFolder = "NongTriPhat-Backups"
$testFile = "$env:TEMP\ntp_test_upload.txt"
"NongTriPhat Cloud Backup Test - $(Get-Date)" | Set-Content $testFile -Encoding UTF8

try {
    & $RCLONE_EXE copy $testFile "gdrive:$testFolder/" --config $RCLONE_CONF 2>&1
    if ($LASTEXITCODE -eq 0) {
        Write-Host "[OK] Upload thu nghiem thanh cong!" -ForegroundColor Green
        Write-Host "     Thu muc tren Google Drive: $testFolder" -ForegroundColor Gray

        # Cleanup test file on Drive
        & $RCLONE_EXE delete "gdrive:$testFolder/ntp_test_upload.txt" --config $RCLONE_CONF 2>$null
    } else {
        Write-Host "[LOI] Upload thu nghiem that bai!" -ForegroundColor Red
    }
} catch {
    Write-Host "[LOI] $($_.Exception.Message)" -ForegroundColor Red
} finally {
    Remove-Item $testFile -Force -ErrorAction SilentlyContinue
}

# --- Update backup_config.json ---
$configPath = "$PSScriptRoot\backup_config.json"
if (Test-Path $configPath) {
    try {
        $config = Get-Content $configPath -Raw -Encoding UTF8 | ConvertFrom-Json
        $needsUpdate = $false

        if (-not ($config.PSObject.Properties.Name -contains "cloudEnabled")) {
            $config | Add-Member -MemberType NoteProperty -Name "cloudEnabled" -Value $true
            $needsUpdate = $true
        }
        if (-not ($config.PSObject.Properties.Name -contains "cloudRemote")) {
            $config | Add-Member -MemberType NoteProperty -Name "cloudRemote" -Value "gdrive"
            $needsUpdate = $true
        }
        if (-not ($config.PSObject.Properties.Name -contains "cloudFolder")) {
            $config | Add-Member -MemberType NoteProperty -Name "cloudFolder" -Value "NongTriPhat-Backups"
            $needsUpdate = $true
        }
        if (-not ($config.PSObject.Properties.Name -contains "cloudKeep")) {
            $config | Add-Member -MemberType NoteProperty -Name "cloudKeep" -Value 10
            $needsUpdate = $true
        }
        if (-not ($config.PSObject.Properties.Name -contains "lastCloudRun")) {
            $config | Add-Member -MemberType NoteProperty -Name "lastCloudRun" -Value ""
            $needsUpdate = $true
        }
        if (-not ($config.PSObject.Properties.Name -contains "cloudNotify")) {
            $config | Add-Member -MemberType NoteProperty -Name "cloudNotify" -Value $true
            $needsUpdate = $true
        }

        if ($needsUpdate) {
            $config | ConvertTo-Json | Set-Content $configPath -Encoding UTF8
            Write-Host "[OK] Da cap nhat backup_config.json voi cau hinh cloud" -ForegroundColor Green
        }
    } catch {
        Write-Host "[CANH BAO] Khong the cap nhat backup_config.json: $($_.Exception.Message)" -ForegroundColor Yellow
    }
}

Write-Host ""
Write-Host "============================================" -ForegroundColor Green
Write-Host "  THIET LAP HOAN TAT!                      " -ForegroundColor Green
Write-Host "============================================" -ForegroundColor Green
Write-Host ""
Write-Host "  Cloud backup da san sang su dung." -ForegroundColor White
Write-Host "  Bat tinh nang trong menu tray:" -ForegroundColor White
Write-Host "    Click phai icon > Cau hinh sao luu" -ForegroundColor Gray
Write-Host "    > Tick 'Dong bo len cloud'" -ForegroundColor Gray
Write-Host ""
Read-Host "Nhan Enter de thoat"
