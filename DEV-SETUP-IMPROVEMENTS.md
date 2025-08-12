# Development Setup Improvements

## Problem Description

The original `start-dev.bat` script had a critical issue where the Vite development server would stop working after the PHP/Laravel server started. This happened because:

- The original script started Vite in a minimized window (`/min`)
- Then ran Laravel server in the foreground of the main script window
- When the main script terminated or Laravel exited, it would kill the Vite process
- This left developers with a broken frontend development environment

## Root Cause Analysis

The issue stemmed from process dependency and window management:

1. **Process Hierarchy**: Vite was started as a child process of the main batch script
2. **Foreground Blocking**: Laravel server ran in the main script's foreground, blocking further execution
3. **Process Termination**: When the main script ended, all child processes (including Vite) were terminated
4. **Window Management**: Using `/min` flag made it difficult to monitor Vite's status and errors

## Key Improvements in `start-dev-improved.bat`

### 🔧 **Independent Process Management**
- Both Vite and Laravel servers now run in separate, persistent windows using `cmd /k`
- Each server operates independently without process hierarchy dependencies
- Servers can be stopped individually without affecting each other

### 📊 **Enhanced Monitoring & Status**
- Real-time status display showing both server URLs
- Clear visual indicators (✅) when servers are running
- Dedicated monitoring window that stays open for oversight
- Periodic status updates every 30 seconds

### ⏱️ **Robust Initialization**
- Improved Vite readiness detection using PowerShell HTTP requests
- 60-second timeout with progress indicators
- Graceful fallback if Vite doesn't respond within timeout
- Better error handling and user feedback

### 🌐 **Automatic Browser Integration**
- Automatically opens both development URLs in the default browser
- Saves developers time by eliminating manual URL entry
- Immediate access to both frontend and backend environments

### 🧹 **Better Cleanup & Setup**
- More thorough process cleanup at startup
- Enhanced cache clearing for Laravel
- Improved error messages and user guidance

## Usage Instructions

### Using the Improved Script (Recommended)

```bash
# Run the improved development setup
.\start-dev-improved.bat
```

**What happens:**
1. Cleans up any existing PHP/Node processes
2. Verifies PHP and Node.js installations
3. Clears Laravel caches
4. Starts Vite server in a persistent window
5. Waits for Vite to be ready (up to 60 seconds)
6. Starts Laravel server in another persistent window
7. Opens both URLs in your browser
8. Keeps a monitoring window open

### Using the Original Script

```bash
# Run the original development setup (not recommended)
.\start-dev.bat
```

**Note:** The original script may still experience the Vite termination issue.

## Server Access

- **Frontend (Vite)**: http://127.0.0.1:5100
- **Backend (Laravel)**: http://127.0.0.1:8080

## Troubleshooting Tips

### 🔍 **Vite Server Issues**
- Check the "Vite Dev Server - Alumni Platform" window for error messages
- Ensure port 5100 is not being used by another application
- Verify Node.js and npm are properly installed
- Try running `npm install` if dependencies are missing

### 🔍 **Laravel Server Issues**
- Check the "Laravel Server - Alumni Platform" window for error messages
- Ensure port 8080 is available
- Verify PHP path: `D:\DevCenter\xampp\php-8.3.23\php.exe`
- Check Laravel configuration and database connections

### 🔍 **General Issues**
- **Port Conflicts**: Change ports in the script if defaults are occupied
- **Permission Issues**: Run as administrator if needed
- **Path Issues**: Verify PHP installation path in the script
- **Cache Issues**: Laravel caches are cleared automatically, but you can run `php artisan config:clear` manually

### 🔍 **Browser Issues**
- If URLs don't open automatically, manually navigate to the server addresses
- Clear browser cache if experiencing asset loading issues
- Check browser console for JavaScript errors

## Benefits of the New Approach

### ✅ **Reliability**
- Eliminates the Vite termination problem
- More stable development environment
- Independent server lifecycle management

### ✅ **Developer Experience**
- Better visibility into server status
- Easier debugging with dedicated windows
- Automatic browser setup
- Clear error reporting

### ✅ **Flexibility**
- Stop/restart servers individually
- Monitor each service separately
- Better control over development workflow

### ✅ **Maintainability**
- Clearer script structure
- Better error handling
- More informative user feedback
- Easier to modify and extend

## Migration Guide

1. **Backup**: Keep your original `start-dev.bat` as a fallback
2. **Test**: Run `start-dev-improved.bat` to verify it works in your environment
3. **Customize**: Modify paths or ports if needed for your specific setup
4. **Adopt**: Use the improved script as your primary development launcher

## Technical Notes

- **Windows Compatibility**: Designed for Windows PowerShell environment
- **Process Management**: Uses `taskkill` for cleanup and `start` with `cmd /k` for persistence
- **HTTP Checking**: PowerShell `Invoke-WebRequest` for Vite readiness detection
- **Error Handling**: Comprehensive error checking and user feedback

---

**Recommendation**: Use `start-dev-improved.bat` for all development work to ensure a stable and reliable development environment.