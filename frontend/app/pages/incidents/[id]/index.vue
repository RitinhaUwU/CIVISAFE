<script setup lang="ts">
import {useRoute, useRouter} from 'vue-router'
import {useToast} from '@nuxt/ui/composables'
import type {BreadcrumbItem} from '@nuxt/ui/components/Breadcrumb.vue'
import {useApiStore} from '@/stores/api'
import {useAuthStore} from '@/stores/auth'
import * as z from 'zod';
import Map from '@/components/Map.vue'
import {computed} from 'vue'
import LogisticFormModal from '@/components/incidents/LogisticFormModal.vue'
import {usePaginatedSelect} from '@/composables/usePaginatedSelect'
import PCOFormModal from '@/components/incidents/PCOFormModal.vue'
import ConflictPCOModal from '@/components/incidents/ConflictPCOModal.vue'
import {toDatetimeLocal, incidentDisplayName} from "@/utils";
import moment from "moment";

const router = useRouter()
const route = useRoute()
const api = useApiStore()
const toast = useToast()

const saving = ref(false)
let incidentID: number = -1;

const typeMenu: any = useTemplateRef('typeMenu')
const stateMenu: any = useTemplateRef('stateMenu')
const priorityMenu: any = useTemplateRef('priorityMenu')
const incidentsMenu: any = useTemplateRef('incidentsMenu')

const types = usePaginatedSelect({
  fetcher: api.getIncidentTypes,
  menuRef: typeMenu,
  map: (t: any) => ({
    id: t.id,
    name: `${t.code} - ${t.type}`
  })
})

const states = usePaginatedSelect({
  fetcher: api.getIncidentStates,
  menuRef: stateMenu,
  map: (s: any) => ({
    id: s.id,
    name: s.name,
    terminates_incident: s.terminates_incident
  })
})

const priorities = usePaginatedSelect({
  fetcher: api.getIncidentPriorities,
  menuRef: priorityMenu,
  map: (p: any) => ({
    id: p.id,
    name: `${p.name} - ${p.description}`
  })
})

const incidents = usePaginatedSelect({
  fetcher: api.getIncidents,
  menuRef: incidentsMenu,
  filters: () => ({
    terminates_incident: !state.is_major
  }),
  map: (i: any) => ({
    id: i.id,
    name: incidentDisplayName(i)
  })
})

const tabs = computed(() => {
  const items = [
    {
      label: 'Geral',
      slot: 'geral',
      icon: 'i-lucide-users'
    }
  ]
  if (!state.is_major) {
    items.push(
      {
        label: 'Posto de Comando',
        slot: 'posto',
        icon: 'i-lucide-satellite-dish'
      },
      {
        label: 'Meios e Recursos',
        slot: 'logistica',
        icon: 'i-lucide-ambulance'
      }
    )
  }
  items.push({
    label: 'Fita de Tempo',
    slot: 'timeline',
    icon: 'i-lucide-history'
  })
  return items
})

const items = ref<BreadcrumbItem[]>([
  {
    label: 'Ocorrências',
    icon: 'i-lucide-flame',
    to: '/incidents'
  },
  {
    label: 'Dados de Ocorrência',
    icon: 'i-lucide-brick-wall-fire',
  }
])

const selectOptionSchema = z.object({
  id: z.number(),
  name: z.string()
})

