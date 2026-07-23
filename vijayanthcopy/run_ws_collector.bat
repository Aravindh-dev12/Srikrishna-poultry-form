@echo off
cd /d C:\xampp\htdocs\common\Vijayanth
:restart
echo [%date% %time%] Starting Vijayanth WebSocket SQL collector...
node ws_collector.js
echo [%date% %time%] Collector stopped. Restarting in 5 seconds...
timeout /t 5 /nobreak >nul
goto restart
