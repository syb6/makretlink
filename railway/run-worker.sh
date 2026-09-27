#!/bin/bash
# Railway queue worker. Mail is sent synchronously today, so this service is
# optional; enable it if you switch notifications to ShouldQueue later.
# Make executable: chmod +x railway/run-worker.sh
set -e
php artisan queue:work --tries=3 --timeout=60
