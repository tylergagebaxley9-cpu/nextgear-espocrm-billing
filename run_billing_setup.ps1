# Run EspoCRM Billing Setup
Write-Host "Copying billing_setup.php into container..."
docker cp C:\Users\Owner\billing\billing_setup.php espocrm:/tmp/billing_setup.php

Write-Host "Running billing_setup.php inside container..."
docker exec espocrm php /tmp/billing_setup.php

Write-Host "Restarting EspoCRM container..."
docker restart espocrm

Write-Host "Done! EspoCRM restarting..."
