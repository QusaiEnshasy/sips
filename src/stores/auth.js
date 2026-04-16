import { defineStore } from 'pinia'
import { ref, computed } from 'vue'
import api from '@/services/api/api'
import { useToastStore } from './toast'

export const useAuthStore = defineStore('auth', () => {
  const initialServerUser = typeof window !== 'undefined' ? (window.__AUTH_USER__ || null) : null
  const initialStoredUser = localStorage.getItem('user') ? JSON.parse(localStorage.getItem('user')) : null

  // ========== State ==========
  const user = ref(initialServerUser || initialStoredUser)
  const token = ref(localStorage.getItem('token') || null)
  const userType = ref(localStorage.getItem('userType') || initialServerUser?.role || null)

  // ========== Getters ==========
  const isAuthenticated = computed(() => !!token.value || !!user.value)

  function setUser(userData) {
    user.value = userData
    userType.value = userData?.role || userData?.type || null
    if (userData) {
      localStorage.setItem('user', JSON.stringify(userData))
    } else {
      localStorage.removeItem('user')
    }
    if (userType.value) {
      localStorage.setItem('userType', userType.value)
    }
  }

  function setToken(newToken) {
    token.value = newToken
    if (newToken) {
      localStorage.setItem('token', newToken)
    } else {
      localStorage.removeItem('token')
    }
  }

  async function login(credentials, role = null) {
    try {
      const payload = role ? { ...credentials, role } : credentials
      const response = await api.post('/login', payload)

      if (response.data.status === 'success') {
        const { token, user: userData } = response.data.data
        setToken(token)
        setUser({ ...userData, type: role || userData.role })
        return { success: true, user: userData }
      }
      return { success: false, error: response.data.message }
    } catch (error) {
      console.error('Login error:', error)
      return {
        success: false,
        error: error.response?.data?.message || 'Login failed. Please try again.'
      }
    }
  }

  async function logout() {
    try {
      await api.post('/logout')
    } catch (error) {
      console.error('Logout API error:', error)
    } finally {
      user.value = null
      token.value = null
      userType.value = null
      localStorage.removeItem('token')
      localStorage.removeItem('userType')
      localStorage.removeItem('user')
    }
  }

  async function register(userData) {
    try {
      const response = await api.post('/register', userData)
      if (response.data.status === 'success') {
        return { success: true, data: response.data.data }
      }
      return { success: false, error: response.data.message }
    } catch (error) {
      return {
        success: false,
        error: error.response?.data?.message || 'Registration failed. Please try again.'
      }
    }
  }

  async function updateProfile(data) {
    try {
      const response = await api.put('/profile', data)
      if (response.data.status === 'success') {
        setUser(response.data.data.user)
        return { success: true, user: response.data.data.user }
      }
      return { success: false, error: response.data.message }
    } catch (error) {
      return {
        success: false,
        error: error.response?.data?.message || 'Profile update failed.'
      }
    }
  }

  async function changePassword(data) {
    try {
      const response = await api.post('/change-password', data)
      if (response.data.status === 'success') {
        return { success: true, message: response.data.message }
      }
      return { success: false, error: response.data.message }
    } catch (error) {
      return {
        success: false,
        error: error.response?.data?.message || 'Password change failed.'
      }
    }
  }

  async function verifyToken() {
    if (!token.value) return false
    try {
      const response = await api.get('/verify-token')
      return response.data.status === 'success'
    } catch (error) {
      logout()
      return false
    }
  }

  return {
    user,
    token,
    userType,
    isAuthenticated,
    setUser,
    setToken,
    login,
    logout,
    register,
    updateProfile,
    changePassword,
    verifyToken
  }
})