const schema = z.object({
  is_major: z.boolean(),
  identifier: z.string().optional().nullable(),
  user_id: z.number(),
  start_datetime: z.string().min(1, 'A data de alerta é obrigatória'),
  end_datetime: z.string().optional().nullable(),
  incident_state_id: selectOptionSchema.nullable().refine(val => val !== null, {message: 'O estado é obrigatório'}),
  incident_priority_id: selectOptionSchema.nullable().refine(val => val !== null, {message: 'A prioridade é obrigatória'}),
  incident_type_id: selectOptionSchema.nullable().refine(val => val !== null, {message: 'O tipo de ocorrência é obrigatório'}),
  incident_id: z.union([selectOptionSchema, z.array(selectOptionSchema), z.null()]).optional(),
  children_incidents: z.array(z.any()).optional(),
  alert_source_relationship: z.string().optional().nullable(),
  alert_source_name: z.string().optional().nullable(),
  alert_source_contact: z.string().refine(value => !value || /^\+?[0-9]+(?: [0-9]+)*$/.test(value), 'Insira apenas números ou formato +000 000000000').optional().nullable(),
  operational_grid: z.string().optional().nullable(),
  coordinates: z.string().optional().nullable(),
  address: z.string().optional().nullable(),
  district: z.string().optional().nullable(),
  municipality: z.string().optional().nullable(),
  parish: z.string().optional().nullable(),
  common_place: z.string().optional().nullable(),
  obs: z.string().optional().nullable(),
  coordinates_pco: z.string().optional().nullable(),
  name_pco: z.string().nullable().optional(),
}).superRefine((data, ctx) => {
  if (data.is_major && !data.identifier?.trim()) {
    ctx.addIssue({
      code: 'custom',
      message: 'O nº de identificação de ocorrência é obrigatório',
      path: ['identifier']
    })
  }
})

type Schema = z.output<typeof schema>

const state = reactive<Partial<Schema>>({
  identifier: '',
  incident_type_id: null as any,
  incident_state_id: null as any,
  incident_priority_id: null as any,
  incident_id: null as any,
  user_id: null as any,
  user: null as any,
  start_datetime: '',
  end_datetime: '',
  operational_grid: '',
  coordinates: '',
  common_place: '',
  address: '',
  parish: '',
  municipality: '',
  district: '',
  is_major: false,
  alert_source_relationship: '',
  alert_source_name: '',
  alert_source_contact: '',
  obs: '',
  children_incidents: [] as any[],
  coordinates_pco: '',
  name_pco: '',
})

// Map
const mapCenter = computed(() => {
  if (!state.coordinates) {
    return [39.917504, -8.145675]
  }

  const [lat, lng] = state.coordinates.split(',').map(v => Number(v.trim()))

  if (Number.isNaN(lat) || Number.isNaN(lng)) {
    return [39.917504, -8.145675]
  }

  return [lat, lng]
})

function updateCoordinates(coords: { lat: number, lng: number }) {
  state.coordinates = `${coords.lat}, ${coords.lng}`
}

// Geral
const loadingIncident = ref(true)
const userTouchedAssociation = ref(false)

const displayIdentifier = computed(() => {
  if (!shouldHaveOwnIdentifier()) return ''
  return state.identifier || ''
})

const handleSaveGeral = async () => {
  if (!useAuthStore().hasPermission('INCIDENTS_UPDATE')) return;

  if (!await checkServerAccess()) {
    useToast().add({
      title: 'Sem ligação à internet!',
      description: 'Não é possível guardar alterações sem estar ligado à internet. Tente novamente mais tarde',
      color: 'error'
    });
    return;
  }

  const result = schema.safeParse(state)

  if (!result.success) {
    result.error.issues.forEach((err) => {
      toast.add({
        title: 'Erro de validação',
        description: err.message,
        color: 'error'
      })
    })

    return
  }

  saving.value = true

  try {
    const payload = {
      identifier: state.identifier,
      start_datetime: state.start_datetime != '' ? moment(state.start_datetime).toISOString() : '',
      end_datetime: state.end_datetime != '' ? moment(state.end_datetime).toISOString() : '',
      operational_grid: state.operational_grid,
      coordinates: state.is_major ? null : state.coordinates,
      common_place: state.common_place,
      address: state.address,
      parish: state.parish,
      municipality: state.municipality,
      district: state.district,
      is_major: state.is_major,
      alert_source_relationship: state.alert_source_relationship,
      alert_source_name: state.alert_source_name,
      alert_source_contact: state.alert_source_contact,
      obs: state.obs,
      incident_type_id: state.incident_type_id?.id,
      incident_priority_id: state.incident_priority_id?.id,
      incident_state_id: state.incident_state_id?.id,
      incident_id: state.is_major ? (state.incident_id as any[])?.map(i => i.id) : (state.incident_id as any)?.id ?? null,
      children_incidents: state.is_major ? (state.incident_id as any[])?.map(i => i.id) : [],
      coordinates_pco: state.coordinates_pco,
      name_pco: state.name_pco,
    }

    const response = await api.updateIncident(incidentID, payload)
    state.identifier = response.data.data.identifier

    toast.add({
      title: 'Sucesso',
      description: 'Ocorrência atualizada',
      color: 'success'
    })
  } catch (e: any) {
    toast.add({
      title: 'Erro',
      description: e.response?.data?.message ?? 'Erro ao atualizar',
      color: 'error'
    })

  } finally {
    saving.value = false
  }
}

