@echo off
title Bot LPJK PUPR (Selenium Turbo)
cd /d "%~dp0"

echo ======================================================================
echo    BOT OTOMATIS LPJK PUPR DAN EKSTRAKSI SBU TURBO
echo ======================================================================
echo.

set PYTHON_EXE=C:\Users\lobar\AppData\Local\Python\pythoncore-3.14-64\python.exe
if not exist "%PYTHON_EXE%" (
    set PYTHON_EXE=python
)

"%PYTHON_EXE%" -u "bot_lpjk_selenium.py" %*

echo.
echo ======================================================================
echo [i] Proses Bot LPJK telah selesai.
echo Jendela ini tidak ditutup otomatis agar Anda dapat membaca log.
echo Tekan tombol apa saja untuk menutup jendela ini...
echo ======================================================================
pause > nul
