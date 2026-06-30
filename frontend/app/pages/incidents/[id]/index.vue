<script setup lang="ts">
import {useRoute, useRouter} from 'vue-router'
import {useToast} from '@nuxt/ui/composables'
import type {BreadcrumbItem} from '@nuxt/ui/components/Breadcrumb.vue'
import type { TimelineItem } from '@nuxt/ui'
import {formatTimeAgoIntl} from '@vueuse/core'
import {useApiStore} from '@/stores/api'
import {useAuthStore} from '@/stores/auth'
import * as z from 'zod';
import Map from '@/components/Map.vue'
import {computed} from 'vue'
import LogisticFormModal from '@/components/incidents/LogisticFormModal.vue'
import {usePaginatedSelect} from '@/composables/usePaginatedSelect'
import PCOFormModal from '@/components/incidents/PCOFormModal.vue'
import ConflictPCOModal from '@/components/incidents/ConflictPCOModal.vue'
import {toDatetimeLocal} from "@/utils";

const router = useRouter()
const route = useRoute()
const api = useApiStore()
const authStore = useAuthStore()
const toast = useToast()

const saving = ref(false)

const typeMenu = useTemplateRef('typeMenu')
const stateMenu = useTemplateRef('stateMenu')
const priorityMenu = useTemplateRef('priorityMenu')
const incidentsMenu = useTemplateRef('incidentsMenu')

const types = usePaginatedSelect({
  fetcher: api.getIncidentTypes,
  menuRef: typeMenu,
  map: (t: any) => ({
    id: t.id,
    name: `${t.code} - ${t.species} - ${t.type}`
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
  filters: () => ({is_major: !state.is_major}),
  map: (i: any) => ({id: i.id, name: i.identifier})
})

const tabs = [
  {
    label: 'Geral',
    slot: 'geral',
    icon: 'i-lucide-users'
  },
  {
    label: 'Posto de Comando',
    slot: 'posto',
    icon: 'i-lucide-satellite-dish',
  },
  {
    label: 'Meios e Recursos',
    slot: 'logistica',
    icon: 'i-lucide-ambulance'
  },
  {
    label: 'Fita de Tempo',
    slot: 'timeline',
    icon: 'i-lucide-history'
  }
]

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
  identifier: z.string().min(1, 'O nº de identificação de ocorrência é obrigatório'),
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
  coordinates: z.string().optional().nullable(),
  address: z.string().optional().nullable(),
  district: z.string().optional().nullable(),
  municipality: z.string().optional().nullable(),
  parish: z.string().optional().nullable(),
  common_place: z.string().optional().nullable(),
  obs: z.string().optional().nullable(),
  coordinates_pco: z.string().optional().nullable(),
  name_pco: z.string().nullable().optional(),
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
    return [38.7223, -9.1393]
  }

  const [lat, lng] = state.coordinates.split(',').map(v => Number(v.trim()))

  if (Number.isNaN(lat) || Number.isNaN(lng)) {
    return [38.7223, -9.1393]
  }

  return [lat, lng]
})

function updateCoordinates(coords: { lat: number, lng: number }) {
  state.coordinates = `${coords.lat}, ${coords.lng}`
}

// Geral
const loadingIncident = ref(true)