const fetchIncident = async () => {
  userTouchedAssociation.value = false
  const data = (await api.getIncident(incidentID)).data.data

  state.is_major = data.is_major
  await incidents.fetchItems()

  if (data.is_major && data.children_incidents?.length) {
    incidents.prependSelected(data.children_incidents.map((i: any) => ({
      id: i.id,
      name: incidentDisplayName(i)
    })))
  }
  else if (!data.is_major && data.parentIncident) {
    incidents.prependSelected([{
      id: data.parentIncident.id,
      name: data.parentIncident.identifier
    }])
  }

  if (data.incidentType) {
    types.prependSelected([{
      id: data.incidentType.id,
      name: `${data.incidentType.code} - ${data.incidentType.type}`
    }])
  }

  if (data.incidentState) {
    states.prependSelected([{
      id: data.incidentState.id,
      name: data.incidentState.name
    }])
  }

  if (data.incidentPriority) {
    priorities.prependSelected([{
      id: data.incidentPriority.id,
      name: `${data.incidentPriority.name} - ${data.incidentPriority.description}`
    }])
  }

  await nextTick()

  Object.assign(state, {
    ...data,
    user_id: data.user?.id,
    user: data.user,
    incident_type_id: data.incidentType ? {id: data.incidentType.id, name: `${data.incidentType.code} - ${data.incidentType.type}`} : null,
    incident_state_id: data.incidentState ? {id: data.incidentState.id, name: data.incidentState.name, terminates_incident: data.incidentState.terminates_incident} : null,
    incident_priority_id: data.incidentPriority ? {id: data.incidentPriority.id, name: `${data.incidentPriority.name} - ${data.incidentPriority.description}`} : null,
    incident_id: data.is_major ? (data.children_incidents ?? []).map((i: any) => ({id: i.id, name: incidentDisplayName(i)})) : data.parentIncident ? {id: data.parentIncident.id, name: incidentDisplayName(data.parentIncident)} : null,    start_datetime: toDatetimeLocal(data.start_datetime),
    end_datetime: toDatetimeLocal(data.end_datetime),
  })

  await nextTick()

  loadingIncident.value = false
}

const showEndDateWarning = computed(() => {
  return (
    state.end_datetime &&
    !state.incident_state_id?.terminates_incident
  )
})

function shouldHaveOwnIdentifier() {
  if (state.is_major) return true
  return !state.incident_id
}

async function loadNextIdentifier() {
  if (!shouldHaveOwnIdentifier()) {
    state.identifier = ''
    return
  }

  if (!await checkServerAccess()) {
    useToast().add({
      title: 'Sem ligação à internet!',
      description: 'Não é possível carregar o identificador sem estar ligado à internet. Tente novamente mais tarde',
      color: 'error'
    });
    return;
  }

  try {
    const { data } = await api.getNextIncidentIdentifier()
    state.identifier = data.identifier
  } catch (e) {
    console.error(e)
  }
}

