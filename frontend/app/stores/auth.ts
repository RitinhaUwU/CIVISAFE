import { defineStore } from 'pinia'
import { ref, computed } from 'vue'
import { useApiStore } from './api'
import { useRouter } from 'vue-router'
import type {User} from "~/types";
import {createDB, retrieveData, storeData} from "~/composables/useIndexedDB";
import isOnline from "is-online";

export const useAuthStore = defineStore('auth', () => {
  const apiStore = useApiStore()
  const toast = useToast()
  const router = useRouter()

  const currentUser = ref<User|undefined>(undefined)
  const token = ref<string|null>(localStorage.getItem('token'))

  function reset() {
    token.value = null
    currentUser.value = undefined
    localStorage.removeItem('token')
    apiStore.removeBearerToken?.()
    useNotificationStore().disconnect();
  }

  const isLoggedIn = computed(() => currentUser.value !== undefined)

  const currentUserID = computed(() => { return currentUser.value?.id })

  const currentUserPermissions = computed(() => currentUser.value?.permissions)

  const roles  = computed(() => currentUser.value?.roles)

  const isAuthenticated = async () => {
    console.log("createdb")
    await createDB();

    console.log("token check")
    if (!token.value) return false

    try {
      console.log("set token", token.value)
      apiStore.setBearerToken(token.value)

      if(!await isOnline())
      {
        // @ts-ignore
        const userData = await retrieveData('users',  parseInt(token.value.split('|')[0]));

        console.log(userData)

        currentUser.value = userData;
      }
      else
      {
        console.log("Auth user")
        const res = await apiStore.getAuthUser()
        console.log("get user")
        currentUser.value = res.data.data
        console.log(res.data.data)

        const a = await retrieveData('users', res.data.data.id)
        console.log(a)

        await storeData('users', res.data.data);
      }

      //Tentamos ligar na mesma porque ele vai fazendo tentativas
      await useNotificationStore().connect()
      return true
    } catch (err) {
      reset()
      return false
    }
  }

  const login = async (credentials: { email: string, password: string }) => {
    if(!await isOnline())
    {
      toast.add({
        title: 'Sem Ligação à internet',
        description: 'Lige-se à internet para iniciar sessão',
        color: 'error'
      });

      reset();
      return;
    }

    try {
      const res = await apiStore.postLogin(credentials)

      token.value = res.data.token // res.data
      localStorage.setItem('token', token.value)
      apiStore.setBearerToken(token.value)

      await getUser()
      await useNotificationStore().connect()

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
    localStorage.setItem('userData', JSON.stringify(res.data.data))
    await storeData('users', res.data.data)
    return currentUser.value
  }

  const hasPermission = (permission: string) => {
    if(!currentUserPermissions.value) return false;

    return currentUserPermissions.value.includes(permission)
  }

  const hasRole = (role: string) => {
    if(!roles.value) return false;

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
