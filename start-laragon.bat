@echo off
title BWS-LIS Native Launcher (Laragon)
echo ========================================================
echo   BANK WOORI SAUDARA - LEARNING INTEGRATED SYSTEM (LIS)
echo   Mode: Native Laragon (Tanpa Docker)
echo ========================================================
echo.

:: 1. Cek apakah MySQL Laragon aktif di port 3306
netstat -ano | findstr :3306 | findstr LISTENING >nul
if %errorlevel% neq 0 (
    echo [PERINGATAN] MySQL Laragon belum aktif di port 3306!
    echo Silakan buka aplikasi Laragon lalu klik "Start All" terlebih dahulu.
    echo.
    pause
    exit /b 1
)

echo [1/3] MySQL Laragon terdeteksi aktif di port 3306.
echo [2/3] Menjalankan Backend Laravel di port 8000...
start "BWS-LIS Backend (Laravel)" /min cmd /c "cd /d %~dp0be && php artisan serve --port=8000"

echo [3/3] Menjalankan Frontend Vue 3 di port 5173...
start "BWS-LIS Frontend (Vite)" /min cmd /c "cd /d %~dp0fe && npm run dev"

echo.
echo ========================================================
echo   APLIKASI BWS-LIS BERHASIL DIJALANKAN!
echo   - Frontend Portal : http://localhost:5173
echo   - Backend API     : http://127.0.0.1:8000/api
echo   - Database        : Laragon MySQL (bws_lis_db)
echo ========================================================
echo.
echo Membuka browser ke http://localhost:5173...
timeout /t 3 >nul
start http://localhost:5173

echo.
echo Service sedang berjalan di background.
echo Anda dapat menutup jendela ini kapan saja.
timeout /t 4 >nul