watch(() => state.is_major, async (isMajor, wasMajor) => {
  if (loadingIncident.value) return

  state.incident_id = isMajor ? [] : null
  await incidents.reset(true)

  if (isMajor) {
    state.coordinates = ''
  }
})

watch(() => state.incident_state_id, (newState) => {
  if (loadingIncident.value) return

  if (newState?.terminates_incident) {
    state.end_datetime = toDatetimeLocal(moment().toISOString())
  } else {
    state.end_datetime = state.end_datetime != '' ? toDatetimeLocal(state.end_datetime) : ''
  }
})

// Posto
const pcoList = ref<any[]>([])
const pcoModalOpen = ref(false)
const editingPCO = ref<any | null>(null)
const savingPCO = ref(false)
const conflictModalOpen = ref(false)
const conflictingPCO = ref<any | null>(null)
const pendingPCOPayload = ref<any | null>(null)

const isPCOActive = (item: any) => !item.end_pco_datetime

const fetchPCOList = async () => {
  const res = await api.getIncidentPCOs(incidentID)

  pcoList.value = res?.data?.data ?? []
}

const openCreatePCO = () => {
  editingPCO.value = null
  pcoModalOpen.value = true
}

const openEditPCO = (item: any) => {
  editingPCO.value = item
  pcoModalOpen.value = true
}

const findActiveConflict = (payload: any) => {
  return pcoList.value.find(p => p.function_pco === payload.function_pco && !p.end_pco_datetime && p.id !== editingPCO.value?.id)
}

const savePCOFromModal = (payload: any) => {
  if (!useAuthStore().hasPermission('INCIDENTS_UPDATE')) return;

  const conflict = findActiveConflict(payload)

  if (conflict) {
    conflictingPCO.value = conflict
    pendingPCOPayload.value = payload
    conflictModalOpen.value = true
    return
  }

  persistPCO(payload)
}

const confirmPCOConflict = async () => {
  if (!useAuthStore().hasPermission('INCIDENTS_UPDATE')) return;

  if (!pendingPCOPayload.value || !conflictingPCO.value) return

  if (!await checkServerAccess()) {
    useToast().add({
      title: 'Sem ligação à internet!',
      description: 'Não é possível guardar alterações sem estar ligado à internet. Tente novamente mais tarde',
      color: 'error'
    });
    return;
  }

  try {
    await api.updateIncidentPCO(incidentID, conflictingPCO.value.id, {
      ...conflictingPCO.value,
      end_pco_datetime: pendingPCOPayload.value.start_pco_datetime
    })
  } catch (e: any) {
    toast.add({
      title: 'Erro',
      description: e.response?.data?.message ?? 'Erro ao encerrar função ativa',
      color: 'error'
    })
    conflictModalOpen.value = false
    conflictingPCO.value = null
    pendingPCOPayload.value = null
    return
  }

  await persistPCO(pendingPCOPayload.value)

  conflictModalOpen.value = false
  conflictingPCO.value = null
  pendingPCOPayload.value = null
}

const persistPCO = async (payload: any) => {
  if (!useAuthStore().hasPermission('INCIDENTS_UPDATE')) return;

  if (!await checkServerAccess()) {
    useToast().add({
      title: 'Sem ligação à internet!',
      description: 'Não é possível guardar alterações sem estar ligado à internet. Tente novamente mais tarde',
      color: 'error'
    });
    return;
  }

  savingPCO.value = true

  try {
    if (editingPCO.value?.id) {
      await api.updateIncidentPCO(incidentID, editingPCO.value.id, payload)
    } else {
      await api.createIncidentPCO(incidentID, payload)
    }

    toast.add({
      title: 'Sucesso',
      description: editingPCO.value?.id ? 'Função atualizada' : 'Função adicionada',
      color: 'success'
    })

    await fetchPCOList()
    pcoModalOpen.value = false

  } catch (e: any) {
    if (e.response?.status === 422) {
      const data = e.response.data

      if (data?.type === 'overlap') {
        toast.add({
          title: 'Sobreposição temporal',
          description: `A função ${data.function_pco} já tem um registo que cobre este período. Ajuste as datas antes de guardar.`,
          color: 'warning'
        })
        return
      }

      conflictingPCO.value = e.response.data
      pendingPCOPayload.value = payload
      conflictModalOpen.value = true
      return
    }

    toast.add({
      title: 'Erro',
      description: e.response?.data?.message ?? 'Erro ao guardar função PCO',
      color: 'error'
    })

  } finally {
    savingPCO.value = false
  }
}

