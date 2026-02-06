# Step-by-Step Fix Instructions

## Prerequisites
- Backup your codebase before making changes
- Ensure you have search/replace capabilities in your IDE
- Test environment ready for validation

## Method 1: Automated Fix (Recommended)

### Step 1: Fix @/Components → @/components
```bash
# Using PowerShell (Windows)
Get-ChildItem -Path "d:\DevCenter\abuilds\alumate\resources\js" -Recurse -Include "*.vue","*.ts","*.js" | ForEach-Object {
    (Get-Content $_.FullName) -replace "@/Components", "@/components" | Set-Content $_.FullName
}

# Using find/sed (Linux/WSL)
find d:/DevCenter/abuilds/alumate/resources/js -type f \( -name "*.vue" -o -name "*.ts" -o -name "*.js" \) -exec sed -i 's|@/Components|@/components|g' {} +
```

### Step 2: Fix @/Layouts → @/layouts
```bash
# Using PowerShell (Windows)
Get-ChildItem -Path "d:\DevCenter\abuilds\alumate\resources\js" -Recurse -Include "*.vue","*.ts","*.js" | ForEach-Object {
    (Get-Content $_.FullName) -replace "@/Layouts", "@/layouts" | Set-Content $_.FullName
}

# Using find/sed (Linux/WSL)
find d:/DevCenter/abuilds/alumate/resources/js -type f \( -name "*.vue" -o -name "*.ts" -o -name "*.js" \) -exec sed -i 's|@/Layouts|@/layouts|g' {} +
```

## Method 2: IDE Search & Replace

### VS Code / IDE Instructions:
1. Open project in VS Code
2. Press `Ctrl+Shift+H` (Find and Replace in Files)
3. Set search scope to: `resources/js`
4. Enable regex mode

#### Replace 1: Components
- **Find**: `@/Components`
- **Replace**: `@/components`
- **Files to include**: `*.vue,*.ts,*.js`
- Click "Replace All"

#### Replace 2: Layouts
- **Find**: `@/Layouts`
- **Replace**: `@/layouts`
- **Files to include**: `*.vue,*.ts,*.js`
- Click "Replace All"

## Method 3: Manual File-by-File (Not Recommended)

### High Priority Files (Fix First):
1. `Pages/Analytics/FundraisingDashboard.vue`
2. `Pages/Dashboard.vue`
3. `Pages/Events.vue`
4. `Pages/Students/MentorshipHub.vue`
5. `Pages/Career/Goals.vue`

### For each file:
1. Open file
2. Find all instances of `@/Components` → replace with `@/components`
3. Find all instances of `@/Layouts` → replace with `@/layouts`
4. Save file

## Validation Steps

### Step 1: Verify Changes
```bash
# Check no uppercase Components/Layouts remain
grep -r "@/Components" d:/DevCenter/abuilds/alumate/resources/js/
grep -r "@/Layouts" d:/DevCenter/abuilds/alumate/resources/js/

# Should return no results if fixes are complete
```

### Step 2: Build Test
```bash
# Test Vite build
npm run build

# Test dev server
npm run dev
```

### Step 3: Runtime Test
1. Start development server
2. Navigate through key pages:
   - Dashboard
   - Analytics
   - Events
   - Student sections
   - Career pages
3. Check browser console for import errors

## Expected Results

### Before Fix:
```javascript
// ❌ WRONG - Will fail on Linux
import EventCard from '@/Components/EventCard.vue';
import AppLayout from '@/Layouts/AppLayout.vue';
```

### After Fix:
```javascript
// ✅ CORRECT - Works on all systems
import EventCard from '@/components/EventCard.vue';
import AppLayout from '@/layouts/AppLayout.vue';
```

## Troubleshooting

### If build still fails:
1. Clear Vite cache: `rm -rf node_modules/.vite`
2. Reinstall dependencies: `npm install`
3. Check for missed files: `grep -r "@/Components\|@/Layouts" resources/js/`

### If imports still fail:
1. Verify actual folder names match exactly
2. Check for typos in replacement
3. Ensure no hidden characters in paths

## Post-Fix Guidelines

### For Future Development:
1. **Always use lowercase**: `@/components`, `@/layouts`
2. **Never use uppercase**: `@/Components`, `@/Layouts`
3. **IDE Setup**: Configure auto-completion to suggest lowercase paths
4. **Code Review**: Check import casing in all PRs

### ESLint Rule (Recommended):
```json
{
  "rules": {
    "import/no-unresolved": ["error", { "caseSensitive": true }]
  }
}
```

This will catch case sensitivity issues during development.