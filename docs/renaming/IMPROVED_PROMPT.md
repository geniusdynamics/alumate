# Improved Prompt for Case Sensitivity Import Fixes

## Context
Based on comprehensive analysis of the Alumate codebase, this improved prompt addresses critical case sensitivity issues that prevent Linux deployment.

## The Problem (Refined)

I have identified **critical case sensitivity mismatches** in my Vue.js/Laravel Inertia application that cause deployment failures on Linux systems. The analysis reveals:

### Specific Issues Found:
1. **Components Folder**: Actual folder is `resources/js/components/` (lowercase), but **80+ files** import from `@/Components` (uppercase)
2. **Layouts Folder**: Actual folder is `resources/js/layouts/` (lowercase), but **25+ files** import from `@/Layouts` (uppercase)
3. **Mixed Patterns**: Some files correctly use lowercase, others incorrectly use uppercase, creating inconsistency

### Impact:
- ✅ **Windows**: Works fine (case-insensitive filesystem)
- ❌ **Linux**: Complete import failures, broken builds, deployment failures
- 🔥 **Production**: Cannot deploy to Linux servers

## What I Need (Specific)

### 1. Systematic Analysis & Documentation
- [x] ~~Analyze all import statements in `resources/js/` directory~~
- [x] ~~Create comprehensive documentation in `docs/renaming/` folder~~
- [x] ~~Identify exact files and line numbers requiring fixes~~
- [x] ~~Document step-by-step fix procedures~~

### 2. Automated Fixes (Priority)
- [ ] **HIGH PRIORITY**: Fix all `@/Components` → `@/components` imports
- [ ] **HIGH PRIORITY**: Fix all `@/Layouts` → `@/layouts` imports  
- [ ] **MEDIUM PRIORITY**: Verify `@/Pages` consistency (appears correct)
- [ ] **LOW PRIORITY**: Check for other capitalized folder imports

### 3. Validation & Testing
- [ ] Verify no uppercase imports remain using grep/search
- [ ] Test Vite build process (`npm run build`)
- [ ] Test development server (`npm run dev`)
- [ ] Validate key application pages load without import errors

### 4. Prevention Measures
- [ ] Create ESLint rule for case-sensitive imports
- [ ] Update development guidelines
- [ ] Configure IDE auto-completion for lowercase paths

## Technical Specifications

### File Types to Process:
- `*.vue` files (Vue components)
- `*.ts` files (TypeScript)
- `*.js` files (JavaScript)

### Search Patterns:
```regex
@/Components  → @/components
@/Layouts     → @/layouts
```

### Scope:
- Directory: `d:\DevCenter\abuilds\alumate\resources\js\`
- Recursive: Yes, include all subdirectories
- Estimated files affected: **100+ files**

## Expected Deliverables

1. **Complete Fix Implementation**:
   - All import statements corrected
   - No case sensitivity mismatches remaining
   - Successful build on both Windows and Linux

2. **Comprehensive Testing**:
   - Build process validation
   - Runtime import verification
   - Key functionality testing

3. **Documentation Updates**:
   - Fix summary report
   - Updated development guidelines
   - Prevention measures implemented

## Success Criteria

### ✅ Fix Complete When:
- `grep -r "@/Components" resources/js/` returns **zero results**
- `grep -r "@/Layouts" resources/js/` returns **zero results**
- `npm run build` succeeds without import errors
- Application loads correctly on development server
- All major pages/components function properly

### 🚀 Deployment Ready When:
- Successful build on Linux environment
- No console import errors
- All features working as expected
- Prevention measures in place

## Priority Order

1. **CRITICAL**: Fix `@/Components` imports (affects 80+ files)
2. **CRITICAL**: Fix `@/Layouts` imports (affects 25+ files)
3. **HIGH**: Validate and test all changes
4. **MEDIUM**: Implement prevention measures
5. **LOW**: Documentation and guidelines updates

## Additional Context

### Framework Stack:
- **Frontend**: Vue.js 3 with TypeScript
- **Backend**: Laravel with Inertia.js
- **Build Tool**: Vite
- **Environment**: Windows development, Linux production

### Key Affected Areas:
- Analytics dashboards
- Student/Career sections  
- Event management
- Messaging system
- Component library
- Admin interfaces

This systematic approach ensures **zero import failures** and **successful Linux deployment** while maintaining code quality and preventing future issues.