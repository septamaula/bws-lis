<#
.SYNOPSIS
  Smart Docker Launcher for Bank Woori Saudara (BWS) LIS.
  Secara otomatis memeriksa ketersediaan port (Backend, Frontend, PWA, MySQL).
  Jika port default sedang dipakai oleh aplikasi lain di komputer, launcher
  ini akan otomatis mencari port kosong berikutnya dan memperbarui file .env.
#>

param(
    [switch]$Build = $false,
    [switch]$Down = $false
)

$ErrorActionPreference = "Stop"

if ($Down) {
    Write-Host "[BWS-LIS] Menghentikan semua container..." -ForegroundColor Yellow
    docker compose down
    exit 0
}

# 1. Pastikan file .env ada
if (-not (Test-Path .env)) {
    if (Test-Path .env.docker.example) {
        Write-Host "[BWS-LIS] Menyiapkan .env dari .env.docker.example..." -ForegroundColor Cyan
        Copy-Item .env.docker.example .env
    } else {
        Write-Host "[BWS-LIS] Error: File .env atau .env.docker.example tidak ditemukan." -ForegroundColor Red
        exit 1
    }
}

# 2. Hentikan container bws-lis yang sedang berjalan agar port internalnya dilepas
$existingContainers = docker ps -q --filter "name=bws-lis-"
if ($existingContainers) {
    Write-Host "[BWS-LIS] Melepas port container bws-lis yang aktif..." -ForegroundColor DarkGray
    docker compose down | Out-Null
}

# 3. Fungsi pencari port kosong
$allocated = [System.Collections.Generic.List[int]]::new()

function Get-AvailablePort {
    param(
        [int]$StartPort,
        [string]$ServiceName
    )

    $candidate = $StartPort
    while ($true) {
        if ($allocated.Contains($candidate)) {
            $candidate++
            continue
        }

        $busy = $false
        try {
            $listener = [System.Net.Sockets.TcpListener]::new([System.Net.IPAddress]::Any, $candidate)
            $listener.Start()
            $listener.Stop()
        } catch {
            $busy = $true
        }

        if (-not $busy) {
            $conn = Get-NetTCPConnection -LocalPort $candidate -ErrorAction SilentlyContinue
            if ($conn) {
                $busy = $true
            }
        }

        if (-not $busy) {
            $allocated.Add($candidate)
            if ($candidate -ne $StartPort) {
                Write-Host "  * [AUTO-RESOLVE] Port $StartPort sedang dipakai. $ServiceName dialihkan ke port: $candidate" -ForegroundColor Yellow
            } else {
                Write-Host "  * Port $candidate tersedia untuk $ServiceName" -ForegroundColor Green
            }
            return $candidate
        }

        $candidate++
    }
}

Write-Host "============================================================" -ForegroundColor Cyan
Write-Host "  BWS LIS - Deteksi Port Otomatis & Docker Launcher" -ForegroundColor Cyan
Write-Host "============================================================" -ForegroundColor Cyan

$bePort    = Get-AvailablePort -StartPort 8000 -ServiceName "Backend (Laravel REST API)"
$fePort    = Get-AvailablePort -StartPort 5173 -ServiceName "Frontend (Vue 3 Admin)"
$pwaPort   = Get-AvailablePort -StartPort 5174 -ServiceName "PWA Portal (React 19 Mobile)"
$mysqlPort = Get-AvailablePort -StartPort 3307 -ServiceName "MySQL 8.4 Database"

# 4. Update file .env
$envContent = Get-Content .env -Raw

function Set-EnvVar {
    param([string]$Key, [string]$Value)
    if ($script:envContent -match "(?m)^$Key=.*`$") {
        $script:envContent = $script:envContent -replace "(?m)^$Key=.*`$", "$Key=$Value"
    } else {
        $script:envContent = $script:envContent.TrimEnd() + "`r`n$Key=$Value`r`n"
    }
}

Set-EnvVar "DOCKER_BE_PORT" $bePort
Set-EnvVar "DOCKER_FE_PORT" $fePort
Set-EnvVar "DOCKER_PWA_PORT" $pwaPort
Set-EnvVar "DOCKER_MYSQL_PORT" $mysqlPort

Set-Content -Path .env -Value $envContent -NoNewline

Write-Host "`n[BWS-LIS] Menjalankan Docker Compose..." -ForegroundColor Cyan
if ($Build) {
    docker compose up -d --build
} else {
    docker compose up -d
}

if ($LASTEXITCODE -ne 0) {
    Write-Host "[BWS-LIS] Gagal menjalankan docker compose." -ForegroundColor Red
    exit 1
}

Write-Host "`n============================================================" -ForegroundColor Green
Write-Host "  BWS LEARNING INTEGRATED SYSTEM (LIS) AKTIF BERJALAN" -ForegroundColor Green
Write-Host "============================================================" -ForegroundColor Green
Write-Host "  * Aplikasi 1 (Admin & Ops)  : http://localhost:$fePort" -ForegroundColor White
Write-Host "  * Aplikasi 2 (Portal Mobile): http://localhost:$pwaPort" -ForegroundColor White
Write-Host "  * Backend REST API          : http://localhost:$bePort/api" -ForegroundColor White
Write-Host "  * MySQL Database            : localhost:$mysqlPort (DB: bws_lis_db)" -ForegroundColor White
Write-Host "============================================================" -ForegroundColor Green
