@echo off
title BOT SCRAPER SPSE INAPROC REKANAN LOMBOK BARAT
cd /d "c:\laragon\www\infoajip"
echo ==============================================================================
echo       BOT SCRAPER SPSE INAPROC VERIFIKASI REKANAN (LOMBOK BARAT)
echo ==============================================================================
echo.
echo URL Target: https://spse.inaproc.id/lombokbaratkab/verifikasi/rekanan
echo.
echo Petunjuk:
echo  1. Browser Chrome akan terbuka otomatis.
echo  2. Silakan login ke akun SPSE Anda pada browser Chrome yang muncul.
echo  3. Setelah berhasil masuk ke halaman Verifikasi Rekanan, bot akan otomatis
echo     mengambil seluruh halaman data penyedia dan menyimpannya ke database.
echo.
set PYTHON_EXE=C:\Users\lobar\AppData\Local\Python\pythoncore-3.14-64\python.exe
if not exist "%PYTHON_EXE%" (
    set PYTHON_EXE=python
)

"%PYTHON_EXE%" -u "bot_spse_rekanan.py" %*
echo.
echo ==============================================================================
echo Proses selesai. Jendela CMD ini tetap terbuka agar Anda dapat meninjau log.
echo ==============================================================================
pause
cmd /k
