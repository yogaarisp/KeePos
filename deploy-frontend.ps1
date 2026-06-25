# ============================================================
# deploy-frontend.ps1
# Script untuk build frontend dan zip siap upload ke server
# ============================================================

$frontendPath = "$PSScriptRoot\frontend"
$distPath     = "$frontendPath\dist"
$zipPath      = "$PSScriptRoot\frontend-dist.zip"

Write-Host ""
Write-Host "========================================" -ForegroundColor Cyan
Write-Host "  Kee POS - Frontend Deploy Helper" -ForegroundColor Cyan
Write-Host "========================================" -ForegroundColor Cyan
Write-Host ""

# Step 1: Build
Write-Host "[1/3] Building frontend..." -ForegroundColor Yellow
Set-Location $frontendPath
npm run build
if ($LASTEXITCODE -ne 0) {
    Write-Host "BUILD FAILED. Cek error di atas." -ForegroundColor Red
    exit 1
}
Write-Host "Build selesai." -ForegroundColor Green

# Step 2: Zip dist
Write-Host ""
Write-Host "[2/3] Membuat zip dari dist/..." -ForegroundColor Yellow
if (Test-Path $zipPath) { Remove-Item $zipPath -Force }
Compress-Archive -Path "$distPath\*" -DestinationPath $zipPath -Force
Write-Host "Zip dibuat: $zipPath" -ForegroundColor Green

# Step 3: Info
Write-Host ""
Write-Host "[3/3] SELESAI!" -ForegroundColor Green
Write-Host ""
Write-Host "========================================" -ForegroundColor Cyan
Write-Host "  LANGKAH SELANJUTNYA (di aaPanel):" -ForegroundColor Cyan
Write-Host "========================================" -ForegroundColor Cyan
Write-Host ""
Write-Host "1. Buka aaPanel > Files" -ForegroundColor White
Write-Host "2. Tentukan folder public aktif di server Anda" -ForegroundColor White
Write-Host "3. HAPUS folder assets yang lama" -ForegroundColor Red
Write-Host "4. Upload file: frontend-dist.zip" -ForegroundColor White
Write-Host "5. Extract zip di folder tersebut" -ForegroundColor White
Write-Host "6. Selesai!" -ForegroundColor White
Write-Host ""
