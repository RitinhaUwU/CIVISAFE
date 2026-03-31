import {defineStore} from 'pinia'
import axios from 'axios'

export const useApiStore = defineStore('api', () => {
  const config = useRuntimeConfig()

  const setBearerToken = (token: string) => {
    axios.defaults.headers.common['Authorization'] = `Bearer ${token}`
  }

  const removeBearerToken = () => {
    delete axios.defaults.headers.common['Authorization']
  }

  // Login
  const postLogin = async (credentials: {email: string, password: string }) => {
    const response = await axios.post(`${config.public.apiBase}/login`, credentials)
    localStorage.setItem('token', response.data.token) // response.data
    return response
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


  /*************************
   *
   *  Categories
   *
   *************************/

  const getCategories = (params?: { page?: number; per_page?: number }) => {
    return axios.get(`${config.public.apiBase}/categories`, { params })
  }

  /*************************
   *
   *  Entities
   *
   *************************/

  const getEntities = (params?: { page?: number; per_page?: number }) => {
    return axios.get(`${config.public.apiBase}/entities`, { params })
  }

  /*************************
   *
   *  Incidents
   *
   *************************/

  const getIncidents = (params?: { page?: number; per_page?: number }) => {
    return axios.get(`${config.public.apiBase}/incidents`, { params })
  }

  /*************************
   *
   *  Incident States
   *
   *************************/

  const getIncidentStates = (params?: { page?: number; per_page?: number }) => {
    return axios.get(`${config.public.apiBase}/incidentStates`, { params })
  }

  /*************************
   *
   *  Incident Priorities
   *
   *************************/

  const getIncidentPriorities = (params?: { page?: number; per_page?: number }) => {
    return axios.get(`${config.public.apiBase}/incidentPriorities`, { params })
  }

  return {
    setBearerToken,
    removeBearerToken,
    postLogin,
    postLogout,
    getAuthUser,
    getCategories,
    getEntities,
    getIncidents,
    getIncidentStates,
    getIncidentPriorities
  }
})
