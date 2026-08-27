@echo off
echo Starting Flask API...
start /b python backend/api/api.py
echo Flask API started on localhost:5000.
echo.
echo Starting Cloudflare Quick Tunnel...
cloudflared tunnel --url http://localhost:5000
