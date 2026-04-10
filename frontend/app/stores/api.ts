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

  const getIncidentTypes = (params?: { page?: number; per_page?: number }) => {
    return axios.get(`${config.public.apiBase}/incidentTypes`, { params })
  }

  const getIncidentType = (code, params) => {
    return axios.get(`${config.public.apiBase}/incidentTypes/${code}`, params)
  }

  const createIncidentType = (params) => {
    return axios.post(`${config.public.apiBase}/incidentTypes`, params)
  }

  const updateIncidentType = (code, params) => {
    return axios.put(`${config.public.apiBase}/incidentTypes/${code}`, params)
  }

  const deleteIncidentType = (code) => {
    return axios.delete(`${config.public.apiBase}/incidentTypes/${code}`)
  }

  /*************************
   *
   *  Entities
   *
   *************************/

  const getEntities = (params?: { page?: number; per_page?: number }) => {
    return axios.get(`${config.public.apiBase}/entities`, { params })
  }

  const getEntity = async (id, params) => {
    return axios.get(`${config.public.apiBase}/entities/${id}`, params)
  }

  const createEntity = async (params) => {
    return axios.post(`${config.public.apiBase}/entities`, params)
  }

  const updateEntity = (id, params) => {
    return axios.put(`${config.public.apiBase}/entities/${id}`, params)
  }

  const deleteEntity = (id) => {
    return axios.delete(`${config.public.apiBase}/entities/${id}`)
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

  const getIncidentState = async (id, params) => {
    return axios.get(`${config.public.apiBase}/incidentStates/${id}`, params)
  }

  const createIncidentState = async (params) => {
    return axios.post(`${config.public.apiBase}/incidentStates`, params)
  }

  const updateIncidentState = (id, params) => {
    return axios.put(`${config.public.apiBase}/incidentStates/${id}`, params)
  }

  const deleteIncidentState = (id) => {
    return axios.delete(`${config.public.apiBase}/incidentStates/${id}`)
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
    getIncidentTypes,
    getIncidentType,
    updateIncidentType,
    deleteIncidentType,
    createIncidentType,
    getEntities,
    getEntity,
    updateEntity,
    deleteEntity,
    createEntity,
    getIncidents,
    getIncidentStates,
    getIncidentState,
    updateIncidentState,
    deleteIncidentState,
    createIncidentState,
    getIncidentPriorities
  }
})
