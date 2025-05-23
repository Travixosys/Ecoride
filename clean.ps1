# C:\ecoride-slim\clean.ps1

Write-Host "`nCleaning up unnecessary backup/dev files in EcoRide-Slim..." -ForegroundColor Cyan

$patterns = @(
    "*copy*.php",
    "*copy*.twig",
    "*BALINE*.php",
    "*origin*.twig",
    "*.tmp",
    "et --hard*"
)

foreach ($pattern in $patterns) {
    Get-ChildItem -Path "." -Recurse -Filter $pattern -File -ErrorAction SilentlyContinue | Where-Object {
        $_.Name -ne "admin-charts.js"
    } | ForEach-Object {
        Write-Host "Deleting: $($_.FullName)"
        Remove-Item $_.FullName -Force
    }
}

Write-Host "`nCleanup complete. You can now safely run 'git add .' and commit." -ForegroundColor Green
