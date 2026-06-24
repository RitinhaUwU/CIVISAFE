import {defineStore} from 'pinia'
import axios from 'axios'
import {checkServerAccess} from "@/utils";
import {retrieveData, retrieveDataPaginated, storeData} from "@/composables/useIndexedDB";
import type {QueryParams} from "@/types";

export const useApiStore = defineStore('api', () => {
  const config = useRuntimeConfig()

  const setBearerToken = (token: string) => {
    axios.defaults.headers.common['Authorization'] = `Bearer ${token}`
  }

  const removeBearerToken = () => {
    delete axios.defaults.headers.common['Authorization']
  }

  // Login
  const postLogin = async (credentials: { email: string, password: string }) => {
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

  const getUsers = async (params?: QueryParams) => {
    if (await checkServerAccess()) {
      const response = await axios.get(`${config.public.apiBase}/users`, {params})

      await storeData('users', response.data.data)

      return response;
    } else {
      console.debug("OFFLINE DATA")
      return await retrieveDataPaginated('users', params);
    }
  }

  const getUser = async (id: number, params?: QueryParams) => {
    if (await checkServerAccess()) {
      const response = await axios.get(`${config.public.apiBase}/users/${id}`, {params})

      await storeData('users', response.data.data)

      return response;
    } else {
      console.debug("OFFLINE DATA")
      return await retrieveData('users', id);
    }
  }

  const createUser = (params: any) => {
    return axios.post(`${config.public.apiBase}/users`, params)
  }

  const updateUser = (id: number, params: any) => {
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
   *  Incident Types
   *
   *************************/

  const getIncidentTypes = async (params?: QueryParams) => {
    if (await checkServerAccess()) {
      const response = await axios.get(`${config.public.apiBase}/incidentTypes`, {params})

      await storeData('incidentTypes', response.data.data)

      return response;
    } else {
      console.debug("OFFLINE DATA")
      return await retrieveDataPaginated('incidentTypes', params);
    }
  }

  const getIncidentType = async (id: number, params?: QueryParams) => {
    if (await checkServerAccess()) {
      const res = await axios.get(`${config.public.apiBase}/incidentTypes/${id}`, {params})
      await storeData('incidentTypes', res.data.data)
      return res;
    } else {
      console.debug("OFFLINE DATA")
      return await retrieveData('incidentTypes', id);
    }
  }

  const uploadIncidentTypesFile = (form: FormData) => {
    return axios.post(`${config.public.apiBase}/incidentTypes`, form)
  }

  /*************************
   *
   *  Entities
   *
   *************************/

  const getEntities = async (params?: QueryParams) => {
    if (await checkServerAccess()) {
      const res = await axios.get(`${config.public.apiBase}/entities`, {params})
      await storeData('entities', res.data.data);
      return res
    } else {
      console.debug("OFFLINE DATA")
      return await retrieveDataPaginated('entities', params);
    }
  }

  const getEntity = async (id: number, params?: QueryParams) => {
    if (await checkServerAccess()) {
      const res = await axios.get(`${config.public.apiBase}/entities/${id}`, {params})
      await storeData('entities', res.data.data)
      return res;
    } else {
      console.debug("OFFLINE DATA")
      return await retrieveData('entities', id);
    }
  }

  const createEntity = async (params: any) => {
    return axios.post(`${config.public.apiBase}/entities`, params)
  }

  const updateEntity = (id: number, params: any) => {
    return axios.put(`${config.public.apiBase}/entities/${id}`, params)
  }

  const deleteEntity = (id: number) => {
    return axios.delete(`${config.public.apiBase}/entities/${id}`)
  }

  const requestEntitySignedUrl = (filename: string) => {
    return axios.post(`${config.public.apiBase}/entities/uploadUrl`, {filename: filename});
  }

  const updateEntityLogo = (entityId: number, key: string) => {
    return axios.post(`${config.public.apiBase}/entities/${entityId}/upload`, {key: key})
  }

  /*************************
   *
   *  EntityTypes
   *
   *************************/

  const getEntityTypes = async (params?: QueryParams) => {
    if (await checkServerAccess()) {
      const res = await axios.get(`${config.public.apiBase}/entityTypes`, {params})
      await storeData('entityTypes', res.data.data)
      return res;
    } else {
      console.debug("OFFLINE DATA")
      return await retrieveDataPaginated('entityTypes', params);
    }
  }

  const getEntityType = async (id: number, params?: QueryParams) => {
    if (await checkServerAccess()) {
      const res = await axios.get(`${config.public.apiBase}/entityTypes/${id}`, {params})
      await storeData('entityTypes', res.data.data);
      return res;
    } else {
      console.debug("OFFLINE DATA")
      return await retrieveData('entityTypes', id);
    }
  }

  const createEntityType = async (params: any) => {
    return axios.post(`${config.public.apiBase}/entityTypes`, params)
  }

  const updateEntityType = (id: number, params: any) => {
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

  const getIncidents = async (params?: QueryParams) => {
    if (await checkServerAccess()) {
      const res = await axios.get(`${config.public.apiBase}/incidents`, {params})
      await storeData('incidents', res.data.data)
      return res;
    } else {
      console.debug("OFFLINE DATA")
      return await retrieveDataPaginated('incidents', params);
    }
  }

  const getIncident = async (id: number, params?: QueryParams) => {
    if (await checkServerAccess()) {
      const res = await axios.get(`${config.public.apiBase}/incidents/${id}`, {params})
      await storeData('incidents', res.data.data)
      return res;
    } else {
      console.debug("OFFLINE DATA")
      return await retrieveData('incidents', id);
    }
  }

  const createIncident = async (params: any) => {
    return axios.post(`${config.public.apiBase}/incidents`, params)
  }

  const updateIncident = (id: number, params: any) => {
    return axios.put(`${config.public.apiBase}/incidents/${id}`, params)
  }

  const deleteIncident = (id: number) => {
    return axios.delete(`${config.public.apiBase}/incidents/${id}`)
  }

  /*************************
   *
   *  Incidents PCO
   *
   *************************/

  const getIncidentPCOs = (incidentId: number, params?: QueryParams) => {
    return axios.get(`${config.public.apiBase}/incidents/${incidentId}/pco`, { params })
  }

  const createIncidentPCO = (incidentId: number, params: any) => {
    return axios.post(`${config.public.apiBase}/incidents/${incidentId}/pco`, params)
  }

  const updateIncidentPCO = (incidentId: number, pcoId: number, params: any) => {
    return axios.put(`${config.public.apiBase}/incidents/${incidentId}/pco/${pcoId}`, params)
  }

  /*************************
   *
   *  Incidents Logistic
   *
   *************************/

  const getIncidentLogistics = (incidentId: number, params?: QueryParams) => {
    return axios.get(`${config.public.apiBase}/incidents/${incidentId}/parties`, { params })
  }

  const createIncidentLogistic = (incidentId: number, params: any) => {
    return axios.post(`${config.public.apiBase}/incidents/${incidentId}/parties`, params)
  }

  const updateIncidentLogistic = (incidentId: number, logisticId: number, params: any) => {
    return axios.put(`${config.public.apiBase}/incidents/${incidentId}/parties/${logisticId}`, params)
  }

  /*************************
   *
   *  Incident States
   *
   *************************/

  const getIncidentStates = async (params?: QueryParams) => {
    if (await checkServerAccess()) {
      const res = await axios.get(`${config.public.apiBase}/incidentStates`, {params})
      await storeData('incidentStates', res.data.data)
      return res;
    } else {
      console.debug("OFFLINE DATA")
      return await retrieveDataPaginated('incidentStates', params);
    }
  }

  const getIncidentState = async (id: number, params?: QueryParams) => {
    if (await checkServerAccess()) {
      const res = await axios.get(`${config.public.apiBase}/incidentStates/${id}`, {params})
      await storeData('incidentStates', res.data.data)
      return res;
    } else {
      console.debug("OFFLINE DATA")
      return await retrieveData('incidentStates', id);
    }
  }

  const createIncidentState = async (params: any) => {
    return axios.post(`${config.public.apiBase}/incidentStates`, params)
  }

  const updateIncidentState = (id: number, params: any) => {
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

  const getIncidentPriorities = async (params?: QueryParams) => {
    if (await checkServerAccess()) {
      const res = await axios.get(`${config.public.apiBase}/incidentPriorities`, {params})
      await storeData('incidentPriorities', res.data.data)
      return res;
    } else {
      console.debug("OFFLINE DATA")
      return await retrieveDataPaginated('incidentPriorities', params);
    }
  }

  const getIncidentPriority = async (id: number, params?: QueryParams) => {
    if (await checkServerAccess()) {
      const res = await axios.get(`${config.public.apiBase}/incidentPriorities/${id}`, {params})
      await storeData('incidentPriorities', res.data.data)
      return res;
    } else {
      console.debug("OFFLINE DATA")
      return await retrieveData('incidentPriorities', id);
    }
  }

  const createIncidentPriority = async (params: any) => {
    return axios.post(`${config.public.apiBase}/incidentPriorities`, params)
  }

  const updateIncidentPriority = (id: number, params: any) => {
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

  const getVolunteers = async (params?: QueryParams) => {
    if (await checkServerAccess()) {
      const res = await axios.get(`${config.public.apiBase}/volunteers`, {params})
      await storeData('volunteers', res.data.data)
      return res;
    } else {
      console.debug("OFFLINE DATA")
      return await retrieveDataPaginated('volunteers', params);
    }
  }

  const getVolunteer = async (id: number, params?: QueryParams) => {
    if (await checkServerAccess()) {
      const res = await axios.get(`${config.public.apiBase}/volunteers/${id}`, {params})
      await storeData('volunteers', res.data.data)
      return res;
    } else {
      console.debug("OFFLINE DATA")
      return await retrieveData('volunteers', id);
    }
  }

  const createVolunteer = (params: any) => {
    return axios.post(`${config.public.apiBase}/volunteers`, params)
  }

  const updateVolunteer = (id: number, params: any) => {
    return axios.put(`${config.public.apiBase}/volunteers/${id}`, params)
  }

  const deleteVolunteer = (id: number) => {
    return axios.delete(`${config.public.apiBase}/volunteers/${id}`)
  }

  /*************************
   *
   *  Notifications
   *
   *************************/

  const getNotifications = () => {
    return axios.get(`${config.public.apiBase}/notifications`);
  }

  const readNotification = (uuid: string) => {
    return axios.delete(`${config.public.apiBase}/notifications/${uuid}`);
  }

  const readAllNotifications = () => {
    return axios.delete(`${config.public.apiBase}/notifications/`);
  }

  /*************************
   *
   *  Facilities
   *
   *************************/

  const getFacilities = async (params?: QueryParams) => {
    if (await checkServerAccess()) {
      const res = await axios.get(`${config.public.apiBase}/facilities`, {params})
      await storeData('facilities', res.data.data)
      return res;
    } else {
      console.debug("OFFLINE DATA")
      return await retrieveDataPaginated('facilities', params);
    }
  }

  const getFacility = async (id: number, params?: QueryParams) => {
    if (await checkServerAccess()) {
      const res = await axios.get(`${config.public.apiBase}/facilities/${id}`, {params})
      await storeData('facilities', res.data.data)
      return res;
    } else {
      console.debug("OFFLINE DATA")
      return await retrieveData('facilities', id);
    }
  }

  const createFacility = (params: any) => {
    return axios.post(`${config.public.apiBase}/facilities`, params)
  }

  const updateFacility = (id: number, params: any) => {
    return axios.put(`${config.public.apiBase}/facilities/${id}`, params)
  }

  const deleteFacility = (id: number) => {
    return axios.delete(`${config.public.apiBase}/facilities/${id}`)
  }

  // Facilities - Images
  const requestFacilitySignedUrl = (filename: string) => {
    return axios.post(`${config.public.apiBase}/facilities/uploadUrl`, {filename: filename});
  }

  const updateFacilityImage = (facilityId: number, key: string) => {
    return axios.post(`${config.public.apiBase}/facilities/${facilityId}/upload`, {key: key})
  }

  // Facilities - Documentos
  const uploadFacilityDocuments = (facilityId: number, files: File[]) => {
    const form = new FormData()
    files.forEach(file => form.append('files[]', file))
    return axios.post(`${config.public.apiBase}/facilities/${facilityId}/documents`, form)
  }

  const downloadFacilityDocument = async (facilityId: number, mediaId: number, filename: string) => {
    const response = await axios.get(`${config.public.apiBase}/facilities/${facilityId}/documents/${mediaId}/download`, { responseType: 'blob' })
    const url = URL.createObjectURL(response.data)
    const a = document.createElement('a')
    a.href = url
    a.download = filename
    a.click()
    URL.revokeObjectURL(url)
  }

  const deleteFacilityDocument = (facilityId: number, mediaId: number) => {
    return axios.delete(`${config.public.apiBase}/facilities/${facilityId}/documents/${mediaId}`)
  }

  /*************************
   *
   *  Timeline
   *
   *************************/

  const getIncidentTimeline = (incidentId: number) => {
    return axios.get(`${config.public.apiBase}/incidents/${incidentId}/timeline`)
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
    uploadIncidentTypesFile,
    getEntities,
    getEntity,
    updateEntity,
    deleteEntity,
    createEntity,
    requestEntitySignedUrl,
    updateEntityLogo,
    getEntityTypes,
    getEntityType,
    updateEntityType,
    deleteEntityType,
    createEntityType,
    getIncidents,
    getIncident,
    updateIncident,
    createIncident,
    deleteIncident,
    getIncidentPCOs,
    updateIncidentPCO,
    createIncidentPCO,
    getIncidentLogistics,
    updateIncidentLogistic,
    createIncidentLogistic,
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
    getNotifications,
    readNotification,
    readAllNotifications,
    getVolunteers,
    getVolunteer,
    updateVolunteer,
    deleteVolunteer,
    createVolunteer,
    getFacilities,
    getFacility,
    updateFacility,
    deleteFacility,
    createFacility,
    requestFacilitySignedUrl,
    updateFacilityImage,
    uploadFacilityDocuments,
    downloadFacilityDocument,
    deleteFacilityDocument,
    getIncidentTimeline
  }
})
