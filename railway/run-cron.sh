#!/bin/bash
# Laravel scheduler loop for Railway cron service (runs every minute).
# Make executable: railway/run-cron.sh
while true; do
    echo "Running the scheduler..."
    php artisan schedule:run --verbose --no-interaction &
    sleep 60
done
