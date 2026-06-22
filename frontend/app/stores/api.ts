import {defineStore} from 'pinia'
import axios from 'axios'
import {checkServerAccess} from "@/utils";
import {clearTable, removeEntry, retrieveData, retrieveDataPaginated, storeData} from "@/composables/useIndexedDB";
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

  const deleteUser = async (id: number) => {
    const res = await axios.delete(`${config.public.apiBase}/users/${id}`)
    if (res.status === 200) {
      await removeEntry('user', id)
    }
    return res;
  }

  /*************************
   *
   *  Incident Types
   *
   *************************/

  const getIncidentTypes = async (params?: QueryParams) => {
    if (await checkServerAccess()) {
      const response = await axios.get(`${config.public.apiBase}/incidentTypes`, {params})

      await clearTable('incidentTypes');
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

  const deleteEntity = async (id: number) => {
    const res = await axios.delete(`${config.public.apiBase}/entities/${id}`)
    if (res.status === 200) {
      await removeEntry('entities', id)
    }
    return res;
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

  const deleteEntityType = async (id: number) => {
    const res = await axios.delete(`${config.public.apiBase}/entityTypes/${id}`)
    if (res.status === 200) {
      await removeEntry('entityTypes', id);
    }
    return res;
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

  const deleteIncident = async (id: number) => {
    const res = await axios.delete(`${config.public.apiBase}/incidents/${id}`)
    if (res.status === 200) {
      await removeEntry('incidents', id);
    }
    return res;
  }

  /*************************
   *
   *  Incidents PCO
   *  TODO: Implementar lógica de storage offline
   *
   *************************/

  const getIncidentPCOs = (incidentId: number, params?: QueryParams) => {
    return axios.get(`${config.public.apiBase}/incidents/${incidentId}/pco`, {params})
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
   *  TODO: Implementar lógica de storage offline
   *
   *************************/

  const getIncidentLogistics = (incidentId: number, params?: QueryParams) => {
    return axios.get(`${config.public.apiBase}/incidents/${incidentId}/parties`, {params})
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

  const deleteIncidentState = async (id: number) => {
    const res = await axios.delete(`${config.public.apiBase}/incidentStates/${id}`)
    if (res.status === 200) {
      await removeEntry('incidentStates', id);
    }
    return res;
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

  const deleteIncidentPriority = async (id: number) => {
    const res = await axios.delete(`${config.public.apiBase}/incidentPriorities/${id}`)
    if (res.status === 200) {
      await removeEntry('incidentPriorities', id)
    }
    return res;
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

  const deleteVolunteer = async (id: number) => {
    const res = await axios.delete(`${config.public.apiBase}/volunteers/${id}`)
    if (res.status === 200) {
      await removeEntry('volunteers', id);
    }
    return res;
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

  const deleteFacility = async (id: number) => {
    const res = await axios.delete(`${config.public.apiBase}/facilities/${id}`);
    if (res.status === 200) {
      await removeEntry('facilities', id);
    }
    return res;
  }

  /*************************
   *
   *  Donation Good Types
   *
   *************************/

  const getDonationGoodTypes = async (params?: QueryParams) => {
    if (await checkServerAccess()) {
      const res = await axios.get(`${config.public.apiBase}/donationGoodsTypes`, {params})
      await storeData('donation_goods_types', res.data.data)
      return res;
    } else {
      console.debug("OFFLINE DATA")
      return await retrieveDataPaginated('donation_goods_types', params);
    }
  }

  const getDonationGoodType = async (id: number, params?: QueryParams) => {
    if (await checkServerAccess()) {
      const res = await axios.get(`${config.public.apiBase}/donationGoodsTypes/${id}`, {params})
      await storeData('donation_goods_types', res.data.data)
      return res;
    } else {
      console.debug("OFFLINE DATA")
      return await retrieveData('donation_goods_types', id);
    }
  }

  const createDonationGoodType = (params: any) => {
    return axios.post(`${config.public.apiBase}/donationGoodsTypes`, params)
  }

  const updateDonationGoodType = (id: number, params: any) => {
    return axios.put(`${config.public.apiBase}/donationGoodsTypes/${id}`, params)
  }

  const deleteDonationGoodType = async (id: number) => {
    const res = await axios.delete(`${config.public.apiBase}/donationGoodsTypes/${id}`)
    console.log(res)
    if (res.status === 200) {
      await removeEntry('donation_goods_types', id);
    }
    return res;
  }


  /*************************
   *
   *  Donation Log
   *
   *************************/

  const getDonationLogs = async (params?: QueryParams) => {
    if (await checkServerAccess()) {
      const res = await axios.get(`${config.public.apiBase}/donations`, {params})
      await storeData('donation_logs', res.data.data)
      return res;
    } else {
      console.debug("OFFLINE DATA")
      return await retrieveDataPaginated('donation_logs', params);
    }
  }

  const getDonationLog = async (id: number, params?: QueryParams) => {
    if (await checkServerAccess()) {
      const res = await axios.get(`${config.public.apiBase}/donations/${id}`, {params})
      await storeData('donation_logs', res.data.data)
      return res;
    } else {
      console.debug("OFFLINE DATA")
      return await retrieveData('donation_logs', id);
    }
  }

  const createDonationLog = (params: any) => {
    return axios.post(`${config.public.apiBase}/donations`, params)
  }

  const updateDonationLog = (id: number, params: any) => {
    return axios.put(`${config.public.apiBase}/donations/${id}`, params)
  }

  const deleteDonationLog = async (id: number) => {
    const res = await axios.delete(`${config.public.apiBase}/donations/${id}`)
    if (res.status === 200) {
      await removeEntry('donation_logs', id);
    }
    return res;
  }

  /*************************
   *
   *  Donation Statistics
   *
   *************************/

  const getStockStats = async () => {
    if(await checkServerAccess()) {
      return await axios.get(`${config.public.apiBase}/donationStatistics`);
    }
    throw new Error('Not Implemented');
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
    getDonationGoodTypes,
    getDonationGoodType,
    createDonationGoodType,
    updateDonationGoodType,
    deleteDonationGoodType,
    getDonationLogs,
    getDonationLog,
    createDonationLog,
    updateDonationLog,
    deleteDonationLog,
    getStockStats,
  }
})
