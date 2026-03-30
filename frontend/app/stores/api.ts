import { defineStore } from 'pinia'
import axios from 'axios'

export const useApiStore = defineStore('api', () => {
  const config = useRuntimeConfig()

  const setBearerToken = (token) => {
    axios.defaults.headers.common['Authorization'] = `Bearer ${token}`
  }

  const removeBearerToken = () => {
    delete axios.defaults.headers.common['Authorization']
  }

  // Login
  const postLogin = async (credentials) => {
    try {
      const response = await axios.post(`${config.public.apiBase}/login`, credentials)
      localStorage.setItem('token', response.data.token) // response.data
      return response
    } catch (erro) {
      console.error('Erro no login:', erro)
      throw erro
    }
  }

  // Logout
  const postLogout = async () => {
    removeBearerToken()
    localStorage.removeItem('token')
  }

  // Utilizador Autenticado
  const getAuthUser = () => {
    return axios.get(`${config.public.apiBase}/user`)
  }

  return {
    setBearerToken,
    removeBearerToken,
    postLogin,
    postLogout,
    getAuthUser
  }
})
