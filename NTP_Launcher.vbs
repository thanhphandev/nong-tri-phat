Set WshShell = CreateObject("WScript.Shell")
' Run the PowerShell Tray script in hidden mode
WshShell.Run "powershell.exe -WindowStyle Hidden -ExecutionPolicy Bypass -File .\NongTriPhatTray.ps1", 0, False
