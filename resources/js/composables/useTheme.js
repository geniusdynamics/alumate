import { ref, computed, watch, onMounted } from 'vue'

const THEME_KEY = 'alumni-platform-theme'
const THEME_DARK = 'dark'
const THEME_LIGHT = 'light'
const THEME_SYSTEM = 'system'

// Global theme state
const currentTheme = ref(THEME_SYSTEM)
const systemPrefersDark = ref(false)

// Computed theme that resolves system preference
const resolvedTheme = computed(() => {
    if (currentTheme.value === THEME_SYSTEM) {
        return systemPrefersDark.value ? THEME_DARK : THEME_LIGHT
    }
    return currentTheme.value
})

const isDarkMode = computed(() => resolvedTheme.value === THEME_DARK)

// Apply theme to document
const applyTheme = (theme) => {
    const root = document.documentElement
    
    if (theme === THEME_DARK) {
        root.setAttribute('data-theme', 'dark')
        root.classList.add('dark')
    } else {
        root.setAttribute('data-theme', 'light')
        root.classList.remove('dark')
    }
    
    // Update meta theme-color for mobile browsers
    const metaThemeColor = document.querySelector('meta[name="theme-color"]')
    if (metaThemeColor) {
        metaThemeColor.setAttribute('content', theme === THEME_DARK ? '#1f2937' : '#ffffff')
    }
}

// Save theme preference
const saveTheme = (theme) => {
    try {
        localStorage.setItem(THEME_KEY, theme)
    } catch (error) {
        console.warn('Failed to save theme preference:', error)
    }
}

// Load theme preference
const loadTheme = () => {
    try {
        const saved = localStorage.getItem(THEME_KEY)
        if (saved && [THEME_DARK, THEME_LIGHT, THEME_SYSTEM].includes(saved)) {
            return saved
        }
    } catch (error) {
        console.warn('Failed to load theme preference:', error)
    }
    return THEME_SYSTEM
}

// Detect system theme preference
const detectSystemTheme = () => {
    if (typeof window !== 'undefined' && window.matchMedia) {
        const mediaQuery = window.matchMedia('(prefers-color-scheme: dark)')
        systemPrefersDark.value = mediaQuery.matches
        
        // Listen for changes
        mediaQuery.addEventListener('change', (e) => {
            systemPrefersDark.value = e.matches
        })
    }
}

// Initialize theme system
const initializeTheme = () => {
    detectSystemTheme()
    currentTheme.value = loadTheme()
    
    // Apply initial theme
    applyTheme(resolvedTheme.value)
}

export function useTheme() {
    // Initialize on first use
    onMounted(() => {
        if (typeof window !== 'undefined') {
            initializeTheme()
        }
    })
    
    // Watch for theme changes
    watch(resolvedTheme, (newTheme) => {
        applyTheme(newTheme)
    }, { immediate: true })
    
    // Watch for current theme changes to save preference
    watch(currentTheme, (newTheme) => {
        saveTheme(newTheme)
    })
    
    const setTheme = (theme) => {
        if ([THEME_DARK, THEME_LIGHT, THEME_SYSTEM].includes(theme)) {
            currentTheme.value = theme
        }
    }
    
    const toggleTheme = () => {
        if (currentTheme.value === THEME_SYSTEM) {
            // If system, switch to opposite of current resolved theme
            setTheme(resolvedTheme.value === THEME_DARK ? THEME_LIGHT : THEME_DARK)
        } else if (currentTheme.value === THEME_DARK) {
            setTheme(THEME_LIGHT)
        } else {
            setTheme(THEME_DARK)
        }
    }
    
    const cycleTheme = () => {
        const themes = [THEME_LIGHT, THEME_DARK, THEME_SYSTEM]
        const currentIndex = themes.indexOf(currentTheme.value)
        const nextIndex = (currentIndex + 1) % themes.length
        setTheme(themes[nextIndex])
    }
    
    return {
        currentTheme: computed(() => currentTheme.value),
        resolvedTheme,
        isDarkMode,
        systemPrefersDark: computed(() => systemPrefersDark.value),
        setTheme,
        toggleTheme,
        cycleTheme,
        themes: {
            DARK: THEME_DARK,
            LIGHT: THEME_LIGHT,
            SYSTEM: THEME_SYSTEM
        }
    }
}

// Export for direct use in setup functions
export { initializeTheme }