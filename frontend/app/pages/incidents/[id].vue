<script setup lang="ts">
import { useRoute } from 'vue-router'
import {useToast} from "@nuxt/ui/composables";
import type {BreadcrumbItem} from "@nuxt/ui/components/Breadcrumb.vue";
import { useApiStore } from '@/stores/api'
import { useAuthStore } from '@/stores/auth'
import * as z from 'zod';
import Map from '../../components/Map.vue'
import { computed } from 'vue'

const route = useRoute()
const api = useApiStore()
const auth = useAuthStore()

const saving = ref(false)

const typeMenu = useTemplateRef('typeMenu')
const typeItems = ref<any[]>([])
const typePage = ref(1)
const typeLastPage = ref(Infinity)
const typeLoading = ref(false)
const typeSearch = ref('')

const stateMenu = useTemplateRef('stateMenu')
const stateItems = ref<any[]>([])
const statePage = ref(1)
const stateLastPage = ref(Infinity)
const stateLoading = ref(false)
const stateSearch = ref('')

const priorityMenu = useTemplateRef('priorityMenu')
const priorityItems = ref<any[]>([])
const priorityPage = ref(1)
const priorityLastPage = ref(Infinity)
const priorityLoading = ref(false)
const prioritySearch = ref('')

const incidentsMenu = useTemplateRef('incidentsMenu')
const incidentsItems = ref<any[]>([])
const incidentsPage = ref(1)
const incidentsLastPage = ref(Infinity)
const incidentsLoading = ref(false)
const incidentsSearch = ref('')

const entitiesMenu = useTemplateRef('entitiesMenu')
const entitiesItems = ref<any[]>([])
const entitiesPage = ref(1)
const entitiesLastPage = ref(Infinity)
const entitiesLoading = ref(false)
const entitiesSearch = ref('')

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
    label: 'Logística',
    slot: 'logistica',
    icon: 'i-lucide-ambulance'
  }
]

const toast = useToast()

const schema = z.object({
  is_major: z.boolean(),
  identifier: z.string().min(1, 'O nº de identificação de ocorrência é obrigatório'),
  start_datetime: z.string().min(1, 'A data de alerta é obrigatória'),
  end_datetime: z.string().optional().nullable(),
  incident_state_id: z.number({required_error: 'O estado é obrigatório'}).nullable().refine(val => val !== null, {message: 'O estado é obrigatório'}),
  incident_priority_id: z.number({required_error: 'A prioridade é obrigatória'}).nullable().refine(val => val !== null, {message: 'A prioridade é obrigatória'}),
  incident_type_id: z.number({required_error: 'O tipo de ocorrência é obrigatório'}).nullable().refine(val => val !== null, {message: 'O tipo de ocorrência é obrigatório'}),
  incident_id: z.any().nullable().optional(),
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

  // PCO
  function_pco:  z.string().optional().nullable(),
  resp_pco: z.string().optional().nullable(),
  category_pco: z.string().optional().nullable(),
  contact1_pco: z.string().refine(value => !value || /^\+?[0-9]+(?: [0-9]+)*$/.test(value), 'Insira apenas números ou formato +000 000000000').optional().nullable(),
  contact2_pco: z.string().refine(value => !value || /^\+?[0-9]+(?: [0-9]+)*$/.test(value), 'Insira apenas números ou formato +000 000000000').optional().nullable(),
  localization_pco: z.string().optional().nullable(),
  rob_pco: z.string().optional().nullable(),
  srp_pco: z.string().optional().nullable(),
  activation_pco_datetime: z.string().optional().nullable(),
  start_pco_datetime: z.string().optional().nullable(),
  end_pco_datetime: z.string().optional().nullable(),
})

type Schema = z.output<typeof schema>

const state = reactive({
  identifier: '',
  incident_type_id: null as number,
  incident_state_id: null as number,
  incident_priority_id: null as number,
  user_id: null as number,
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
  incident_id: [] as any[],
  children_incidents: [] as any[],
  coordinates_pco: '',
  name_pco: '',
})

const pco = reactive<any>({
  function_pco: '',
  resp_pco: '',
  category_pco: '',
  contact1_pco: '',
  contact2_pco: '',
  localization_pco: '',
  rob_pco: '',
  srp_pco: '',
  activation_pco_datetime: '',
  start_pco_datetime: '',
  end_pco_datetime: '',
  incident_id: null,
})

