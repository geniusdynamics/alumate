<?php

echo "🔧 Frontend Troubleshooting Script\n";
echo "==================================\n\n";

// Check if package.json exists
if (file_exists('../../package.json')) {
    echo "✅ package.json found\n";
} else {
    echo "❌ package.json not found\n";
    exit(1);
}

// Check if node_modules exists
if (is_dir('../../node_modules')) {
    echo "✅ node_modules directory exists\n";
} else {
    echo "⚠️  node_modules not found - you need to run 'npm install'\n";
}

// Check if vite.config.js exists
if (file_exists('../../vite.config.js')) {
    echo "✅ vite.config.js found\n";
} else {
    echo "❌ vite.config.js not found\n";
}

// Check if public/build directory exists
if (is_dir('../../public/build')) {
    echo "✅ public/build directory exists\n";
    
    // Check for manifest file
    if (file_exists('../../public/build/manifest.json')) {
        echo "✅ Build manifest found\n";
    } else {
        echo "⚠️  Build manifest not found - assets may not be built\n";
    }
} else {
    echo "❌ public/build directory not found - assets not built\n";
}

// Check main JS file
if (file_exists('../../resources/js/app.js')) {
    echo "✅ resources/js/app.js found\n";
} else {
    echo "❌ resources/js/app.js not found\n";
}

// Check main CSS file
if (file_exists('../../resources/css/app.css')) {
    echo "✅ resources/css/app.css found\n";
} else {
    echo "❌ resources/css/app.css not found\n";
}

echo "\n🚀 Recommended Actions:\n";
echo "======================\n";

if (!is_dir('../../node_modules')) {
    echo "1. Install dependencies: npm install\n";
}

if (!is_dir('../../public/build') || !file_exists('../../public/build/manifest.json')) {
    echo "2. Build assets: npm run build\n";
    echo "   OR for development: npm run dev\n";
}

echo "3. Clear Laravel caches:\n";
echo "   - php artisan config:clear\n";
echo "   - php artisan route:clear\n";
echo "   - php artisan view:clear\n";

echo "\n💡 Quick Fix Commands:\n";
echo "======================\n";
echo "npm install && npm run build\n";
echo "php artisan config:clear\n";
echo "php artisan route:clear\n";
echo "php artisan view:clear\n";

echo "\n🌐 After fixing, try visiting:\n";
echo "- http://localhost:8080/ (home page)\n";
echo "- http://localhost:8080/login (login page)\n";
echo "- http://localhost:8080/analytics/dashboard (analytics)\n";