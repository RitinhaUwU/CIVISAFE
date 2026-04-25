import {defineStore} from 'pinia'
import axios from 'axios'

export const useApiStore = defineStore('api', () => {
  const config = useRuntimeConfig()

  interface QueryParams {
    page?: number
    per_page?: number
    search?: string
  }

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
   *  Users
   *
   *************************/

  const getUsers = (params?: QueryParams) => {
    return axios.get(`${config.public.apiBase}/users`, { params })
  }

  const getUser = (id: number, params?: QueryParams) => {
    return axios.get(`${config.public.apiBase}/users/${id}`, params)
  }

  const createUser = (params) => {
    return axios.post(`${config.public.apiBase}/users`, params)
  }

  const updateUser = (id: number, params) => {
    return axios.put(`${config.public.apiBase}/users/${id}`, params)
  }

  const patchUser = (id: number, params: any) => {
    return axios.patch(`${config.public.apiBase}/users/${id}`, params)
  }

  const deleteUser = (id: number) => {
    return axios.delete(`${config.public.apiBase}/users/${id}`)
  }

  /*************************
   *
   *  Categories
   *
   *************************/

  const getIncidentTypes = (params?: QueryParams) => {
    return axios.get(`${config.public.apiBase}/incidentTypes`, { params })
  }

  const getIncidentType = (id: number, params?: QueryParams) => {
    return axios.get(`${config.public.apiBase}/incidentTypes/${id}`, params)
  }

  const createIncidentType = (params) => {
    return axios.post(`${config.public.apiBase}/incidentTypes`, params)
  }

  const updateIncidentType = (id: number, params) => {
    return axios.put(`${config.public.apiBase}/incidentTypes/${id}`, params)
  }

  const deleteIncidentType = (id: number) => {
    return axios.delete(`${config.public.apiBase}/incidentTypes/${id}`)
  }

  /*************************
   *
   *  Entities
   *
   *************************/

  const getEntities = (params?: QueryParams) => {
    return axios.get(`${config.public.apiBase}/entities`, { params })
  }

  const getEntity = async (id: number, params?: QueryParams) => {
    return axios.get(`${config.public.apiBase}/entities/${id}`, params)
  }

  const createEntity = async (params) => {
    return axios.post(`${config.public.apiBase}/entities`, params)
  }

  const updateEntity = (id: number, params) => {
    return axios.put(`${config.public.apiBase}/entities/${id}`, params)
  }

  const deleteEntity = (id: number) => {
    return axios.delete(`${config.public.apiBase}/entities/${id}`)
  }

  /*************************
   *
   *  Entities
   *
   *************************/

  const getEntityTypes = (params?: any) => {
    return axios.get(`${config.public.apiBase}/entityTypes`, { params })
  }

  const getEntityType = async (id: number, params?: QueryParams) => {
    return axios.get(`${config.public.apiBase}/entityTypes/${id}`, params)
  }

  const createEntityType = async (params) => {
    return axios.post(`${config.public.apiBase}/entityTypes`, params)
  }

  const updateEntityType = (id: number, params) => {
    return axios.put(`${config.public.apiBase}/entityTypes/${id}`, params)
  }

  const deleteEntityType = (id: number) => {
    return axios.delete(`${config.public.apiBase}/entityTypes/${id}`)
  }

  /*************************
   *
   *  Incidents
   *
   *************************/

  const getIncidents = (params?: QueryParams) => {
    return axios.get(`${config.public.apiBase}/incidents`, { params })
  }

  const getIncident = async (id: number, params?: QueryParams) => {
    return axios.get(`${config.public.apiBase}/incidents/${id}`, params)
  }

  /*************************
   *
   *  Incident States
   *
   *************************/

  const getIncidentStates = (params?: QueryParams) => {
    return axios.get(`${config.public.apiBase}/incidentStates`, { params })
  }

  const getIncidentState = async (id: number, params?: QueryParams) => {
    return axios.get(`${config.public.apiBase}/incidentStates/${id}`, params)
  }

  const createIncidentState = async (params) => {
    return axios.post(`${config.public.apiBase}/incidentStates`, params)
  }

  const updateIncidentState = (id: number, params) => {
    return axios.put(`${config.public.apiBase}/incidentStates/${id}`, params)
  }

  const deleteIncidentState = (id: number) => {
    return axios.delete(`${config.public.apiBase}/incidentStates/${id}`)
  }

  /*************************
   *
   *  Incident Priorities
   *
   *************************/

  const getIncidentPriorities = (params?: QueryParams) => {
    return axios.get(`${config.public.apiBase}/incidentPriorities`, { params })
  }

  const getIncidentPriority = async (id: number, params?: QueryParams) => {
    return axios.get(`${config.public.apiBase}/incidentPriorities/${id}`, params)
  }

  const createIncidentPriority = async (params) => {
    return axios.post(`${config.public.apiBase}/incidentPriorities`, params)
  }

  const updateIncidentPriority = (id: number, params) => {
    return axios.put(`${config.public.apiBase}/incidentPriorities/${id}`, params)
  }

  const deleteIncidentPriority = (id: number) => {
    return axios.delete(`${config.public.apiBase}/incidentPriorities/${id}`)
  }

  /*************************
   *
   *  Volunteers
   *
   *************************/

  const getVolunteers = (params?: QueryParams) => {
    return axios.get(`${config.public.apiBase}/volunteers`, { params })
  }

  const getVolunteer = (id: number, params?: QueryParams) => {
    return axios.get(`${config.public.apiBase}/volunteers/${id}`, params)
  }

  const createVolunteer = (params) => {
    return axios.post(`${config.public.apiBase}/volunteers`, params)
  }

  const updateVolunteer = (id: number, params) => {
    return axios.put(`${config.public.apiBase}/volunteers/${id}`, params)
  }

  const deleteVolunteer = (id: number) => {
    return axios.delete(`${config.public.apiBase}/volunteers/${id}`)
  }

  return {
    setBearerToken,
    removeBearerToken,
    postLogin,
    postLogout,
    getAuthUser,
    getUsers,
    getUser,
    patchUser,
    updateUser,
    deleteUser,
    createUser,
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
    getEntityTypes,
    getEntityType,
    updateEntityType,
    deleteEntityType,
    createEntityType,
    getIncidents,
    getIncident,
    getIncidentStates,
    getIncidentState,
    updateIncidentState,
    deleteIncidentState,
    createIncidentState,
    getIncidentPriorities,
    getIncidentPriority,
    updateIncidentPriority,
    deleteIncidentPriority,
    createIncidentPriority,
    getVolunteers,
    getVolunteer,
    updateVolunteer,
    deleteVolunteer,
    createVolunteer
  }
})
