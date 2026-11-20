@echo off
title SPSE Lombok Barat - Bot Penyedia Terverifikasi
cd /d "%~dp0"

set "PY_EXE=C:\Users\lobar\AppData\Local\Python\pythoncore-3.14-64\python.exe"
if not exist "%PY_EXE%" set "PY_EXE=python"

echo ============================================================================
echo   BOT SCRAPER PENYEDIA TERVERIFIKASI SPSE INAPROC
echo   Target: https://spse.inaproc.id/lombokbaratkab/verifikasi/rekananterverifikasi
echo ============================================================================
echo.
echo Pilih Mode Eksekusi:
echo   [1] Scrape Lengkap (Tabel Utama + Detail Domisili/Alamat + Simpan DB/Excel) [Default]
echo   [2] Scrape Cepat (Hanya Tabel Utama + Simpan DB/Excel)
echo   [3] Lanjutkan Enrich Detail Domisili/Alamat (dari CSV/DB yang sudah ada)
echo   [4] Test 50 Data Pertama (Tabel + Detail)
echo.
set /p MODE_PILIH="Masukkan pilihan [1-4, tekan Enter untuk 1]: "

if "%MODE_PILIH%"=="2" (
    "%PY_EXE%" rekanan_terverifikasi_bot.py --mode list
) else if "%MODE_PILIH%"=="3" (
    "%PY_EXE%" rekanan_terverifikasi_bot.py --mode enrich
) else if "%MODE_PILIH%"=="4" (
    "%PY_EXE%" rekanan_terverifikasi_bot.py --mode all --limit 50
) else (
    "%PY_EXE%" rekanan_terverifikasi_bot.py --mode all
)

echo.
pause

