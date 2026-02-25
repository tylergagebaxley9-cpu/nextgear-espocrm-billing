# Run Call Flow Button Setup in EspoCRM Docker container
# Run this from the billing folder

$scriptPath = "$PSScriptRoot\call_flow_setup.php"

Write-Host ""
Write-Host "=== EspoCRM Call Flow Setup ===" -ForegroundColor Cyan
Write-Host ""

# Copy script into container
Write-Host "Copying script to container..." -ForegroundColor Yellow
docker cp $scriptPath espocrm:/tmp/call_flow_setup.php

if ($LASTEXITCODE -ne 0) {
    Write-Host "ERROR: Could not copy script. Is the container named 'espocrm'?" -ForegroundColor Red
    Write-Host "Check with: docker ps" -ForegroundColor Yellow
    exit 1
}

# Run the script
Write-Host "Running setup..." -ForegroundColor Yellow
Write-Host ""
docker exec espocrm php /tmp/call_flow_setup.php

if ($LASTEXITCODE -ne 0) {
    Write-Host ""
    Write-Host "ERROR: Setup script failed." -ForegroundColor Red
    exit 1
}

# Restart container to pick up new JS files
Write-Host ""
Write-Host "Restarting EspoCRM container..." -ForegroundColor Yellow
docker restart espocrm

Write-Host ""
Write-Host "Done! Hard-refresh your browser (Ctrl+Shift+R) to see the new buttons." -ForegroundColor Green
Write-Host ""
