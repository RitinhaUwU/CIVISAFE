import { ref, computed } from 'vue'

export const useAuth = () => {
  const config = useRuntimeConfig()

  const currentUser = useState('user', () => null)
  const token = useCookie('token')

  const toast = useToast()

  function reset() {
    token.value = null
    currentUser.value = null
  }

  const isLoggedIn = computed(() => currentUser.value !== null)

  const currentUserID = computed(() => {
    return currentUser.value?.id
  })

  const isAuthenticated = async () => {
    if (!token.value) return false

    try {
      await getUser()
      return true
    } catch (err) {
      reset()
      return false
    }
  }

  const login = async (credentials) => {
    try {
      const res = await $fetch(`${config.public.apiBase}/login`, {
        method: 'POST',
        body: credentials
      })
      token.value = res.token
      await getUser()
      toast.add({
        title: 'Login efetuado com sucesso',
        color: 'green'
      })
      return currentUser.value
    } catch (err) {
      reset()
      toast.add({
        title: 'Credenciais inválidas',
        color: 'red'
      })
      throw err
    }
  }

  const logout = async () => {
    reset()
    toast.add({
      title: 'Sessão encerrada',
      color: 'blue'
    })
    await navigateTo('/')
  }

  const getUser = async () => {
    if (!token.value) return null
    const res = await $fetch(`${config.public.apiBase}/user`, {
      headers: {
        Authorization: `Bearer ${token.value}`
      }
    })
    currentUser.value = res
    return res
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
}
