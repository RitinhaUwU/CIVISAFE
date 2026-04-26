import { defineStore } from 'pinia'
import { ref, computed } from 'vue'
import { useApiStore } from './api'
import { useToast } from '../../.nuxt/imports'
import { useRouter } from 'vue-router'
import axios from 'axios'

export const useAuthStore = defineStore('auth', () => {
  const apiStore = useApiStore()
  const toast = useToast()
  const router = useRouter()

  const currentUser = ref(undefined)
  const token = ref(localStorage.getItem('token'))

  function reset() {
    token.value = null
    currentUser.value = undefined
    localStorage.removeItem('token')
    apiStore.removeBearerToken?.()
  }

  const isLoggedIn = computed(() => currentUser.value !== undefined)

  const currentUserID = computed(() => { return currentUser.value?.id })

  const currentUserPermissions = computed(() => currentUser.value?.permissions)

  const roles  = computed(() => currentUser.value?.roles)

  const isAuthenticated = async () => {
    if (!token.value) return false

    try {
      apiStore.setBearerToken(token.value)
      const res = await apiStore.getAuthUser()
      currentUser.value = res.data.data
      return true
    } catch (err) {
      reset()
      return false
    }
  }

  const login = async (credentials) => {
    try {
      const res = await apiStore.postLogin(credentials)

      token.value = res.data.token // res.data
      localStorage.setItem('token', token.value)
      apiStore.setBearerToken(token.value)

      await getUser()

      toast.add({
        title: 'Login efetuado com sucesso',
        color: 'success'
      })

      return currentUser.value
    } catch (err) {
      reset()

      toast.add({
        title: 'Credenciais inválidas',
        color: 'error'
      })

      // throw err
    }
  }

  const logout = async () => {
    reset()
    toast.add({
      title: 'Sessão Encerrada',
      color: 'success'
    })

    if (router) {
      await router.push('/')
    }
  }

  const getUser = async () => {
    const res = await apiStore.getAuthUser()
    currentUser.value = res.data.data
    return currentUser.value
  }

  const hasPermission = (permission) => {
    return currentUserPermissions.value.includes(permission)
  }

  const hasRole = (role: string) => {
    return roles.value.includes(role)
  }

  const isAdmin = computed(() => hasRole('admin'))
  const isManager = computed(() => hasRole('manager'))
  const isUser = computed(() => hasRole('user'))

  return {
    currentUser,
    currentUserID,
    currentUserPermissions,
    roles,
    reset,
    isLoggedIn,
    isAuthenticated,
    login,
    logout,
    getUser,
    hasPermission,
    hasRole,
    isAdmin,
    isManager,
    isUser
  }
})
