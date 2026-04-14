import { defineStore } from 'pinia'
import { ref, computed } from 'vue'
import { useApiStore } from './api'
import { useRouter } from 'vue-router'
import {useToast} from "@nuxt/ui/composables";

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

  const currentUserID = computed(() => {
    return currentUser.value?.id
  })

  const isAuthenticated = async () => {
    if (!token.value) return false

    try {
      apiStore.setBearerToken(token.value)
      const res = await apiStore.getAuthUser()
      currentUser.value = res.data
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
    currentUser.value = res.data
    return currentUser.value
  }

  return {
    currentUserID,
    currentUser,
    isLoggedIn,
    isAuthenticated,
    login,
    logout,
    reset,
    getUser
  }
})