// Logística
const logisticModalOpen = ref(false)
const editingLogistic = ref<any | null>(null)
const logistics = ref<any[]>([])
const logisticTotals = ref({ total_vehicles: 0, total_humans: 0 })

const openCreateLogistic = () => {
  editingLogistic.value = null
  logisticModalOpen.value = true
}

const editLogistic = (item: any) => {
  editingLogistic.value = item
  logisticModalOpen.value = true
}

const saveLogistic = async (payload: any) => {
  if (!useAuthStore().hasPermission('INCIDENTS_UPDATE')) return;

  if (!await checkServerAccess()) {
    useToast().add({
      title: 'Sem ligação à internet!',
      description: 'Não é possível guardar alterações sem estar ligado à internet. Tente novamente mais tarde',
      color: 'error'
    });
    return;
  }

  try {
    let response

    if (editingLogistic.value?.id) {
      response = await api.updateIncidentLogistic(incidentID, editingLogistic.value.id, payload)
    }
    else {
      response = await api.createIncidentLogistic(incidentID, payload)
    }

    toast.add({
      title: response?.data?.meta?.merged ? 'Recurso atualizado' : 'Sucesso',
      description: response?.data?.meta?.merged ? 'Esta entidade já tinha um registo — os valores foram somados.' : 'Recurso guardado',
      color: response?.data?.meta?.merged ? 'info' : 'success'
    })
    await fetchLogistics()
    logisticModalOpen.value = false
  } catch (e: any) {
    toast.add({
      title: 'Erro',
      description: 'Erro ao guardar o recurso',
      color: 'error'
    })
  }
}

const fetchLogistics = async () => {
  const res = await api.getIncidentLogistics(incidentID)
  logistics.value = res?.data?.data ?? []
  logisticTotals.value = res?.data?.meta ?? { total_vehicles: 0, total_humans: 0 }
}

onMounted(async () => {
  if (!useAuthStore().hasPermission('INCIDENTS_LIST')) {
    await router.push('/inicio');
    return;
  }

  const routeID = route.params.id;
  if (typeof routeID !== 'string') {
    toast.add({
      title: 'Ocorrência inválida',
      description: 'O Caminho que o trouxe aqui aponta para uma ocorrência inválida',
      color: 'error'
    });
    await useRouter().push('/incidents');
    return;
  }

  incidentID = Number(routeID);

  await Promise.all([
    types.fetchItems(),
    states.fetchItems(),
    priorities.fetchItems(),
    fetchPCOList(),
    fetchLogistics(),
  ])
  await fetchIncident()
})
</script>

