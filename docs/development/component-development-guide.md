# Component Development Guide

This guide covers Vue.js component development patterns, best practices, and conventions for the Alumate Platform frontend.

## Table of Contents

1. [Component Architecture](#component-architecture)
2. [Component Structure](#component-structure)
3. [Composition API Patterns](#composition-api-patterns)
4. [Props and Events](#props-and-events)
5. [State Management](#state-management)
6. [Composables](#composables)
7. [UI Components](#ui-components)
8. [Form Components](#form-components)
9. [Testing Components](#testing-components)
10. [Performance Optimization](#performance-optimization)

## Component Architecture

### Directory Structure

```
resources/js/
├── Components/
│   ├── ui/                    # Base UI components (shadcn/ui)
│   │   ├── button/
│   │   ├── card/
│   │   ├── dialog/
│   │   └── ...
│   ├── layout/                # Layout components
│   │   ├── AppLayout.vue
│   │   ├── Sidebar.vue
│   │   └── Header.vue
│   ├── common/                # Shared components
│   │   ├── DataTable.vue
│   │   ├── SearchInput.vue
│   │   └── Pagination.vue
│   ├── alumni/                # Feature-specific components
│   │   ├── AlumniCard.vue
│   │   ├── AlumniList.vue
│   │   └── AlumniProfile.vue
│   └── analytics/             # Analytics components
│       ├── MetricsCard.vue
│       ├── ChartWidget.vue
│       └── InsightPanel.vue
├── Pages/                     # Inertia.js pages
│   ├── Dashboard.vue
│   ├── Alumni/
│   │   ├── Index.vue
│   │   └── Show.vue
│   └── Analytics/
│       └── Dashboard.vue
├── composables/               # Vue composables
│   ├── useAlumni.ts
│   ├── useAnalytics.ts
│   └── useAuth.ts
├── stores/                    # Pinia stores
│   ├── auth.ts
│   └── notifications.ts
├── types/                     # TypeScript types
│   ├── alumni.ts
│   ├── analytics.ts
│   └── index.ts
└── utils/                     # Utility functions
    ├── formatters.ts
    └── validators.ts
```

### Component Hierarchy

```
App.vue
└── AppLayout.vue
    ├── Header.vue
    │   ├── Navigation.vue
    │   └── UserMenu.vue
    ├── Sidebar.vue
    │   └── SidebarNav.vue
    └── MainContent.vue
        └── [Page Components]
            └── [Feature Components]
                └── [UI Components]
```

## Component Structure

### Single File Component Template

```vue
<template>
  <div class="alumni-card">
    <!-- Template content -->
  </div>
</template>

<script setup lang="ts">
// 1. Imports
import { ref, computed, onMounted } from 'vue'
import { Button } from '@/Components/ui/button'
import type { Alumni } from '@/types/alumni'

// 2. Props definition
interface Props {
  alumni: Alumni
  showActions?: boolean
}

const props = withDefaults(defineProps<Props>(), {
  showActions: true,
})

// 3. Emits definition
interface Emits {
  (e: 'connect', id: number): void
  (e: 'view', alumni: Alumni): void
}

const emit = defineEmits<Emits>()

// 4. Reactive state
const isLoading = ref(false)
const isExpanded = ref(false)

// 5. Computed properties
const fullName = computed(() => {
  return `${props.alumni.firstName} ${props.alumni.lastName}`
})

// 6. Methods
const handleConnect = async () => {
  isLoading.value = true
  try {
    emit('connect', props.alumni.id)
  } finally {
    isLoading.value = false
  }
}

// 7. Lifecycle hooks
onMounted(() => {
  // Initialization logic
})
</script>

<style scoped>
.alumni-card {
  @apply bg-white dark:bg-gray-800 rounded-lg border border-gray-200 dark:border-gray-700 p-4;
}
</style>
```

### Component Naming Conventions

| Type | Convention | Example |
|------|------------|---------|
| Pages | PascalCase | `AlumniIndex.vue` |
| Components | PascalCase | `AlumniCard.vue` |
| Composables | camelCase with `use` prefix | `useAlumni.ts` |
| Stores | camelCase | `authStore.ts` |
| Types | PascalCase | `Alumni.ts` |

## Composition API Patterns

### Basic Component

```vue
<script setup lang="ts">
import { ref, computed, watch } from 'vue'

// Reactive state
const count = ref(0)
const name = ref('')

// Computed property
const doubleCount = computed(() => count.value * 2)

// Watcher
watch(name, (newValue, oldValue) => {
  console.log(`Name changed from ${oldValue} to ${newValue}`)
})

// Methods
const increment = () => {
  count.value++
}
</script>
```

### Component with Async Data

```vue
<script setup lang="ts">
import { ref, onMounted } from 'vue'
import { useAlumni } from '@/composables/useAlumni'
import type { Alumni } from '@/types/alumni'

const { fetchAlumni, isLoading, error } = useAlumni()

const alumni = ref<Alumni[]>([])

onMounted(async () => {
  alumni.value = await fetchAlumni()
})
</script>

<template>
  <div>
    <div v-if="isLoading" class="loading-spinner">Loading...</div>
    <div v-else-if="error" class="error-message">{{ error }}</div>
    <div v-else>
      <AlumniCard v-for="item in alumni" :key="item.id" :alumni="item" />
    </div>
  </div>
</template>
```

### Provide/Inject Pattern

```vue
<!-- Parent component -->
<script setup lang="ts">
import { provide, ref } from 'vue'

const theme = ref('light')
const toggleTheme = () => {
  theme.value = theme.value === 'light' ? 'dark' : 'light'
}

provide('theme', { theme, toggleTheme })
</script>

<!-- Child component -->
<script setup lang="ts">
import { inject } from 'vue'

const { theme, toggleTheme } = inject('theme')!
</script>
```

## Props and Events

### Props Definition

```vue
<script setup lang="ts">
import type { Alumni } from '@/types/alumni'

// Simple props
interface Props {
  title: string
  count?: number
  items: string[]
}

// Props with defaults
const props = withDefaults(defineProps<Props>(), {
  count: 0,
  items: () => [],
})

// Complex props
interface ComplexProps {
  alumni: Alumni
  config: {
    showAvatar: boolean
    maxConnections: number
  }
  onSelect?: (id: number) => void
}

const complexProps = defineProps<ComplexProps>()
</script>
```

### Events Definition

```vue
<script setup lang="ts">
// Define emits with types
interface Emits {
  (e: 'update:modelValue', value: string): void
  (e: 'submit', data: FormData): void
  (e: 'cancel'): void
  (e: 'select', id: number, name: string): void
}

const emit = defineEmits<Emits>()

// Usage
const handleSubmit = (data: FormData) => {
  emit('submit', data)
}

const handleSelect = (id: number, name: string) => {
  emit('select', id, name)
}
</script>
```

### v-model Support

```vue
<script setup lang="ts">
// Single v-model
const modelValue = defineModel<string>()

// Multiple v-models
const title = defineModel<string>('title')
const content = defineModel<string>('content')

// With default value
const count = defineModel<number>({ default: 0 })
</script>

<template>
  <input v-model="modelValue" />
  <input v-model="title" />
  <textarea v-model="content" />
</template>
```

## State Management

### Pinia Store

```typescript
// stores/alumni.ts
import { defineStore } from 'pinia'
import { ref, computed } from 'vue'
import type { Alumni } from '@/types/alumni'
import { alumniService } from '@/services/alumniService'

export const useAlumniStore = defineStore('alumni', () => {
  // State
  const alumni = ref<Alumni[]>([])
  const selectedAlumni = ref<Alumni | null>(null)
  const isLoading = ref(false)
  const error = ref<string | null>(null)

  // Getters
  const alumniCount = computed(() => alumni.value.length)
  const recentGraduates = computed(() => 
    alumni.value.filter(a => a.graduationYear >= new Date().getFullYear() - 2)
  )

  // Actions
  async function fetchAlumni(filters?: Record<string, any>) {
    isLoading.value = true
    error.value = null
    
    try {
      alumni.value = await alumniService.getAll(filters)
    } catch (e) {
      error.value = e instanceof Error ? e.message : 'Failed to fetch alumni'
    } finally {
      isLoading.value = false
    }
  }

  async function selectAlumni(id: number) {
    isLoading.value = true
    
    try {
      selectedAlumni.value = await alumniService.getById(id)
    } catch (e) {
      error.value = e instanceof Error ? e.message : 'Failed to fetch alumni'
    } finally {
      isLoading.value = false
    }
  }

  function clearSelection() {
    selectedAlumni.value = null
  }

  return {
    // State
    alumni,
    selectedAlumni,
    isLoading,
    error,
    // Getters
    alumniCount,
    recentGraduates,
    // Actions
    fetchAlumni,
    selectAlumni,
    clearSelection,
  }
})
```

### Using Store in Components

```vue
<script setup lang="ts">
import { storeToRefs } from 'pinia'
import { useAlumniStore } from '@/stores/alumni'

const alumniStore = useAlumniStore()

// Destructure reactive state
const { alumni, isLoading, error } = storeToRefs(alumniStore)

// Actions can be destructured directly
const { fetchAlumni, selectAlumni } = alumniStore

// Fetch on mount
onMounted(() => {
  fetchAlumni()
})
</script>
```

## Composables

### Creating Composables

```typescript
// composables/useAlumni.ts
import { ref, computed } from 'vue'
import { router } from '@inertiajs/vue3'
import type { Alumni, AlumniFilters } from '@/types/alumni'

export function useAlumni() {
  const alumni = ref<Alumni[]>([])
  const isLoading = ref(false)
  const error = ref<string | null>(null)
  const filters = ref<AlumniFilters>({})

  const filteredAlumni = computed(() => {
    return alumni.value.filter(a => {
      if (filters.value.graduationYear && a.graduationYear !== filters.value.graduationYear) {
        return false
      }
      if (filters.value.major && !a.major.toLowerCase().includes(filters.value.major.toLowerCase())) {
        return false
      }
      return true
    })
  })

  async function fetchAlumni(params?: AlumniFilters) {
    isLoading.value = true
    error.value = null

    try {
      const response = await fetch('/api/alumni?' + new URLSearchParams(params as any))
      if (!response.ok) throw new Error('Failed to fetch')
      alumni.value = await response.json()
    } catch (e) {
      error.value = e instanceof Error ? e.message : 'Unknown error'
    } finally {
      isLoading.value = false
    }
  }

  function setFilters(newFilters: AlumniFilters) {
    filters.value = { ...filters.value, ...newFilters }
  }

  function clearFilters() {
    filters.value = {}
  }

  return {
    alumni,
    filteredAlumni,
    isLoading,
    error,
    filters,
    fetchAlumni,
    setFilters,
    clearFilters,
  }
}
```

### Composable with Lifecycle

```typescript
// composables/useWindowSize.ts
import { ref, onMounted, onUnmounted } from 'vue'

export function useWindowSize() {
  const width = ref(window.innerWidth)
  const height = ref(window.innerHeight)

  const handleResize = () => {
    width.value = window.innerWidth
    height.value = window.innerHeight
  }

  onMounted(() => {
    window.addEventListener('resize', handleResize)
  })

  onUnmounted(() => {
    window.removeEventListener('resize', handleResize)
  })

  return { width, height }
}
```

### Composable with Async State

```typescript
// composables/useAsync.ts
import { ref, computed } from 'vue'

export function useAsync<T>(asyncFn: () => Promise<T>) {
  const data = ref<T | null>(null)
  const error = ref<Error | null>(null)
  const isLoading = ref(false)

  const execute = async () => {
    isLoading.value = true
    error.value = null

    try {
      data.value = await asyncFn()
    } catch (e) {
      error.value = e instanceof Error ? e : new Error('Unknown error')
    } finally {
      isLoading.value = false
    }
  }

  const isSuccess = computed(() => data.value !== null && error.value === null)
  const isError = computed(() => error.value !== null)

  return {
    data,
    error,
    isLoading,
    isSuccess,
    isError,
    execute,
  }
}
```

## UI Components

### Button Component

```vue
<!-- Components/ui/button/Button.vue -->
<script setup lang="ts">
import { computed } from 'vue'
import { cva, type VariantProps } from 'class-variance-authority'

const buttonVariants = cva(
  'inline-flex items-center justify-center rounded-md text-sm font-medium transition-colors focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring disabled:pointer-events-none disabled:opacity-50',
  {
    variants: {
      variant: {
        default: 'bg-primary text-primary-foreground hover:bg-primary/90',
        destructive: 'bg-destructive text-destructive-foreground hover:bg-destructive/90',
        outline: 'border border-input bg-background hover:bg-accent hover:text-accent-foreground',
        secondary: 'bg-secondary text-secondary-foreground hover:bg-secondary/80',
        ghost: 'hover:bg-accent hover:text-accent-foreground',
        link: 'text-primary underline-offset-4 hover:underline',
      },
      size: {
        default: 'h-10 px-4 py-2',
        sm: 'h-9 rounded-md px-3',
        lg: 'h-11 rounded-md px-8',
        icon: 'h-10 w-10',
      },
    },
    defaultVariants: {
      variant: 'default',
      size: 'default',
    },
  }
)

type ButtonVariants = VariantProps<typeof buttonVariants>

interface Props {
  variant?: ButtonVariants['variant']
  size?: ButtonVariants['size']
  disabled?: boolean
  loading?: boolean
}

const props = withDefaults(defineProps<Props>(), {
  variant: 'default',
  size: 'default',
  disabled: false,
  loading: false,
})

const classes = computed(() => buttonVariants({ variant: props.variant, size: props.size }))
</script>

<template>
  <button
    :class="classes"
    :disabled="disabled || loading"
  >
    <span v-if="loading" class="mr-2">
      <svg class="animate-spin h-4 w-4" viewBox="0 0 24 24">
        <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4" fill="none" />
        <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z" />
      </svg>
    </span>
    <slot />
  </button>
</template>
```

### Card Component

```vue
<!-- Components/ui/card/Card.vue -->
<script setup lang="ts">
interface Props {
  title?: string
  description?: string
  padding?: 'none' | 'sm' | 'md' | 'lg'
}

const props = withDefaults(defineProps<Props>(), {
  padding: 'md',
})

const paddingClasses = {
  none: '',
  sm: 'p-2',
  md: 'p-4',
  lg: 'p-6',
}
</script>

<template>
  <div class="bg-white dark:bg-gray-800 rounded-lg border border-gray-200 dark:border-gray-700 shadow-sm">
    <div v-if="title || $slots.header" class="border-b border-gray-200 dark:border-gray-700 px-4 py-3">
      <slot name="header">
        <h3 class="text-lg font-semibold text-gray-900 dark:text-white">{{ title }}</h3>
        <p v-if="description" class="text-sm text-gray-500 dark:text-gray-400">{{ description }}</p>
      </slot>
    </div>
    <div :class="paddingClasses[padding]">
      <slot />
    </div>
    <div v-if="$slots.footer" class="border-t border-gray-200 dark:border-gray-700 px-4 py-3">
      <slot name="footer" />
    </div>
  </div>
</template>
```

## Form Components

### Form Input Component

```vue
<!-- Components/form/FormInput.vue -->
<script setup lang="ts">
import { computed } from 'vue'

interface Props {
  label?: string
  error?: string
  hint?: string
  required?: boolean
  type?: 'text' | 'email' | 'password' | 'number' | 'tel' | 'url'
}

const props = withDefaults(defineProps<Props>(), {
  type: 'text',
  required: false,
})

const modelValue = defineModel<string | number>()

const inputId = computed(() => `input-${Math.random().toString(36).substr(2, 9)}`)

const inputClasses = computed(() => [
  'block w-full rounded-md border-0 py-1.5 text-gray-900 dark:text-white shadow-sm ring-1 ring-inset',
  'placeholder:text-gray-400 focus:ring-2 focus:ring-inset sm:text-sm sm:leading-6',
  'dark:bg-gray-800',
  props.error
    ? 'ring-red-300 focus:ring-red-500'
    : 'ring-gray-300 dark:ring-gray-600 focus:ring-primary-600',
])
</script>

<template>
  <div class="form-group">
    <label v-if="label" :for="inputId" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
      {{ label }}
      <span v-if="required" class="text-red-500">*</span>
    </label>
    
    <input
      :id="inputId"
      v-model="modelValue"
      :type="type"
      :class="inputClasses"
      :required="required"
    />
    
    <p v-if="hint && !error" class="mt-1 text-sm text-gray-500 dark:text-gray-400">
      {{ hint }}
    </p>
    
    <p v-if="error" class="mt-1 text-sm text-red-600 dark:text-red-400">
      {{ error }}
    </p>
  </div>
</template>
```

### Form Select Component

```vue
<!-- Components/form/FormSelect.vue -->
<script setup lang="ts">
import { computed } from 'vue'

interface Option {
  value: string | number
  label: string
  disabled?: boolean
}

interface Props {
  label?: string
  options: Option[]
  error?: string
  placeholder?: string
  required?: boolean
}

const props = withDefaults(defineProps<Props>(), {
  placeholder: 'Select an option',
  required: false,
})

const modelValue = defineModel<string | number>()

const selectId = computed(() => `select-${Math.random().toString(36).substr(2, 9)}`)
</script>

<template>
  <div class="form-group">
    <label v-if="label" :for="selectId" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
      {{ label }}
      <span v-if="required" class="text-red-500">*</span>
    </label>
    
    <select
      :id="selectId"
      v-model="modelValue"
      class="block w-full rounded-md border-0 py-1.5 text-gray-900 dark:text-white dark:bg-gray-800 shadow-sm ring-1 ring-inset ring-gray-300 dark:ring-gray-600 focus:ring-2 focus:ring-inset focus:ring-primary-600 sm:text-sm sm:leading-6"
      :required="required"
    >
      <option value="" disabled>{{ placeholder }}</option>
      <option
        v-for="option in options"
        :key="option.value"
        :value="option.value"
        :disabled="option.disabled"
      >
        {{ option.label }}
      </option>
    </select>
    
    <p v-if="error" class="mt-1 text-sm text-red-600 dark:text-red-400">
      {{ error }}
    </p>
  </div>
</template>
```

## Testing Components

### Component Test Example

```typescript
// tests/js/Components/AlumniCard.test.ts
import { describe, it, expect, vi } from 'vitest'
import { mount } from '@vue/test-utils'
import AlumniCard from '@/Components/alumni/AlumniCard.vue'

describe('AlumniCard', () => {
  const mockAlumni = {
    id: 1,
    name: 'John Doe',
    graduationYear: 2023,
    major: 'Computer Science',
    company: 'Tech Corp',
  }

  it('renders alumni information correctly', () => {
    const wrapper = mount(AlumniCard, {
      props: { alumni: mockAlumni },
    })

    expect(wrapper.text()).toContain('John Doe')
    expect(wrapper.text()).toContain('2023')
    expect(wrapper.text()).toContain('Computer Science')
  })

  it('emits connect event when button is clicked', async () => {
    const wrapper = mount(AlumniCard, {
      props: { alumni: mockAlumni },
    })

    await wrapper.find('[data-testid="connect-button"]').trigger('click')

    expect(wrapper.emitted('connect')).toBeTruthy()
    expect(wrapper.emitted('connect')![0]).toEqual([1])
  })

  it('shows loading state when connecting', async () => {
    const wrapper = mount(AlumniCard, {
      props: { alumni: mockAlumni, isConnecting: true },
    })

    const button = wrapper.find('[data-testid="connect-button"]')
    expect(button.attributes('disabled')).toBeDefined()
    expect(button.text()).toContain('Connecting')
  })

  it('hides actions when showActions is false', () => {
    const wrapper = mount(AlumniCard, {
      props: { alumni: mockAlumni, showActions: false },
    })

    expect(wrapper.find('[data-testid="connect-button"]').exists()).toBe(false)
  })
})
```

### Testing Composables

```typescript
// tests/js/composables/useAlumni.test.ts
import { describe, it, expect, vi, beforeEach } from 'vitest'
import { useAlumni } from '@/composables/useAlumni'

// Mock fetch
global.fetch = vi.fn()

describe('useAlumni', () => {
  beforeEach(() => {
    vi.clearAllMocks()
  })

  it('fetches alumni successfully', async () => {
    const mockAlumni = [{ id: 1, name: 'John Doe' }]
    
    ;(global.fetch as any).mockResolvedValueOnce({
      ok: true,
      json: () => Promise.resolve(mockAlumni),
    })

    const { alumni, fetchAlumni, isLoading, error } = useAlumni()

    await fetchAlumni()

    expect(alumni.value).toEqual(mockAlumni)
    expect(isLoading.value).toBe(false)
    expect(error.value).toBeNull()
  })

  it('handles fetch error', async () => {
    ;(global.fetch as any).mockRejectedValueOnce(new Error('Network error'))

    const { alumni, fetchAlumni, error } = useAlumni()

    await fetchAlumni()

    expect(alumni.value).toEqual([])
    expect(error.value).toBe('Network error')
  })

  it('filters alumni correctly', () => {
    const { alumni, filteredAlumni, setFilters } = useAlumni()

    alumni.value = [
      { id: 1, name: 'John', graduationYear: 2023, major: 'CS' },
      { id: 2, name: 'Jane', graduationYear: 2022, major: 'Business' },
    ]

    setFilters({ graduationYear: 2023 })

    expect(filteredAlumni.value).toHaveLength(1)
    expect(filteredAlumni.value[0].name).toBe('John')
  })
})
```

## Performance Optimization

### Lazy Loading Components

```vue
<script setup lang="ts">
import { defineAsyncComponent } from 'vue'

// Lazy load heavy components
const HeavyChart = defineAsyncComponent(() => 
  import('@/Components/analytics/HeavyChart.vue')
)

const AlumniModal = defineAsyncComponent({
  loader: () => import('@/Components/alumni/AlumniModal.vue'),
  loadingComponent: () => import('@/Components/common/LoadingSpinner.vue'),
  delay: 200,
  timeout: 3000,
})
</script>

<template>
  <Suspense>
    <HeavyChart :data="chartData" />
    <template #fallback>
      <div class="loading">Loading chart...</div>
    </template>
  </Suspense>
</template>
```

### Virtual Scrolling

```vue
<script setup lang="ts">
import { useVirtualList } from '@vueuse/core'

const props = defineProps<{
  items: any[]
}>()

const { list, containerProps, wrapperProps } = useVirtualList(
  () => props.items,
  {
    itemHeight: 80,
    overscan: 10,
  }
)
</script>

<template>
  <div v-bind="containerProps" class="h-[400px] overflow-auto">
    <div v-bind="wrapperProps">
      <div
        v-for="{ data, index } in list"
        :key="index"
        class="h-20 border-b"
      >
        {{ data.name }}
      </div>
    </div>
  </div>
</template>
```

### Memoization

```vue
<script setup lang="ts">
import { computed, shallowRef } from 'vue'

// Use shallowRef for large objects that don't need deep reactivity
const largeDataset = shallowRef<any[]>([])

// Memoize expensive computations
const expensiveComputation = computed(() => {
  return largeDataset.value.reduce((acc, item) => {
    // Complex calculation
    return acc + item.value
  }, 0)
})
</script>
```

## Best Practices Summary

### Do's
- ✅ Use TypeScript for type safety
- ✅ Follow single responsibility principle
- ✅ Use composables for reusable logic
- ✅ Implement proper prop validation
- ✅ Use scoped styles
- ✅ Write unit tests for components
- ✅ Use lazy loading for heavy components

### Don'ts
- ❌ Mutate props directly
- ❌ Use inline styles excessively
- ❌ Create deeply nested components
- ❌ Skip error handling
- ❌ Ignore accessibility
- ❌ Use v-if and v-for on same element

---

**Related Documentation**:
- [Coding Standards](./coding-standards.md)
- [Testing Guide](./testing-guide.md)
- [Performance Optimization Guide](./performance-optimization-guide.md)

**Last Updated**: February 2026
