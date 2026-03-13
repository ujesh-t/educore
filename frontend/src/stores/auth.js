import { defineStore } from 'pinia'
import { ref, computed } from 'vue'
import authService from '@/services/authService'

export const useAuthStore = defineStore('auth', () => {
  const token = ref(typeof localStorage !== 'undefined' ? localStorage.getItem('token') : null)
  const user = ref(typeof localStorage !== 'undefined' && localStorage.getItem('user') 
    ? JSON.parse(localStorage.getItem('user')) 
    : null)
  const loading = ref(false)
  const error = ref(null)

  const isAuthenticated = computed(() => !!token.value)
  const userRole = computed(() => user.value?.role?.name)
  const isSuperAdmin = computed(() => user.value?.is_super_admin || user.value?.role?.name === 'super_admin')
  const userSchool = computed(() => user.value?.school)
  const enabledModules = computed(() => user.value?.enabled_modules || [])

  async function login(credentials) {
    loading.value = true
    error.value = null

    try {
      const response = await authService.login(credentials)
      
      // API returns: {success, message, data: {token, user}}
      const success = response.success || response.data?.success
      const apiData = response.data || response
      
      if (!success) {
        throw new Error(response.message || response.data?.message || 'Login failed')
      }

      // Extract token and user from nested data object
      const tokenData = apiData.data?.token || apiData.token
      const userData = apiData.data?.user || apiData.user

      token.value = tokenData
      user.value = userData

      localStorage.setItem('token', token.value)
      localStorage.setItem('user', JSON.stringify(user.value))

      return response
    } catch (err) {
      error.value = err.message
      throw err
    } finally {
      loading.value = false
    }
  }

  async function register(userData) {
    loading.value = true
    error.value = null
    
    try {
      const response = await authService.register(userData)
      return response
    } catch (err) {
      error.value = err.message
      throw err
    } finally {
      loading.value = false
    }
  }

  async function logout() {
    try {
      await authService.logout()
    } finally {
      token.value = null
      user.value = null
      localStorage.removeItem('token')
      localStorage.removeItem('user')
    }
  }

  async function fetchUser() {
    if (!token.value) return

    loading.value = true
    try {
      const response = await authService.me()
      user.value = response.data?.user || response.user
    } catch (err) {
      token.value = null
      user.value = null
      localStorage.removeItem('token')
    } finally {
      loading.value = false
    }
  }

  async function resetPassword(email) {
    loading.value = true
    error.value = null
    
    try {
      await authService.resetPassword(email)
    } catch (err) {
      error.value = err.message
      throw err
    } finally {
      loading.value = false
    }
  }

  return {
    user,
    token,
    loading,
    error,
    isAuthenticated,
    userRole,
    isSuperAdmin,
    userSchool,
    enabledModules,
    login,
    register,
    logout,
    fetchUser,
    resetPassword,
  }
})
