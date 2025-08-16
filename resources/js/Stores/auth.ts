import { defineStore } from 'pinia'
import { ref, computed } from 'vue'
import { router } from '@inertiajs/vue3'

interface User {
  id: number
  name: string
  email: string
  email_verified_at?: string
  roles?: string[]
  permissions?: string[]
  avatar?: string
  institution_id?: string
}

export const useAuthStore = defineStore('auth', () => {
  const user = ref<User | null>(null)
  const isAuthenticated = computed(() => !!user.value)
  const isLoading = ref(false)

  const setUser = (userData: User | null) => {
    user.value = userData
  }

  const login = async (credentials: { email: string; password: string; remember?: boolean }) => {
    isLoading.value = true
    try {
      await router.post('/login', credentials)
    } finally {
      isLoading.value = false
    }
  }

  const logout = async () => {
    isLoading.value = true
    try {
      await router.post('/logout')
      user.value = null
    } finally {
      isLoading.value = false
    }
  }

  const register = async (userData: {
    name: string
    email: string
    password: string
    password_confirmation: string
  }) => {
    isLoading.value = true
    try {
      await router.post('/register', userData)
    } finally {
      isLoading.value = false
    }
  }

  const hasRole = (role: string): boolean => {
    return user.value?.roles?.includes(role) ?? false
  }

  const hasPermission = (permission: string): boolean => {
    return user.value?.permissions?.includes(permission) ?? false
  }

  const hasAnyRole = (roles: string[]): boolean => {
    return roles.some(role => hasRole(role))
  }

  const hasAnyPermission = (permissions: string[]): boolean => {
    return permissions.some(permission => hasPermission(permission))
  }

  return {
    user: computed(() => user.value),
    isAuthenticated,
    isLoading,
    setUser,
    login,
    logout,
    register,
    hasRole,
    hasPermission,
    hasAnyRole,
    hasAnyPermission
  }
})