const logistic = reactive<any>({
  human_count: '',
  vehicle_count: '',
  incident_id: null,
  entity_id: null
})

const selectedIncidents = computed(() => {
  if (state.is_major) {
    return Array.isArray(state.incident_id) ? state.incident_id : []
  }

  return state.incident_id ? [state.incident_id] : []
})

const mapCenter = computed(() => {
  if (!state.coordinates) {
    return [38.7223, -9.1393]
  }

  const [lat, lng] = state.coordinates.split(',').map(v => Number(v.trim()))

  if (isNaN(lat) || isNaN(lng)) {
    return [38.7223, -9.1393]
  }

  return [lat, lng]
})

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
      ...state,
      children_incidents: state.is_major ? (state.incident_id ?? []).map((i: any) => i.id) : []
    }

    await api.updateIncident(route.params.id, payload)

    toast.add({
      title: 'Sucesso',
      description: 'Ocorrrência atualizada',
      color: 'success'
    })
  } catch (e) {
    toast.add({
      title: 'Erro',
      description: 'Erro ao atualizar',
      color: 'error'
    })
  } finally {
    saving.value = false
  }
}

//https://stackoverflow.com/questions/30166338/setting-value-of-datetime-local-from-date
// Converte o ISO que vem da API para um objeto Date.
const toDatetimeLocal = (value?: string | null) => {
  if (!value) return ''

  return new Date(value).toISOString().slice(0, 16) // toISOString() -> Transforma a data em formato padrão
}

const fetchIncident = async () => {
  const routeID = route.params.id;
  if (typeof routeID !== 'string') {
    useToast().add({
      title: 'Ocorrência inválida',
      description: 'O Caminho que o trouxe aqui aponta para uma Ocorrência inválida',
      color: 'error'
    });
    await useRouter().push('/incidents');
    return;
  }

  const data = (await api.getIncident(parseInt(routeID))).data.data

  Object.assign(state, {
    ...data,
    user_id: data.user?.id,
    user: data.user,
    incident_type_id: data.incidentType?.id,
    incident_state_id: data.incidentState?.id,
    incident_priority_id: data.incidentPriority?.id,
    incident_id: data.is_major ? (data.children_incidents ?? []).map((i: any) => ({id: i.id, name: i.identifier})) : data.parentIncident ? {id: data.parentIncident.id, name: data.parentIncident.identifier} : null,
    start_datetime: toDatetimeLocal(data.start_datetime),
    end_datetime: toDatetimeLocal(data.end_datetime),
  })

  if (data.is_major) {
    state.coordinates = ''
  }

  if (data.incidentType) {
    typeItems.value = [{
      id: data.incidentType.id,
      name: `${data.incidentType.code} - ${data.incidentType.species}`
    }]
  }

  if (data.incidentState) {
    stateItems.value = [{
      id: data.incidentState.id,
      name: data.incidentState.name }]
  }

  if (data.incidentPriority) {
    priorityItems.value = [{
      id: data.incidentPriority.id,
      name: `${data.incidentPriority.name} - ${data.incidentPriority.description}`
    }]
  }
}

const fetchTypes = async (search?: string, loadMore = false) => {
  if (typeLoading.value) return

  typeLoading.value = true
  try {
    const res = await api.getIncidentTypes({
      page: typePage.value,
      per_page: 10,
      //TODO: Implementar este filtro para o offline
      filter: {
        ...(search ? { search } : {})
      }
    })

    const data = res.data.data
    typeLastPage.value = res.data.meta.last_page
    const mapped = data.map(t => ({id: t.id, name: `${t.code} - ${t.species}`}))
    if (loadMore) {
      const existingIds = new Set(typeItems.value.map(i => i.id))
      typeItems.value = [...typeItems.value, ...mapped.filter(i => !existingIds.has(i.id))]
    } else {
      const existingIds = new Set(typeItems.value.map(i => i.id))
      typeItems.value = [...typeItems.value, ...mapped.filter(i => !existingIds.has(i.id))]
    }
  } finally {
    typeLoading.value = false
  }
}

