import { ref, reactive, watch } from 'vue'

export function useUserPreferences() {
    // Reactive preferences state
    const preferences = reactive({
        // Onboarding preferences
        showFeatureSpotlights: true,
        showProfileCompletion: true,
        showWhatsNew: true,
        tourSpeed: 'normal',
        
        // Notification preferences
        emailNotifications: true,
        pushNotifications: true,
        digestFrequency: 'weekly',
        
        // Privacy preferences
        profileVisibility: 'alumni',
        showContactInfo: false,
        allowDirectMessages: true,
        
        // Display preferences
        theme: 'system',
        language: 'en',
        timezone: 'auto',
        
        // Feature preferences
        autoPlayVideos: false,
        showTips: true,
        compactMode: false
    })

    const isLoading = ref(false)
    const hasUnsavedChanges = ref(false)

    // Load preferences from localStorage and server
    const loadPreferences = async () => {
        isLoading.value = true
        
        try {
            // Load from localStorage first (for immediate UI response)
            const localPrefs = localStorage.getItem('userPreferences')
            if (localPrefs) {
                Object.assign(preferences, JSON.parse(localPrefs))
            }
            
            // Then load from server (for sync across devices)
            const response = await fetch('/api/user/preferences', {
                headers: {
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                }
            })
            
            if (response.ok) {
                const data = await response.json()
                if (data.success) {
                    Object.assign(preferences, data.preferences)
                    // Update localStorage with server data
                    localStorage.setItem('userPreferences', JSON.stringify(preferences))
                }
            }
        } catch (error) {
            console.error('Failed to load user preferences:', error)
        } finally {
            isLoading.value = false
        }
    }

    // Save preferences to server and localStorage
    const savePreferences = async (updatedPrefs = null) => {
        const prefsToSave = updatedPrefs || preferences
        
        try {
            // Save to localStorage immediately
            localStorage.setItem('userPreferences', JSON.stringify(prefsToSave))
            
            // Save to server
            const response = await fetch('/api/user/preferences', {
                method: 'PUT',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                },
                body: JSON.stringify(prefsToSave)
            })
            
            if (response.ok) {
                hasUnsavedChanges.value = false
                return true
            } else {
                throw new Error('Failed to save preferences to server')
            }
        } catch (error) {
            console.error('Failed to save user preferences:', error)
            return false
        }
    }

    // Update a specific preference
    const updatePreference = async (key, value) => {
        preferences[key] = value
        hasUnsavedChanges.value = true
        
        // Auto-save after a short delay
        setTimeout(() => {
            if (hasUnsavedChanges.value) {
                savePreferences()
            }
        }, 1000)
    }

    // Update multiple preferences at once
    const updatePreferences = async (updates) => {
        Object.assign(preferences, updates)
        hasUnsavedChanges.value = true
        
        // Auto-save after a short delay
        setTimeout(() => {
            if (hasUnsavedChanges.value) {
                savePreferences()
            }
        }, 1000)
    }

    // Reset preferences to defaults
    const resetPreferences = async () => {
        const defaultPrefs = {
            showFeatureSpotlights: true,
            showProfileCompletion: true,
            showWhatsNew: true,
            tourSpeed: 'normal',
            emailNotifications: true,
            pushNotifications: true,
            digestFrequency: 'weekly',
            profileVisibility: 'alumni',
            showContactInfo: false,
            allowDirectMessages: true,
            theme: 'system',
            language: 'en',
            timezone: 'auto',
            autoPlayVideos: false,
            showTips: true,
            compactMode: false
        }
        
        Object.assign(preferences, defaultPrefs)
        await savePreferences()
    }

    // Get preference value with fallback
    const getPreference = (key, fallback = null) => {
        return preferences[key] !== undefined ? preferences[key] : fallback
    }

    // Set preference value (alias for updatePreference)
    const setPreference = (key, value) => {
        return updatePreference(key, value)
    }

    // Check if a specific feature is enabled
    const isFeatureEnabled = (feature) => {
        const featureMap = {
            'feature-spotlights': 'showFeatureSpotlights',
            'profile-completion': 'showProfileCompletion',
            'whats-new': 'showWhatsNew',
            'email-notifications': 'emailNotifications',
            'push-notifications': 'pushNotifications',
            'tips': 'showTips',
            'auto-play-videos': 'autoPlayVideos'
        }
        
        const prefKey = featureMap[feature] || feature
        return getPreference(prefKey, true)
    }

    // Theme-related helpers
    const isDarkMode = () => {
        if (preferences.theme === 'dark') return true
        if (preferences.theme === 'light') return false
        
        // System preference
        return window.matchMedia('(prefers-color-scheme: dark)').matches
    }

    const applyTheme = () => {
        const isDark = isDarkMode()
        document.documentElement.classList.toggle('dark', isDark)
    }

    // Watch for theme changes
    watch(() => preferences.theme, applyTheme, { immediate: true })

    // Watch for system theme changes
    if (window.matchMedia) {
        window.matchMedia('(prefers-color-scheme: dark)').addEventListener('change', () => {
            if (preferences.theme === 'system') {
                applyTheme()
            }
        })
    }

    // Notification permission helpers
    const requestNotificationPermission = async () => {
        if ('Notification' in window) {
            const permission = await Notification.requestPermission()
            if (permission === 'granted') {
                updatePreference('pushNotifications', true)
            } else {
                updatePreference('pushNotifications', false)
            }
            return permission
        }
        return 'denied'
    }

    const hasNotificationPermission = () => {
        return 'Notification' in window && Notification.permission === 'granted'
    }

    // Export preferences for backup/import
    const exportPreferences = () => {
        return JSON.stringify(preferences, null, 2)
    }

    const importPreferences = async (prefsJson) => {
        try {
            const importedPrefs = JSON.parse(prefsJson)
            Object.assign(preferences, importedPrefs)
            await savePreferences()
            return true
        } catch (error) {
            console.error('Failed to import preferences:', error)
            return false
        }
    }

    return {
        // State
        preferences,
        isLoading,
        hasUnsavedChanges,
        
        // Actions
        loadPreferences,
        savePreferences,
        updatePreference,
        updatePreferences,
        resetPreferences,
        getPreference,
        setPreference,
        isFeatureEnabled,
        
        // Theme helpers
        isDarkMode,
        applyTheme,
        
        // Notification helpers
        requestNotificationPermission,
        hasNotificationPermission,
        
        // Import/Export
        exportPreferences,
        importPreferences
    }
}