const handleSaveGeral = async () => {
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
      user_id: state.user_id,
      identifier: state.identifier,
      start_datetime: toDatetimeLocal(state.start_datetime),
      end_datetime: toDatetimeLocal(state.end_datetime),
      coordinates: state.coordinates,
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

    await api.updateIncident(Number(route.params.id), payload)

    toast.add({
      title: 'Sucesso',
      description: 'Ocorrência atualizada',
      color: 'success'
    })

    await fetchTimeline()
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
  const routeID = route.params.id;

  if (typeof routeID !== 'string') {
    toast.add({
      title: 'Ocorrência inválida',
      description: 'O Caminho que o trouxe aqui aponta para uma Ocorrência inválida',
      color: 'error'
    });

    await router.push('/incidents');
    return;
  }

  const data = (await api.getIncident(parseInt(routeID))).data.data

  state.is_major = data.is_major
  await incidents.fetchItems()

  if (data.is_major && data.children_incidents?.length) {
    incidents.prependSelected(data.children_incidents.map((i: any) => ({
      id: i.id,
      name: i.identifier
    })))
  }
  else if (!data.is_major && data.parentIncident) {
    incidents.prependSelected([{
      id: data.parentIncident.id,
      name: data.parentIncident.identifier
    }])
  }

  if (data.is_major) {
    state.coordinates = ''
  }

  if (data.incidentType) {
    types.prependSelected([{
      id: data.incidentType.id,
      name: `${data.incidentType.code} - ${data.incidentType.species}`
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
    incident_type_id: data.incidentType ? {id: data.incidentType.id, name: `${data.incidentType.code} - ${data.incidentType.species}`} : null,
    incident_state_id: data.incidentState ? {id: data.incidentState.id, name: data.incidentState.name} : null,
    incident_priority_id: data.incidentPriority ? {id: data.incidentPriority.id, name: `${data.incidentPriority.name} - ${data.incidentPriority.description}`} : null,
    incident_id: data.is_major ? (data.children_incidents ?? []).map((i: any) => ({id: i.id, name: i.identifier})) : data.parentIncident ? {id: data.parentIncident.id, name: data.parentIncident.identifier} : null,
    start_datetime: toDatetimeLocal(data.start_datetime),
    end_datetime: toDatetimeLocal(data.end_datetime),
    coordinates: data.is_major ? '' : data.coordinates,
  })

  loadingIncident.value = false
}

watch(() => state.incident_state_id, (newState) => {
  if (newState?.terminates_incident) {
    state.end_datetime = toDatetimeLocal(new Date().toISOString())
  } else {
    state.end_datetime = ''
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
  const incidentId = Number(route.params.id)
  const res = await api.getIncidentPCOs(incidentId)

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
  if (!pendingPCOPayload.value || !conflictingPCO.value) return

  const incidentId = Number(route.params.id)

  try {
    await api.updateIncidentPCO(incidentId, conflictingPCO.value.id, {
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
  savingPCO.value = true

  try {
    if (editingPCO.value?.id) {
      await api.updateIncidentPCO(Number(route.params.id), editingPCO.value.id, payload)
    } else {
      await api.createIncidentPCO(Number(route.params.id), payload)
    }

    toast.add({
      title: 'Sucesso',
      description: editingPCO.value?.id ? 'Função atualizada' : 'Função adicionada',
      color: 'success'
    })

    await fetchPCOList()
    await fetchTimeline()
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
  const incidentId = Number(route.params.id)

  try {
    if (editingLogistic.value?.id) {
      await api.updateIncidentLogistic(incidentId, editingLogistic.value.id, payload)
    } else {
      await api.createIncidentLogistic(incidentId, payload)
    }

    toast.add({
      title: 'Sucesso',
      description: 'Recurso guardado',
      color: 'success'
    })
    await fetchLogistics()
    await fetchTimeline()
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
  const res = await api.getIncidentLogistics(Number(route.params.id))
  logistics.value = res?.data?.data ?? []
  logisticTotals.value = res?.data?.meta ?? { total_vehicles: 0, total_humans: 0 }
}

// Linha de Tempo
const timeline = ref<TimelineItem[]>([])
const loadingTimeline = ref(false)
const newComment = ref('')
const savingComment = ref(false)

const commentSchema = z.object({
  incident_id: z.number({ required_error: 'Ocorrência inválida' }),
  user_id: z.number({ required_error: 'Utilizador inválido' }),
  body: z.string().min(1, 'O comentário não pode estar vazio').max(2000, 'Máximo de 2000 caracteres'),
})

type TimelineCommentSchema = z.output<typeof commentSchema>
reactive<Partial<TimelineCommentSchema>>({
  user_id: authStore.currentUserID,
  incident_id: null,
  body: ''
});

const commentDateTime = ref(toDatetimeLocal(new Date().toISOString()))

const formatValue = (key: string, value: any) => {
  if (value === null || value === undefined || value === '') {
    return '-'
  }

  if (typeof value === 'boolean') {
    return value ? 'Sim' : 'Não'
  }

  return value
}

const timeAgo = (date: Date) => formatTimeAgoIntl(new Date(date), { locale: 'pt-PT' })

const fieldLabels: Record<string, string> = {
  identifier: 'Identificador',
  incident_type_id: 'Tipo',
  incident_state_id: 'Estado',
  incident_priority_id: 'Prioridade',
  start_datetime: 'Data Início',
  end_datetime: 'Data Fim',
  coodinates: 'Coordenadas',
  common_place: 'Ponto de Referência',
  address: 'Morada',
  parish: 'Freguesia',
  municipality: 'Municipio',
  district: 'Distrito',
  is_major: 'Ocorrência Major',
  alert_source_relationship: 'Fonte de Alerta',
  alert_source_name: 'Nome do Contacto',
  alert_source_contact: 'Tlf. Contacto',
  obs: 'Observações',
  coordinates_pco: 'Coordenadas do Posto de Comando',
  name_pco: 'Nome do Posto de Comando',

  entity_id: 'Entidade',
  vehicle_count: 'N.º Veículos',
  human_count: 'N.º Operacionais',

  function_pco: 'Função',
  resp_pco: 'Responsável',
  category_pco: 'Categoria',
  contact1_pco: 'Contacto 1',
  contact2_pco: 'Contacto 2',
  localization_pco: 'Localização',
  rob_pco: 'ROB',
  srp_pco: 'SRP',
  activation_pco_datetime: 'Data Ativação',
  start_pco_datetime: 'Data Início',
  end_pco_datetime: 'Data Fim',
}

const fieldLabel = (key: string) => {
  return fieldLabels[key] ?? key
}

const moduleIcon = (module: string) => {
  const icons: Record<string, string> = {
    'incidents': 'i-lucide-users',
    'pcos':      'i-lucide-satellite-dish',
    'parties':   'i-lucide-ambulance',
    'comments':  'i-lucide-message-circle'
  }
  return icons[module] ?? 'i-lucide-message-circle'
}

const fetchTimeline = async () => {
  loadingTimeline.value = true

  try {
    const res = await api.getIncidentTimeline(Number(route.params.id))

    timeline.value = res.data.map((entry: any) => ({
      date:        entry.date,
      username:    entry.user,
      action:      entry.event,
      module:      entry.module,
      changes:     entry.changes,
      old_values:  entry.old_values,
      type:        entry.type,
      body:        entry.body ?? null,
      comment_id:  entry.comment_id ?? null,
      icon:        entry.type === 'comment' ? 'i-lucide-message-circle' : moduleIcon(entry.module),
      description: ' ',
    }))
  } finally {
    loadingTimeline.value = false
  }
}

const editingCommentId = ref<number | null>(null)
const editingCommentBody = ref('')
const editingCommentDate = ref('')

const startEditComment = (item: any) => {
  editingCommentId.value = item.comment_id
  editingCommentBody.value = item.body
  editingCommentDate.value = toDatetimeLocal(item.date)
}

const cancelEditComment = () => {
  editingCommentId.value = null
  editingCommentBody.value = ''
}

const submitComment = async () => {
  if (!newComment.value.trim()) return

  savingComment.value = true

  try {
    const payload = {
      incident_id: Number(route.params.id),
      user_id: authStore.currentUserID,
      body: newComment.value,
      datetime: commentDateTime.value
    }

    await api.createTimelineComment(Number(route.params.id), payload)

    toast.add({
      title: 'Sucesso',
      description: 'Entrada registada',
      color: 'success'
    })

    newComment.value = ''
    await fetchTimeline()
  } catch (e: any) {
    toast.add({
      title: 'Erro',
      description: 'Erro ao registar a entrada',
      color: 'error'
    })
  }finally {
    savingComment.value = false
  }
}

const saveEditComment = async (item: any) => {
  try {
    await api.updateTimelineComment(Number(route.params.id), item.comment_id, {
      body: editingCommentBody.value,
      incident_id: Number(route.params.id),
      user_id: authStore.currentUserID,
      datetime: editingCommentDate.value
    })

    toast.add({
      title: 'Sucesso',
      description: 'Entrada atualizada',
      color: 'success'
    })

    editingCommentId.value = null
    editingCommentBody.value = ''

    await fetchTimeline()
  } catch (e: any) {
    toast.add({
      title: 'Erro',
      description: e.response?.data?.message ?? 'Erro ao atualizar comentário',
      color: 'error'
    })
  }
}

watch(() => state.is_major, async () => {
  if (loadingIncident.value) return

  state.incident_id = state.is_major ? [] : null

  await incidents.reset(true)

  if (state.is_major) {
    state.coordinates = ''
  }
})

onMounted(async () => {
  if (!useAuthStore().hasPermission('INCIDENTS_LIST')) {
    await router.push('/inicio');
    return;
  }

  await Promise.all([
    types.fetchItems(),
    states.fetchItems(),
    priorities.fetchItems(),
    fetchPCOList(),
    fetchLogistics(),
    fetchTimeline()
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
              <UFormField label="Identificador" name="identifier">
                <UInput v-model="state.identifier" class="w-full"/>
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
                />
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
                  <UInput type="datetime-local" v-model="state.end_datetime" class="w-full"/>
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
                  :zoom="13"
                  class="w-full h-[400px] rounded-lg"
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
                { accessorKey: 'vehicle_count', header: 'Veículos' },
                { accessorKey: 'human_count', header: 'Humanos' },
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
          <div class="space-y-6 pt-4">
            <section class="space-y-2">
              <h2 class="font-bold">Nova entrada manual</h2>
              <UFormField label="Data da ocorrência">
                <UInput
                  type="datetime-local"
                  v-model="commentDateTime"
                  class="w-full"
                />
              </UFormField>
              <UFormField label="Descrição">
                <UTextarea v-model="newComment" placeholder="Escreva uma entrada..." :rows="3" class="w-full"/>
              </UFormField>
              <div class="flex justify-end gap-2">
                <UButton
                  color="primary"
                  label="Guardar"
                  :loading="savingComment"
                  :disabled="!newComment.trim()"
                  @click="submitComment"
                />
              </div>
            </section>
            <div class="h-px border-t border-stone-200 dark:border-stone-800"/>
            <h2 class="font-bold">Fita de Tempo</h2>
            <div v-if="loadingTimeline" class="flex justify-center py-10">
              <UIcon name="i-lucide-loader-circle" class="animate-spin text-stone-400 size-6" />
            </div>
            <div v-else-if="timeline.length === 0" class="text-center py-10 text-sm text-stone-400">
              Sem registos na fita de tempo
            </div>
            <UTimeline v-else :items="timeline" size="xl" :ui="{ date: 'float-end ms-1' }">
              <template #title="{ item }">
                <div class="flex flex-col gap-2">
                  <div class="flex items-center justify-between gap-2">
                    <div>
                      <span class="font-semibold">{{ (item as any).username }}</span>
                      <span class="font-normal text-muted">&nbsp;{{ (item as any).action }}</span>
                    </div>
                    <UButton
                      v-if="(item as any).type === 'comment'"
                      icon="i-lucide-pencil"
                      data-testid="edit-comment"
                      color="warning"
                      variant="ghost"
                      size="xs"
                      @click="startEditComment(item)"
                    />
                  </div>
                  <div v-if="(item as any).type === 'comment'" class="space-y-2">
                    <div v-if="editingCommentId !== (item as any).comment_id" class="text-sm px-3 py-2 ring ring-default rounded-md text-stone-400 shrink-0 dark:text-stone-300">
                      {{ (item as any).body }}
                    </div>
                    <div v-else class="space-y-2">
                      <UFormField label="Data da ocorrência">
                        <UInput
                          type="datetime-local"
                          v-model="editingCommentDate"
                          class="w-full"
                        />
                      </UFormField>
                      <UFormField label="Descrição">
                        <UTextarea v-model="editingCommentBody" :rows="3" class="w-full"/>
                      </UFormField>
                      <div class="flex justify-end gap-2">
                        <UButton
                          color="neutral"
                          variant="ghost"
                          label="Cancelar"
                          @click="cancelEditComment"
                        />
                        <UButton
                          color="primary"
                          label="Guardar"
                          @click="saveEditComment(item)"
                        />
                      </div>
                    </div>
                  </div>
                  <div v-else-if="Object.keys((item as any).changes ?? {}).length">
                    <UAccordion
                      :items="[{
                        label: 'Ver alterações',
                      }]"
                      class="text-sm text-stone-400 shrink-0 dark:text-stone-300"
                      :ui="{
                        trigger: 'px-3 py-2 ring ring-default rounded-md bg-default/30',
                        content: 'px-3 py-2'
                      }"
                    >
                      <template #content>
                        <div class="space-y-1 text-xs px-3 py-2">
                          <div v-for="(value, key) in (item as any).changes" :key="key" class="flex gap-2 flex-wrap">
                            <span class="text-stone-400 shrink-0">{{ fieldLabel(key) }}:</span>
                            <template v-if="key in ((item as any).old_values ?? {})">
                              <span class="line-through text-red-400">{{ formatValue(key, (item as any).old_values?.[key]) }}</span>
                              <UIcon name="i-lucide-move-right" />
                            </template>
                            <span class="text-green-500">{{ formatValue(key, value) }}</span>
                          </div>
                        </div>
                      </template>
                    </UAccordion>
                  </div>
                </div>
              </template>
              <template #date="{ item }">{{ timeAgo(new Date((item as any).date)) }}</template>
            </UTimeline>
          </div>
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