const fetchStates = async (search?: string, loadMore = false) => {
  if (stateLoading.value) return

  stateLoading.value = true
  try {
    const res = await api.getIncidentStates({
      page: statePage.value,
      per_page: 10,
      //TODO: Implementar este filtro para o offline
      filter: {
        ...(search ? { search } : {})
      }
    })

    const data = res.data.data
    stateLastPage.value = res.data.meta.last_page
    const mapped = data.map(s => ({ id: s.id, name: s.name }))
    const existingIds = new Set(stateItems.value.map(i => i.id))
    stateItems.value = [...stateItems.value, ...mapped.filter(i => !existingIds.has(i.id))]
  } finally {
    stateLoading.value = false
  }
}

const fetchPriorities = async (search?: string, loadMore = false) => {
  if (priorityLoading.value) return

  priorityLoading.value = true
  try {
    const res = await api.getIncidentPriorities({
      page: priorityPage.value,
      per_page: 10,
      //TODO: Implementar este filtro para o offline
      filter: {
        ...(search ? { search } : {})
      }
    })

    const data = res.data.data
    priorityLastPage.value = res.data.meta.last_page
    const mapped = data.map(p => ({ id: p.id, name: `${p.name} - ${p.description}` }))
    const existingIds = new Set(priorityItems.value.map(i => i.id))
    priorityItems.value = [...priorityItems.value, ...mapped.filter(i => !existingIds.has(i.id))]
  } finally {
    priorityLoading.value = false
  }
}

const fetchIncidents = async (search?: string, loadMore = false) => {
  if (incidentsLoading.value) return

  incidentsLoading.value = true
  try {
    const res = await api.getIncidents({
      page: incidentsPage.value,
      per_page: 10,
      //TODO: Implementar este filtro para o offline
      filter: {
        ...(search ? { search } : {}),
        is_major: !state.is_major
      }
    })

    const data = res.data.data
    incidentsLastPage.value = res.data.meta.last_page
    const mapped = data.map(i => ({ id: i.id, name: i.identifier }))
    const existingIds = new Set(incidentsItems.value.map(i => i.id))
    incidentsItems.value = [...incidentsItems.value, ...mapped.filter(i => !existingIds.has(i.id))]
  } finally {
    incidentsLoading.value = false
  }
}

const fetchEntities = async (search?: string, loadMore = false) => {
  if (entitiesLoading.value) return

  entitiesLoading.value = true
  try {
    const res = await api.getEntities({
      page: entitiesPage.value,
      per_page: 10,
      //TODO: Implementar este filtro para o offline
      filter: {
        ...(search ? { search } : {}),
      }
    })

    const data = res.data.data
    entitiesLastPage.value = res.data.meta.last_page
    const mapped = data.map(i => ({ id: i.id, name: i.name }))
    const existingIds = new Set(entitiesItems.value.map(i => i.id))
    entitiesItems.value = [...entitiesItems.value, ...mapped.filter(i => !existingIds.has(i.id))]
  } finally {
    entitiesLoading.value = false
  }
}

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

function updateCoordinates(coords: { lat: number, lng: number }) {
  state.coordinates = `${coords.lat}, ${coords.lng}`
}

watchDebounced(typeSearch, async (val) => {
  typePage.value = 1
  typeItems.value = []
  typeLastPage.value = Infinity
  await fetchTypes(val)
}, { debounce: 300 })

watchDebounced(stateSearch, async (val) => {
  statePage.value = 1
  stateItems.value = []
  stateLastPage.value = Infinity
  await fetchStates(val)
}, { debounce: 300 })

watchDebounced(prioritySearch, async (val) => {
  priorityPage.value = 1
  priorityItems.value = []
  priorityLastPage.value = Infinity
  await fetchPriorities(val)
}, { debounce: 300 })

watchDebounced(incidentsSearch, async (val) => {
  incidentsPage.value = 1
  incidentsItems.value = []
  incidentsLastPage.value = Infinity
  await fetchIncidents(val)
}, { debounce: 300 })

watchDebounced(entitiesSearch, async (val) => {
  entitiesPage.value = 1
  entitiesItems.value = []
  entitiesLastPage.value = Infinity
  await fetchEntities(val)
}, { debounce: 300 })

watch(
  () => state.is_major,
  async () => {
    state.incident_id = state.is_major ? [] : null

    incidentsPage.value = 1
    incidentsItems.value = []
    incidentsLastPage.value = Infinity

    await fetchIncidents(incidentsSearch.value)

    if (state.is_major) {
      state.coordinates = ''
    }
  }
)

