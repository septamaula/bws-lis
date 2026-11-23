@echo off
title Hentikan BWS-LIS Native
echo Menghentikan proses Backend dan Frontend BWS-LIS...
taskkill /f /fi "WINDOWTITLE eq BWS-LIS Backend (Laravel)*" 2>nul
taskkill /f /fi "WINDOWTITLE eq BWS-LIS Frontend (Vite)*" 2>nul
echo.
echo BWS-LIS telah dihentikan dengan aman.
timeout /t 2 >nul
