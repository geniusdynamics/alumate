// Manual composable exports to prevent auto-import conflicts
// This allows explicit control over which functions are imported

// Theme Management - prioritize useTheme over useAppearance
export { default as useTheme } from './useTheme.js'

// Debounce - use local implementation over @vueuse/core
export { default as useDebounce } from './useDebounce.js'

// Other composables - only export if they don't exist as individual files
// Commented out to prevent auto-import conflicts
// export { default as useAuth } from './useAuth.ts'
// export { default as useApi } from './useApi.ts'
// export { default as useNotifications } from './useNotifications.ts'
// export { default as usePermissions } from './usePermissions.ts'
// export { default as useRealTimeUpdates } from './useRealTimeUpdates.ts'

// Don't re-export VueUse composables - let auto-import handle them
// export { 
//   useLocalStorage, 
//   useSessionStorage, 
//   useToggle, 
//   useCounter,
//   useMouse,
//   useWindowSize
// } from '@vueuse/core'

// Type definitions
export type {
  // Add any type exports if needed
}