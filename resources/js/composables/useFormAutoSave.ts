import { ref, watch } from 'vue'
import type { FormComponentConfig } from '@/types/components'

export function useFormAutoSave(config: FormComponentConfig, formData: Record<string, any>) {
  const autoSaveStatus = ref<'idle' | 'saving' | 'saved' | 'error'>('idle')
  const autoSaveStatusText = ref('')
  const autoSaveTimer = ref<NodeJS.Timeout | null>(null)
  const lastSaveData = ref<string>('')

  const updateStatusText = () => {
    switch (autoSaveStatus.value) {
      case 'saving':
        autoSaveStatusText.value = 'Saving...'
        break
      case 'saved':
        autoSaveStatusText.value = `Saved at ${new Date().toLocaleTimeString()}`
        break
      case 'error':
        autoSaveStatusText.value = 'Failed to save'
        break
      default:
        autoSaveStatusText.value = ''
    }
  }

  const saveToStorage = async () => {
    if (!config.enableAutoSave) return

    const currentData = JSON.stringify(formData)
    
    // Don't save if data hasn't changed
    if (currentData === lastSaveData.value) {
      return
    }

    autoSaveStatus.value = 'saving'
    updateStatusText()

    try {
      // Save to localStorage
      const storageKey = `form_autosave_${config.title || 'form'}_${Date.now()}`
      const saveData = {
        formData,
        timestamp: new Date().toISOString(),
        config: {
          title: config.title,
          fields: config.fields.map(field => ({
            name: field.name,
            label: field.label,
            type: field.type
          }))
        }
      }

      localStorage.setItem(storageKey, JSON.stringify(saveData))
      
      // Clean up old auto-saves (keep only last 5)
      cleanupOldAutoSaves()

      // Optionally save to server
      if (config.submission.action) {
        await saveToServer(formData)
      }

      lastSaveData.value = currentData
      autoSaveStatus.value = 'saved'
      updateStatusText()

      // Reset to idle after 3 seconds
      setTimeout(() => {
        if (autoSaveStatus.value === 'saved') {
          autoSaveStatus.value = 'idle'
          updateStatusText()
        }
      }, 3000)

    } catch (error) {
      console.error('Auto-save failed:', error)
      autoSaveStatus.value = 'error'
      updateStatusText()

      // Reset to idle after 5 seconds
      setTimeout(() => {
        if (autoSaveStatus.value === 'error') {
          autoSaveStatus.value = 'idle'
          updateStatusText()
        }
      }, 5000)
    }
  }

  const saveToServer = async (data: Record<string, any>) => {
    try {
      const response = await fetch('/api/forms/autosave', {
        method: 'POST',
        headers: {
          'Content-Type': 'application/json',
          'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || ''
        },
        body: JSON.stringify({
          formData: data,
          formConfig: {
            title: config.title,
            fields: config.fields.map(field => ({
              name: field.name,
              label: field.label,
              type: field.type
            }))
          }
        })
      })

      if (!response.ok) {
        throw new Error(`Server auto-save failed: ${response.statusText}`)
      }

    } catch (error) {
      console.warn('Server auto-save failed, using local storage only:', error)
      // Don't throw - local storage save should still work
    }
  }

  const cleanupOldAutoSaves = () => {
    try {
      const keys = Object.keys(localStorage).filter(key => key.startsWith('form_autosave_'))
      
      if (keys.length > 5) {
        // Sort by timestamp and remove oldest
        const saves = keys.map(key => {
          try {
            const data = JSON.parse(localStorage.getItem(key) || '{}')
            return { key, timestamp: data.timestamp || '' }
          } catch {
            return { key, timestamp: '' }
          }
        }).sort((a, b) => b.timestamp.localeCompare(a.timestamp))

        // Remove oldest saves beyond the limit
        saves.slice(5).forEach(save => {
          localStorage.removeItem(save.key)
        })
      }
    } catch (error) {
      console.warn('Failed to cleanup old auto-saves:', error)
    }
  }

  const startAutoSave = () => {
    if (!config.enableAutoSave) return

    // Clear existing timer
    if (autoSaveTimer.value) {
      clearTimeout(autoSaveTimer.value)
    }

    // Set new timer
    const interval = (config.autoSaveInterval || 30) * 1000 // Convert to milliseconds
    autoSaveTimer.value = setTimeout(() => {
      saveToStorage()
    }, interval)
  }

  const stopAutoSave = () => {
    if (autoSaveTimer.value) {
      clearTimeout(autoSaveTimer.value)
      autoSaveTimer.value = null
    }
  }

  const saveProgress = async () => {
    await saveToStorage()
  }

  const loadAutoSavedData = (): Record<string, any> | null => {
    try {
      const keys = Object.keys(localStorage).filter(key => 
        key.startsWith('form_autosave_') && 
        key.includes(config.title || 'form')
      )

      if (keys.length === 0) return null

      // Get the most recent save
      let mostRecent = null
      let mostRecentTime = ''

      for (const key of keys) {
        try {
          const data = JSON.parse(localStorage.getItem(key) || '{}')
          if (data.timestamp > mostRecentTime) {
            mostRecentTime = data.timestamp
            mostRecent = data
          }
        } catch (error) {
          console.warn('Failed to parse auto-saved data:', error)
        }
      }

      return mostRecent?.formData || null
    } catch (error) {
      console.warn('Failed to load auto-saved data:', error)
      return null
    }
  }

  const clearAutoSavedData = () => {
    try {
      const keys = Object.keys(localStorage).filter(key => 
        key.startsWith('form_autosave_') && 
        key.includes(config.title || 'form')
      )

      keys.forEach(key => {
        localStorage.removeItem(key)
      })
    } catch (error) {
      console.warn('Failed to clear auto-saved data:', error)
    }
  }

  return {
    autoSaveStatus,
    autoSaveStatusText,
    startAutoSave,
    stopAutoSave,
    saveProgress,
    loadAutoSavedData,
    clearAutoSavedData
  }
}