Set WshShell = CreateObject("WScript.Shell")
Set fso = CreateObject("Scripting.FileSystemObject")

' Get the directory of this VBS script
strPath = fso.GetParentFolderName(WScript.ScriptFullName)
WshShell.CurrentDirectory = strPath

' Run the PowerShell Tray script in hidden mode
WshShell.Run "powershell.exe -WindowStyle Hidden -ExecutionPolicy Bypass -File NongTriPhatTray.ps1", 0, False
