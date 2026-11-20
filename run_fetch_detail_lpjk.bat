@echo off
title Ekstraksi Modal Detail LPJK PUPR (NIB, Pengurus, Legalitas, SBU)
cd /d "c:\laragon\www\infoajip"
echo ==============================================================================
echo       EKSTRAKSI PROFIL DETAIL (MODAL) LPJK PUPR UNTUK KONTRAKTOR NTB
echo ==============================================================================
echo.
echo Menjalankan bot untuk mengambil NIB, Direktur/Pengurus, Legalitas AHU, & SBU...
echo.
set PYTHON_EXE=C:\Users\lobar\AppData\Local\Python\pythoncore-3.14-64\python.exe
if not exist "%PYTHON_EXE%" (
    set PYTHON_EXE=python
)

"%PYTHON_EXE%" -u "bot_lpjk_selenium.py" --details-only --scope ntb
echo.
echo ==============================================================================
echo Selesai. Jendela CMD ini tetap terbuka agar Anda dapat meninjau riwayat log.
echo ==============================================================================
pause
cmd /k
