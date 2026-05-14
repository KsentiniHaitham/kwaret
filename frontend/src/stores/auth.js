import { defineStore } from 'pinia'
import { ref, computed } from 'vue'
import api from '@/api'

export const useAuthStore = defineStore('auth', () => {
  const token = ref(localStorage.getItem('token') || null)
  const user = ref(null)

  const isLoggedIn = computed(() => !!token.value)

  async function login(email, password) {
    const res = await api.post('/auth/login', { email, password })
    token.value = res.data.token
    localStorage.setItem('token', res.data.token)
    await fetchUser()
  }

  async function register(data) {
    await api.post('/auth/register', data)
    await login(data.email, data.password)
  }

  async function fetchUser() {
    if (!token.value) return
    const res = await api.get('/auth/me')
    user.value = res.data
  }

  function logout() {
    token.value = null
    user.value = null
    localStorage.removeItem('token')
  }

  function setFromOAuth(jwtToken, userData) {
    token.value = jwtToken
    user.value = userData
    localStorage.setItem('token', jwtToken)
  }

  return { token, user, isLoggedIn, login, register, fetchUser, logout, setFromOAuth }
})
