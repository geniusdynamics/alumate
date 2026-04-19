# Development Server Startup Scripts

This project includes three cross-platform development server startup scripts that can be executed from anywhere on your system.

## Available Scripts

### 1. start-dev.ps1 (PowerShell - Windows)
```powershell
# Run from project directory
.\start-dev.ps1

# Run from anywhere
powershell -ExecutionPolicy Bypass -File "D:\DevCenter\abuilds\alumate\start-dev.ps1"

# Skip dependency checks
.\start-dev.ps1 -SkipChecks
```

### 2. start-dev.sh (Bash - Unix/Linux/WSL)
```bash
# Run from project directory
./start-dev.sh

# Run from anywhere
bash "/d/DevCenter/abuilds/alumate/start-dev.sh"

# Skip dependency checks
./start-dev.sh --skip-checks
```

### 3. start-dev.bat (Batch - Windows)
```cmd
# Run from project directory
start-dev.bat

# Run from anywhere
"D:\DevCenter\abuilds\alumate\start-dev.bat"

# Skip dependency checks
start-dev.bat --skip-checks
```

## What These Scripts Do

1. **Auto-detect project directory** - Uses script location to find the correct project root
2. **Verify dependencies** - Checks for PHP, Node.js, and npm
3. **Handle port conflicts** - Automatically kills processes using ports 5173 and 8080
4. **Install dependencies** - Runs `npm install` and `composer install` if needed
5. **Clear Laravel caches** - Clears config, route, view, and application caches
6. **Start both servers** - Launches Vite (frontend) and Laravel (backend) development servers
7. **Provide status updates** - Shows clear messages and server URLs
8. **Handle cleanup** - Properly stops both servers when interrupted

## Server URLs

- **Frontend (Vite)**: http://localhost:5173
- **Backend (Laravel)**: http://127.0.0.1:8080

## Configuration

- **PHP Path**: D:\DevCenter\xampp\php-8.3.23\php.exe
- **Vite Port**: 5173 (configurable in scripts)
- **Laravel Port**: 8080 (configurable in scripts)

## Troubleshooting

- If you get permission errors on Windows, run PowerShell as Administrator
- If ports are in use, the scripts will automatically kill existing processes
- Use `--skip-checks` or `-SkipChecks` to bypass dependency verification
- Check that PHP path is correct in your system

## Features

✅ **Portable** - Works from any directory
✅ **Cross-platform** - Windows, Linux, macOS support
✅ **Auto-cleanup** - Stops servers on Ctrl+C
✅ **Error handling** - Graceful failure recovery
✅ **Status reporting** - Clear progress messages
✅ **Port management** - Automatic conflict resolution