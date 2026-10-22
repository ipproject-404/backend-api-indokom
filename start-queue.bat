@echo off
cd /d C:\laragon\www\backend-api
php artisan queue:work --tries=3
pause