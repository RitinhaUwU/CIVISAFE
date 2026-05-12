<script setup lang="ts">
import { useRoute } from 'vue-router'
import { useApiStore } from '@/stores/api'

const route = useRoute()
const router = useRouter()
const api = useApiStore()

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

const state = reactive({
  identifier: '',
  incident_type_id: null as number,
  incident_state_id: null as number,
  incident_priority_id: null as number,
  user_id: null,
  coordinates: '',
  common_place: '',
  address: '',
  parish: '',
  municipality: '',
  district: '',
  command_post: '',
  is_major: '',
  alert_source_relationship: '',
  alert_source_name: '',
  alert_source_contact: '',
  obs: '',
  incident_id: null
})

const fetchIncident = async () => {
  const res = await api.getIncident(route.params.id)
  const data = res.data.data

  Object.assign(state, {
    ...data,
    incident_type_id: data.incidentType?.id,
    incident_state_id: data.incidentState?.id,
    incident_priority_id: data.incidentPriority?.id,
  })

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

onMounted(async () => {
  await fetchIncident()
  await fetchTypes()
  await fetchStates()
  await fetchPriorities()

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
          <div class="flex items-center gap-2">
            <!-- <UButton label="Guardar" color="primary" :loading="saving" @click="handleSave" /> -->
          </div>
        </div>
      </div>
    </header>
      <div class="flex-1 overflow-y-auto px-6 sm:px-8 py-8 space-y-8">
        <UBreadcrumb :items="items" />
        <div class="grid grid-cols-1 gap-8">
          <div class="space-y-6">
            <section class="space-y-2">
              <h2 class="font-bold">Dados Gerais</h2>
              <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                <UFormField label="Identificador">
                  <UInput v-model="state.identifier" class="w-full"/>
                </UFormField>
                <UFormField label="Tipo de Ocorrência" class="sm:col-span-2">
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
                <UFormField label="Estado" class="sm:col-span-2">
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
                <UFormField label="Prioridade" class="sm:col-span-2">
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
                <USwitch
                  v-model="state.is_major"
                  label="Ocorrência Major"
                />
              </div>
            </section>
            <div class="h-px border-t border-stone-200 dark:border-stone-800" />
            <section class="space-y-2">
              <h2 class="font-bold">Localização</h2>
              <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                <UFormField label="Morada">
                  <UInput v-model="state.address" class="w-full"/>
                </UFormField>
                <UFormField label="Freguesia">
                  <UInput v-model="state.parish" class="w-full"/>
                </UFormField>
                <UFormField label="Município">
                  <UInput v-model="state.municipality" class="w-full"/>
                </UFormField>
                <UFormField label="Distrito">
                  <UInput v-model="state.district" class="w-full"/>
                </UFormField>
              </div>
            </section>
            <div class="h-px border-t border-stone-200 dark:border-stone-800" />
            <section class="space-y-2">
              <h2 class="font-bold">Observações</h2>
              <UTextarea v-model="state.obs" :rows="5" class="w-full" />
            </section>
          </div>
        </div>
      </div>
  </div>
</template>

<style scoped>

</style>