onMounted(async () => {
  await fetchIncident()
  await fetchTypes()
  await fetchStates()
  await fetchPriorities()
  await fetchIncidents()
  await fetchEntities()

  // Ocorrências Tipos
  useInfiniteScroll(
    () => typeMenu.value?.viewportRef,
    () => {
      if (typePage.value < typeLastPage.value) {
        typePage.value++
        fetchTypes(typeSearch.value, true)
      }
    },
    { canLoadMore: () => !typeLoading.value && typePage.value < typeLastPage.value }
  )

  // Ocorrências Estados
  useInfiniteScroll(
    () => stateMenu.value?.viewportRef,
    () => {
      if (statePage.value < stateLastPage.value) {
        statePage.value++
        fetchStates(stateSearch.value, true)
      }
    },
    { canLoadMore: () => !stateLoading.value && statePage.value < stateLastPage.value }
  )

  // Ocorrências Prioridades
  useInfiniteScroll(
    () => priorityMenu.value?.viewportRef,
    () => {
      if (priorityPage.value < priorityLastPage.value) {
        priorityPage.value++
        fetchPriorities(prioritySearch.value, true)
      }
    },
    { canLoadMore: () => !priorityLoading.value && priorityPage.value < priorityLastPage.value }
  )

  // Ocorrências
  useInfiniteScroll(
    () => incidentsMenu.value?.viewportRef,
    () => {
      if (incidentsPage.value < incidentsLastPage.value) {
        incidentsPage.value++
        fetchIncidents(incidentsSearch.value, true)
      }
    },
    { canLoadMore: () => !incidentsLoading.value && incidentsPage.value < incidentsLastPage.value }
  )

  // Entidades
  useInfiniteScroll(
    () => entitiesMenu.value?.viewportRef,
    () => {
      if (entitiesPage.value < entitiesLastPage.value) {
        entitiesPage.value++
        fetchEntities(entitiesSearch.value, true)
      }
    },
    { canLoadMore: () => !entitiesLoading.value && entitiesPage.value < entitiesLastPage.value }
  )
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
      <UBreadcrumb :items="items" />
      <UTabs :items="tabs" class="w-full">
        <template #geral>
          <div class="space-y-6">
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
                  v-model:search-term="typeSearch"
                  :items="typeItems"
                  :loading="typeLoading"
                  value-key="id"
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
                    v-model:search-term="stateSearch"
                    :items="stateItems"
                    :loading="stateLoading"
                    value-key="id"
                    label-key="name"
                    ignore-filter
                    class="w-full"
                  />
                </UFormField>
                <UFormField label="Prioridade" name="incident_priority_id">
                  <USelectMenu
                    ref="priorityMenu"
                    v-model="state.incident_priority_id"
                    v-model:search-term="prioritySearch"
                    :items="priorityItems"
                    :loading="priorityLoading"
                    value-key="id"
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
                  v-model:search-term="incidentsSearch"
                  :items="incidentsItems"
                  :loading="incidentsLoading"
                  label-key="name"
                  value-key="id"
                  ignore-filter
                  :multiple="state.is_major"
                  class="w-full"
                  :placeholder="state.is_major ? 'Selecionar ocorrências associadas' : 'Selecionar ocorrência major'"
                />
              </UFormField>
            </section>
            <div class="h-px border-t border-stone-200 dark:border-stone-800" />
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
            <div class="h-px border-t border-stone-200 dark:border-stone-800" />
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
              <Map
                v-if="!state.is_major"
                :center="mapCenter"
                :zoom="13"
                class="w-full h-[400px] rounded-lg"
                @map-click="updateCoordinates"
              />
            </section>
            <div class="h-px border-t border-stone-200 dark:border-stone-800" />
            <section class="space-y-2">
              <h2 class="font-bold">Observações</h2>
              <UFormField name="obs">
                <UTextarea v-model="state.obs" :rows="5" class="w-full" />
              </UFormField>
            </section>
            <div class="h-px border-t border-stone-200 dark:border-stone-800" />
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
            <div class="flex justify-end gap-2">
              <UButton label="Guardar" color="primary" :loading="saving" @click="handleSaveGeral" />
            </div>
          </div>
        </template>
        <template #posto>
          <div class="space-y-6">
            <section class="space-y-2">
              <UCard :ui="{ body: { base: 'space-y-8' }}" >
                <template #header>
                  <div class="flex items-center justify-between">
                    <div>
                      <h3 class="font-semibold text-lg">Gestão de Funções no Posto de Comando Operacional (PCO)</h3>
                      <p class="text-sm text-gray-500">Informação operacional e contactos</p>
                    </div>
                    <UButton
                      label="Nova Função"
                      icon="i-lucide-plus"
                      @click=""
                      class="flex justify-self-end"
                    />
                  </div>
                </template>
                <div class="grid grid-cols-1 md:grid-cols-3 gap-4 mb-4">
                  <UFormField label="Função" name="function_pco">
                    <USelect
                      v-model="pco.function_pco"
                      :items="[
                       { label: 'COS', value: 'COS' },
                       { label: 'Oficial Operações', value: 'Oficial Operações' },
                       { label: 'Oficial Logística', value: 'Oficial Logística' },
                       { label: 'Oficial Planeamento', value: 'Oficial Planeamento' },
                       { label: 'Oficial Operações Aéreas', value: 'Oficial Operações Aéreas' },
                       { label: 'Adjunto Segurança', value: 'Adjunto Segurança' },
                       { label: 'Adjunto Relações Públicas', value: 'Adjunto Relações Públicas' },
                       { label: 'Adjunto Ligação', value: 'Adjunto Ligação' }
                     ]"
                      class="w-full"
                    />
                  </UFormField>
                  <UFormField label="Responsável" name="resp_pco">
                    <UInput v-model="pco.resp_pco" class="w-full" />
                  </UFormField>
                  <UFormField label="Categoria" name="category_pco">
                    <UInput v-model="pco.category_pco" class="w-full" />
                  </UFormField>
                </div>
                <div class="grid grid-cols-1 md:grid-cols-3 gap-4 mb-4">
                  <UFormField label="Contacto 1" name="contact1_pco">
                    <UInput v-model="pco.contact1_pco" class="w-full" />
                  </UFormField>
                  <UFormField label="Contacto 2" name="contact2_pco">
                    <UInput v-model="pco.contact2_pco" class="w-full" />
                  </UFormField>
                  <UFormField label="Localização" name="localization_pco">
                    <UInput v-model="pco.localization_pco" class="w-full" />
                  </UFormField>
                </div>
                <div class="grid grid-cols-1 md:grid-cols-5 gap-4">
                  <UFormField label="ROB" name="rob_pco">
                    <UInput v-model="pco.rob_pco" class="w-full" />
                  </UFormField>
                  <UFormField label="SRP" name="srp_pco">
                    <UInput v-model="pco.srp_pco" class="w-full" />
                  </UFormField>
                  <UFormField label="Data Ativação" name="activation_pco_datetime">
                    <UInput type="datetime-local" v-model="pco.activation_pco_datetime" class="w-full" />
                  </UFormField>
                  <UFormField label="Data Montagem" name="start_pco_datetime">
                    <UInput type="datetime-local" v-model="pco.start_pco_datetime" class="w-full" />
                  </UFormField>
                  <UFormField label="Data Desmontagem" name="end_pco_datetime">
                    <UInput type="datetime-local" v-model="pco.end_pco_datetime" class="w-full" />
                  </UFormField>
                </div>
              </UCard>
              <UCard>
                <p>Aqui colocar uma lista</p>
              </UCard>
            </section>
          </div>
        </template>
        <template #logistica>
          <div class="space-y-6">
            <section class="space-y-2">
              <h2 class="font-bold">Logística</h2>
              <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <UFormField label="Nº de Veículos" name="vehicle_count">
                  <UInput type="numeric" v-model="logistic.vehicle_count" class="w-full" />
                </UFormField>
                <UFormField label="Nº de Humanos" name="human_count">
                  <UInput type="numeric" v-model="logistic.human_count" class="w-full" />
                </UFormField>
              </div>
              <UFormField label="Entidades" name="">
                <USelectMenu
                  ref="entitiesMenu"
                  v-model="logistic.entity_id"
                  v-model:search-term="entitiesSearch"
                  :items="entitiesItems"
                  :loading="entitiesLoading"
                  value-key="id"
                  label-key="name"
                  ignore-filter
                  class="w-full"
                />
              </UFormField>
            </section>
            <div class="flex justify-end gap-2">
              <UButton label="Guardar" color="primary" :loading="saving" @click="" />
            </div>
          </div>
        </template>
      </UTabs>
    </div>
  </div>
</template>

<style scoped>

</style>
