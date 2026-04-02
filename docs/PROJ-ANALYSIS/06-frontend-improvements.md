# Frontend Architecture Improvements

## Executive Summary

This document outlines the strategy for improving the Alumate platform's frontend architecture, addressing component organization, bundle size, and performance issues.

### Current Issues

- **206 components** without clear categorization
- **71 pages** with potential overlap
- Bundle size concerns
- Inertia.js SSR complexity
- Potential TypeScript strictness gaps

### Target State

- Clear component taxonomy
- Lazy-loaded routes and components
- Optimized bundle size (<500KB initial load)
- Strict TypeScript compliance
- Improved Lighthouse scores

---

## Component Organization

### Proposed Structure

```
resources/js/
├── Components/
│   ├── ui/                    # Base UI components
│   │   ├── Button/
│   │   │   ├── Button.vue
│   │   │   ├── Button.spec.ts
│   │   │   └── index.ts
│   │   ├── Input/
│   │   ├── Modal/
│   │   └── ...
│   │
│   ├── features/              # Feature-specific components
│   │   ├── AlumniDirectory/
│   │   │   ├── DirectorySearch.vue
│   │   │   ├── GraduateCard.vue
│   │   │   └── FiltersPanel.vue
│   │   ├── JobBoard/
│   │   ├── Analytics/
│   │   └── ...
│   │
│   ├── layouts/               # Layout components
│   │   ├── AppHeader.vue
│   │   ├── Sidebar.vue
│   │   └── Footer.vue
│   │
│   └── forms/                 # Form components
│       ├── FormBuilder.vue
│       ├── FormGroup.vue
│       └── ...
│
├── Pages/                     # Route-based pages
│   ├── Graduate/
│   ├── Employer/
│   ├── Admin/
│   └── ...
│
└── composables/               # Vue composables
    ├── useGraduateSearch.ts
    ├── useJobFilters.ts
    └── ...
```

### Implementation Steps

**Week 1: Audit & Categorization**

```bash
#!/bin/bash
# scripts/frontend/audit-components.sh

echo "=== COMPONENT AUDIT ==="
echo ""

# Count components by type
echo "Component Distribution:"
find resources/js/Components -name "*.vue" | wc -l
echo "Total Vue components"

# Find large components (>300 lines)
echo ""
echo "Large Components (>300 lines):"
find resources/js/Components -name "*.vue" -exec sh -c '
    lines=$(wc -l < "$1")
    if [ $lines -gt 300 ]; then
        echo "$1: $lines lines"
    fi
' _ {} \;

# Find unused components
echo ""
echo "Potentially Unused Components (no imports):"
for file in resources/js/Components/**/*.vue; do
    componentName=$(basename "$file" .vue)
    if ! grep -r "$componentName" resources/js --include="*.vue" --include="*.ts" > /dev/null; then
        echo "- $componentName"
    fi
done
```

**Week 2-3: Reorganization**

Move components into new structure:

```bash
# Create new directories
mkdir -p resources/js/Components/ui
mkdir -p resources/js/Components/features/alumni-directory
mkdir -p resources/js/Components/features/job-board
mkdir -p resources/js/Components/features/analytics

# Move base UI components
mv resources/js/Components/Button resources/js/Components/ui/
mv resources/js/Components/Input resources/js/Components/ui/
# ... etc
```

**Week 4: Update Imports**

Use auto-import plugin to simplify:

```typescript
// vite.config.ts
import Components from 'unplugin-vue-components/vite'

export default defineConfig({
  plugins: [
    Components({
      dts: true,
      dirs: ['resources/js/Components/ui'],
      extensions: ['vue'],
    }),
  ],
})
```

---

## Code Splitting Strategy

### Route-Based Splitting

```typescript
// resources/js/app.ts
import { createInertiaApp } from '@inertiajs/vue3'

createInertiaApp({
  resolve: name => {
    // Dynamic import for code splitting
    const pages = import.meta.glob('./Pages/**/*.vue', { eager: false })
    return pages[`./Pages/${name}.vue`]()
  },
  // ... rest of config
})
```

### Component Lazy Loading

```vue
<!-- Resources/js/Pages/Graduate/Dashboard.vue -->
<script setup lang="ts">
import { defineAsyncComponent } from 'vue'

// Lazy load heavy components
const AnalyticsChart = defineAsyncComponent(() =>
  import('@/Components/features/analytics/AnalyticsChart.vue')
)

const JobMatchesList = defineAsyncComponent(() =>
  import('@/Components/features/job-board/JobMatchesList.vue')
)
</script>
```

---

## Performance Optimization

### Bundle Size Reduction

**Before:** ~1.2MB initial bundle  
**Target:** <500KB initial bundle

**Strategies:**

1. **Tree Shaking:**
```typescript
// BAD - imports all of lodash
import _ from 'lodash'

// GOOD - imports only what's needed
import debounce from 'lodash/debounce'
```

2. **Chart.js Optimization:**
```typescript
// Import only needed chart types
import { Chart, LineController, LineElement, PointElement } from 'chart.js'
Chart.register(LineController, LineElement, PointElement)
// Don't import bar charts, pie charts if not used
```

3. **Icon Optimization:**
```vue
<!-- BAD - imports entire icon set -->
import * as icons from '@heroicons/vue/24/solid'

<!-- GOOD - imports only needed icons -->
import { UserIcon } from '@heroicons/vue/24/solid'
```

### Lighthouse Score Improvement

**Current Scores (Estimated):**
- Performance: 65
- Accessibility: 80
- Best Practices: 85
- SEO: 90

**Target Scores:**
- Performance: 90+
- Accessibility: 95+
- Best Practices: 95+
- SEO: 95+

**Action Items:**

```markdown
## Performance
- [ ] Enable text compression (gzip/brotli)
- [ ] Implement image optimization (WebP format)
- [ ] Add resource hints (preload, prefetch)
- [ ] Reduce JavaScript execution time
- [ ] Eliminate render-blocking resources
- [ ] Minimize main thread work

## Accessibility
- [ ] Add ARIA labels to interactive elements
- [ ] Ensure color contrast ratios (4.5:1 minimum)
- [ ] Implement keyboard navigation
- [ ] Add skip links
- [ ] Test with screen readers

## Best Practices
- [ ] Use HTTPS exclusively
- [ ] Remove deprecated APIs
- [ ] Fix console errors and warnings
- [ ] Implement CSP headers

## SEO
- [ ] Add meta descriptions to all pages
- [ ] Implement structured data (Schema.org)
- [ ] Optimize page titles
- [ ] Add Open Graph tags
```

---

## TypeScript Strictness

### Enable Strict Mode

```json
// tsconfig.json
{
  "compilerOptions": {
    "strict": true,
    "noImplicitAny": true,
    "strictNullChecks": true,
    "noUnusedLocals": true,
    "noUnusedParameters": true,
    "noImplicitReturns": true
  }
}
```

### Fix Common Type Issues

**Before:**
```typescript
// Any types everywhere
function processUser(user: any) {
  return user.name.toUpperCase()
}
```

**After:**
```typescript
// Proper type definitions
interface User {
  id: number
  name: string
  email: string
}

function processUser(user: User): string {
  return user.name.toUpperCase()
}
```

---

## Success Criteria

✅ Components organized into clear categories  
✅ Initial bundle size <500KB  
✅ Lighthouse Performance score >90  
✅ Zero TypeScript errors in strict mode  
✅ All critical paths have lazy loading  
✅ Accessibility score >95  

---

## Next Steps

Continue reading:
- [Testing Strategy](./07-testing-strategy.md)
- [Implementation Roadmap](./08-implementation-roadmap.md)