<template>
  <div class="min-h-screen flex flex-col">
    <header class="border-b border-stone-200 dark:border-stone-800">
      <div class="px-6 sm:px-8 py-6">
        <p class="text-[0.65rem] font-bold uppercase tracking-[0.15em] text-stone-400 mb-1">
          Ocorrência
        </p>
        <div class="flex items-center justify-between w-full gap-4">
          <h1 class="text-2xl sm:text-3xl font-bold tracking-tight truncate max-w-full">
            {{ state.identifier }}
          </h1>
        </div>
        <p class="text-sm text-stone-500">
          Criado por: {{ state.user?.name }}
        </p>
      </div>
    </header>
    <div class="flex-1 overflow-y-auto px-6 sm:px-8 py-8 space-y-8">
      <UBreadcrumb :items="items"/>
      <UTabs :items="tabs" class="w-full">
        <template #geral>
          <div class="space-y-6 pt-4">
            <section class="space-y-2">
              <h2 class="font-bold">Dados Gerais</h2>
              <USwitch v-model="state.is_major" label="Ocorrência Major"/>
              <UFormField v-if="state.is_major || !state.incident_id" label="Identificador" name="identifier">
                <UInput :model-value="displayIdentifier" class="w-full" disabled />
              </UFormField>
              <UFormField label="Tipo de Ocorrência" name="incident_type_id" class="sm:col-span-2">
                <USelectMenu
                  ref="typeMenu"
                  v-model="state.incident_type_id"
                  v-model:search-term="types.search.value"
                  :items="types.items.value"
                  :loading="types.loading.value"
                  label-key="name"
                  ignore-filter
                  class="w-full"
                />
              </UFormField>
              <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                <UFormField label="Estado" name="incident_state_id">
                  <USelectMenu
                    ref="stateMenu"
                    v-model="state.incident_state_id"
                    v-model:search-term="states.search.value"
                    :items="states.items.value"
                    :loading="states.loading.value"
                    label-key="name"
                    ignore-filter
                    class="w-full"
                  />
                </UFormField>
                <UFormField label="Prioridade" name="incident_priority_id">
                  <USelectMenu
                    ref="priorityMenu"
                    v-model="state.incident_priority_id"
                    v-model:search-term="priorities.search.value"
                    :items="priorities.items.value"
                    :loading="priorities.loading.value"
                    label-key="name"
                    ignore-filter
                    class="w-full"
                  />
                </UFormField>
              </div>
              <UFormField name="incident_id" label="Associar Evento:" class="sm:col-span-2">
                <div class="flex gap-2">
                  <USelectMenu
                    ref="incidentsMenu"
                    v-model="state.incident_id"
                    v-model:search-term="incidents.search.value"
                    :items="incidents.items.value"
                    :key="state.is_major ? 'multi' : 'single'"
                    label-key="name"
                    ignore-filter
                    :multiple="state.is_major"
                    class="w-full"
                    :placeholder="state.is_major ? 'Selecionar ocorrências associadas' : 'Selecionar ocorrência major'"
                    @update:model-value="userTouchedAssociation = true"
                  />
                  <UButton
                    v-if="!state.is_major && !!state.incident_id"
                    icon="i-lucide-x"
                    color="neutral"
                    variant="ghost"
                    @click="state.incident_id = null"
                  />
                </div>
              </UFormField>
            </section>
            <div class="h-px border-t border-stone-200 dark:border-stone-800"/>
            <section class="space-y-2">
              <h2 class="font-bold">Dados Alerta</h2>
              <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                <UFormField label="Data Alerta:" name="start_datetime">
                  <UInput type="datetime-local" v-model="state.start_datetime" class="w-full"/>
                </UFormField>
                <UFormField label="Data Fim:" name="end_datetime">
                  <UInput type="datetime-local" v-model="state.end_datetime" class="w-full" />
                  <UAlert
                    v-if="showEndDateWarning"
                    class="mt-2"
                    color="warning"
                    variant="soft"
                    icon="i-lucide-triangle-alert"
                    title="Estado e data de fim inconsistentes"
                    description="Foi definida uma data de fim, mas o estado selecionado não indica que a ocorrência está terminada. Esta informação será guardada, mas poderá representar uma inconsistência nos dados."
                  />
                </UFormField>
                <UFormField label="Fonte de Alerta:" name="alert_source_relationship">
                  <UInput v-model="state.alert_source_relationship" class="w-full"/>
                </UFormField>
                <UFormField label="Nome do Contacto:" name="alert_source_name">
                  <UInput v-model="state.alert_source_name" class="w-full"/>
                </UFormField>
                <UFormField label="Tlf. Contacto:" name="alert_source_contact">
                  <UInput v-model="state.alert_source_contact" class="w-full"/>
                </UFormField>
              </div>
            </section>
            <div class="h-px border-t border-stone-200 dark:border-stone-800"/>
            <section class="space-y-2">
              <h2 class="font-bold">Localização</h2>
              <UFormField v-if="!state.is_major" label="Coordenadas" name="coordenates">
                <UInput v-model="state.coordinates" class="w-full"/>
              </UFormField>
              <UFormField label="Grelha Operacional" name="operational_grid">
                <UInput v-model="state.operational_grid" class="w-full"/>
              </UFormField>
              <div class="grid grid-cols-1 sm:grid-cols-2 gap-4 mb-4">
                <UFormField label="Morada" name="address">
                  <UInput v-model="state.address" class="w-full"/>
                </UFormField>
                <UFormField label="Freguesia" name="parish">
                  <UInput v-model="state.parish" class="w-full"/>
                </UFormField>
                <UFormField label="Município" name="municipality">
                  <UInput v-model="state.municipality" class="w-full"/>
                </UFormField>
                <UFormField label="Distrito" name="district">
                  <UInput v-model="state.district" class="w-full"/>
                </UFormField>
              </div>
              <UFormField label="Ponto de Referência" name="common_place">
                <UInput v-model="state.common_place" class="w-full"/>
              </UFormField>
              <Map
                v-if="!state.is_major"
                :center="mapCenter"
                :selected-coords="mapCenter"
                :zoom="13"
                @map-click="updateCoordinates"
              />
            </section>
            <div class="h-px border-t border-stone-200 dark:border-stone-800"/>
            <section class="space-y-2">
              <h2 class="font-bold">Observações</h2>
              <UFormField name="obs">
                <UTextarea id="obs" v-model="state.obs" :rows="5" class="w-full"/>
              </UFormField>
            </section>
            <div class="h-px border-t border-stone-200 dark:border-stone-800"/>
            <section class="space-y-2">
              <h2 class="font-bold">Posto de Comando</h2>
              <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mb-4">
                <UFormField label="Coordenadas" name="coordenates_pco">
                  <UInput v-model="state.coordinates_pco" class="w-full"/>
                </UFormField>
                <UFormField label="Nome" name="name_pco">
                  <UInput v-model="state.name_pco" class="w-full"/>
                </UFormField>
              </div>
            </section>
            <UButton
              icon="i-lucide-save"
              color="primary"
              size="xl"
              :loading="saving"
              @click="handleSaveGeral"
              class="fixed bottom-6 right-6 z-1000 rounded-full w-16 h-16 shadow-lg flex items-center justify-center"
            />
          </div>
        </template>
        <template #posto>
          <div class="space-y-6 pt-4">
            <section class="space-y-2">
              <div class="flex justify-end mb-6">
                <UButton
                  icon="i-lucide-plus"
                  label="Nova Função"
                  @click="openCreatePCO"
                />
              </div>
              <div v-for="item in pcoList" :key="item.id" class="group flex items-center justify-between rounded-xl border border-stone-200 dark:border-stone-800 bg-white dark:bg-stone-900 px-4 py-3 shadow-sm transition hover:shadow-md hover:border-stone-300 dark:hover:border-stone-700">
                <div class="flex flex-col gap-1">
                  <div class="flex flex-row">
                    <p class="font-semibold text-stone-900 dark:text-white">{{ item.function_pco }}</p>
                    <UBadge class="ml-4 rounded-full" :color="isPCOActive(item) ? 'success' : 'error'" variant="soft" size="sm">
                      {{ isPCOActive(item) ? 'Ativo' : 'Inativo' }}
                    </UBadge>
                  </div>
                  <p class="text-sm text-stone-500 flex items-center gap-2 flex-wrap">
                    <span>{{ item.category_pco }}</span>
                    <span class="text-stone-300 dark:text-stone-600">•</span>
                    <span class="font-medium text-stone-600 dark:text-stone-300">{{ item.resp_pco }}</span>
                    <span class="text-stone-300 dark:text-stone-600">•</span>
                    <span>{{ item.contact1_pco }}</span>
                    <template v-if="item.contact2_pco">
                      <span class="text-stone-300 dark:text-stone-600">•</span>
                      <span>{{ item.contact2_pco }}</span>
                    </template>
                    <template v-if="item.localization_pco">
                      <span class="text-stone-300 dark:text-stone-600">•</span>
                      <span>{{ item.localization_pco }}</span>
                    </template>
                  </p>
                  <p class="text-xs text-stone-400 flex gap-3">
                    <span v-if="item.rob_pco">ROB: {{ item.rob_pco }}</span>
                    <span v-if="item.srp_pco">SRP: {{ item.srp_pco }}</span>
                  </p>
                </div>
                <div class="space-x-2 shrink-0">
                  <UButton
                    icon="i-lucide-pencil"
                    color="warning"
                    variant="soft"
                    data-testid="edit-pco"
                    class="opacity-0 group-hover:opacity-100 transition-all duration-200 hover:scale-110 hover:bg-yellow-100 dark:hover:bg-yellow-950/40"
                    :ui="{ rounded: 'rounded-full' }"
                    @click="openEditPCO(item)"
                  />
                </div>
              </div>
              <div v-if="pcoList.length === 0" class="text-center py-6 text-sm text-stone-400">
                Sem funções registadas
              </div>
            </section>
          </div>
        </template>
        <template #logistica>
          <div class="space-y-6 pt-4">
            <div class="flex justify-end">
              <UButton
                icon="i-lucide-plus"
                label="Novo Recurso"
                @click="openCreateLogistic"
              />
            </div>
            <UTable
              :data="logistics"
              :columns="[
                { accessorKey: 'entity.name', header: 'Entidade' },
                { accessorKey: 'vehicle_count', header: 'Nº de Veículos' },
                { accessorKey: 'human_count', header: 'Nº de Operacionais' },
                { id: 'actions', header: '' },
              ]"
            >
              <template #actions-cell="{ row }">
                <div class="flex gap-2 justify-end">
                  <UButton
                    icon="i-lucide-pencil"
                    color="warning"
                    variant="soft"
                    size="sm"
                    data-testid="edit-logistic"
                    class="group-hover:opacity-100 transition-all duration-200 hover:scale-110 hover:bg-yellow-100 dark:hover:bg-yellow-950/40"
                    :ui="{ rounded: 'rounded-full' }"
                    @click="editLogistic(row.original)"
                  />
                </div>
              </template>
              <template #body-bottom>
                <tr class="border-t-2 border-stone-300 dark:border-stone-600 font-semibold bg-stone-50 dark:bg-stone-800/50">
                  <td class="px-4 py-3 text-sm text-stone-600 dark:text-stone-400">Total</td>
                  <td class="px-4 py-3 text-sm">{{ logisticTotals.total_vehicles }}</td>
                  <td class="px-4 py-3 text-sm">{{ logisticTotals.total_humans }}</td>
                  <td />
                </tr>
              </template>
              <template #empty>
                <div class="flex flex-col items-center justify-center py-6 gap-2 text-stone-400">
                  <p class="text-sm">Sem equipas registados</p>
                </div>
              </template>
            </UTable>
          </div>
        </template>
        <template #timeline>
          <IncidentsTabsTimelineComponent :incidentID="incidentID"/>
        </template>
      </UTabs>
    </div>
  </div>
  <PCOFormModal
    v-model:open="pcoModalOpen"
    :model-value="editingPCO"
    @save="savePCOFromModal"
  />
  <ConflictPCOModal
    v-model:open="conflictModalOpen"
    :conflicting="conflictingPCO"
    @confirm="confirmPCOConflict"
  />
  <LogisticFormModal
    v-model:open="logisticModalOpen"
    :model-value="editingLogistic"
    @save="saveLogistic"
  />
</template>
