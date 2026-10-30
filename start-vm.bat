@echo off
echo Menyalakan VM jogjatrip-vm...
"C:\Program Files\Oracle\VirtualBox\VBoxManage.exe" startvm "jogjatrip-vm" --type gui
if %ERRORLEVEL% EQU 0 (
    echo.
    echo Berhasil. Tunggu VM selesai boot, lalu buka http://localhost:8080 di browser.
) else (
    echo.
    echo Gagal menyalakan VM. Coba cek VirtualBox Manager secara manual.
)
pause
