@echo off
echo Starting EduCore Backend Server...
cd /d %~dp0\backend
php artisan serve --port=8000
pause
