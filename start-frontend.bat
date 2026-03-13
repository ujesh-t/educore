@echo off
echo Starting EduCore Frontend Server...
cd /d %~dp0\frontend
npm run dev
pause
