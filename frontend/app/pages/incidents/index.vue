<script setup lang="ts">
import { useApiStore } from '@/stores/api'
import type { TableColumn } from '@nuxt/ui'

const api = useApiStore()

const incidents = ref<Incident[]>([])
const page = ref(1)
const lastPage = ref<number>(Infinity)
const loading = ref(false)
const total = ref(0)

const search = ref('')

const statusFilter = ref('all')
const stateMenu = useTemplateRef('stateMenu')
const stateItems = ref<any[]>([])
const statePage = ref(1)
const stateLastPage = ref(Infinity)
const stateLoading = ref(false)
const stateSearch = ref('')

const prioritiesFilter = ref('all')
const priorityMenu = useTemplateRef('priorityMenu')
const priorityItems = ref<any[]>([])
const priorityPage = ref(1)
const priorityLastPage = ref(Infinity)
const priorityLoading = ref(false)
const prioritySearch = ref('')

const deleteModalOpen = ref(false)
const selectedIncidentById = ref<Incident | null>(null)

type Incident = {
  id: number;
  identifier: string;
  incident_type_id: number;
  incident_state_id: number;
  user_id: number;
  incident_priority_id: number;
  start_datetime: Date;
  end_datetime: Date;
  coordinates: string;
  common_place: string;
  address: string;
  parish: string;
  municipality: string;
  district: string;
  command_post: string;
  is_major: boolean;
  alert_source_relationship: string;
  alert_source_name: string;
  alert_source_contact: string;
  obs: string;
  incident_id: number;
  created_at: Date;
  updated_at: Date;
  deleted_at: Date;
}

const columns: TableColumn<Incident>[] = [
  {
    accessorKey: "identifier",
    header: "Nº Ocorrência",
  },
  {
    accessorKey: "state",
    header: () => h('div', { class: 'text-center w-full' }, 'Estado'),
    meta: { class: 'text-center' },
    cell: ({ row }) => {
      const state = row.original.incidentState
      return h('div', { class: 'flex justify-center' },
        h(UBadge,
          {
            class: 'capitalize rounded-full border',
            style: {backgroundColor: `${state.hex_color}`}
          }, () => state.name
        )
      )
    }
  },
  {
    header: "Prioridade",
    cell: ({ row }) => {
      const p = row.original.incidentPriority
      return h('div', { class: 'flex flex-col' }, [
        h('p', { class: 'font-medium text-highlighted' }, p.name),
        h('p', { class: 'text-xs text-muted' }, p.description)
      ])
    }
  },
  {
    header: "Categoria",
    cell: ({ row }) => {
      const type = row.original.incidentType
      return h('div', { class: 'flex flex-col' }, [
        h('p', { class: 'font-medium text-highlighted' }, type ? `${type.code} - ${type.species}` : '—'),
        h('p', { class: 'text-xs text-muted' }, type?.type ?? '—')
      ])
    }
  },
  {
    accessorKey: "start_datetime",
    header: "Data de Início",
    cell: ({row}) => {
      return new Date(row.getValue("start_datetime")).toLocaleString("pt-PT");
    },
  },
  {
    id: 'actions',
    cell: ({ row }) => {
      return h(
        'div',
        { class: 'text-right' },
        h(UButton, {
          icon: 'i-lucide-info',
          color: 'info',
          variant: 'ghost',
          onClick: () => {
            navigateTo(`/incidents/${row.original.id}`)
          }
        }),
        h(UButton, {
          icon: 'i-lucide-trash',
          color: 'error',
          variant: 'ghost',
          onClick: () => {
            //?????????????????????????????????????????????
            //selectedIncidentById.value = row.original
            //deleteModalOpen.value = true
          }
        })
      )
    }
  }
]

const fetchStates = async (search?: string, loadMore = false) => {
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
    stateItems.value = loadMore ? [...stateItems.value, ...data] : [{ id: 'all', name: 'Estados' }, ...data]
  } finally {
    stateLoading.value = false
  }
}

const fetchPriorities = async (search?: string, loadMore = false) => {
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
    priorityItems.value = loadMore ? [...priorityItems.value, ...data] : [{ id: 'all', name: 'Prioridades' }, ...data]
  } finally {
    priorityLoading.value = false
  }
}

const fetch = async() => {
  if (loading.value) return
  if (page.value > lastPage.value) return

  loading.value = true
  try {
    const params: any = {
      page: page.value,
      per_page: 10,
      filter: {}
    }
    if (search.value) {
      params.filter.search = search.value
    }
    if (statusFilter.value !== 'all') {
      params.filter.state = statusFilter.value
    }
    if (prioritiesFilter.value !== 'all') {
      params.filter.priority = prioritiesFilter.value
    }
    const res = await api.getIncidents(params)

    incidents.value.push(...res.data.data)
    total.value = res.data.meta.total
    lastPage.value = res.data.meta.last_page
  } catch (e) {
    console.error("Erro ao carregar entidades: ", e)
  } finally {
    loading.value = false
  }
}

watch([search, statusFilter, prioritiesFilter], () => {
  page.value = 1
  lastPage.value = Infinity
  incidents.value = []
  fetch()
})

watchDebounced(stateSearch, async (value) => {
  statePage.value = 1
  stateItems.value = []
  stateLastPage.value = Infinity
  await fetchStates(value)
}, { debounce: 300 })

watchDebounced(prioritySearch, async (value) => {
  priorityPage.value = 1
  priorityItems.value = []
  priorityLastPage.value = Infinity
  await fetchPriorities(value)
}, { debounce: 300 })

const scrollContainer = ref<HTMLElement | null>(null)

onMounted(() => {
  fetch()
  fetchStates()
  fetchPriorities()

  // ----------
  // Tables
  // ----------
  useInfiniteScroll(
    scrollContainer,
    () => {
      page.value++
      fetch()
    },
    {
      distance: 200,
      canLoadMore: () => !loading.value && page.value < lastPage.value
    }
  )

  // ----------
  // Filters
  // ----------
  // States
  useInfiniteScroll(
    () => stateMenu.value?.viewportRef,
    () => {
      if (statePage.value < stateLastPage.value) {
        statePage.value++
        fetchStates(stateSearch.value, true)
      }
    },
    {
      canLoadMore: () => !stateLoading.value && statePage.value < stateLastPage.value
    }
  )

  // Priorities
  useInfiniteScroll(
    () => priorityMenu.value?.viewportRef,
    () => {
      if (priorityPage.value < priorityLastPage.value) {
        priorityPage.value++
        fetchPriorities(prioritySearch.value, true)
      }
    },
    {
      canLoadMore: () => !priorityLoading.value && priorityPage.value < priorityLastPage.value
    }
  )
})
</script>

<template>
  <UDashboardPanel id="ocorrencia">
    <template #header>
      <UDashboardNavbar title="Ocorrências">
        <template #leading>
          <UDashboardSidebarCollapse />
        </template>
      </UDashboardNavbar>
    </template>
    <template #body>
      <div class="flex flex-wrap items-center justify-between gap-1.5">
        <UInput
          v-model="search"
          class="max-w-sm"
          icon="i-lucide-search"
          placeholder="Filtrar ocorrências..."
        />
        <div class="flex flex-wrap items-center gap-1.5">
          <USelectMenu
            ref="stateMenu"
            v-model="statusFilter"
            v-model:search-term="stateSearch"
            :items="stateItems"
            :loading="stateLoading"
            value-key="id"
            label-key="name"
            ignore-filter
            class="w-48"
            placeholder="Estado"
          />
          <USelectMenu
            ref="priorityMenu"
            v-model="prioritiesFilter"
            v-model:search-term="prioritySearch"
            :items="priorityItems"
            :loading="priorityLoading"
            value-key="id"
            label-key="name"
            ignore-filter
            class="w-48"
            placeholder="Prioridade"
          />
        </div>
      </div>
      <div ref="scrollContainer" class="overflow-x-auto max-h-[600px] overflow-y-auto">
        <UTable
          :data="incidents"
          :columns="columns"
          :loading="loading"
          :ui="{
            base: 'table-auto border-separate border-spacing-0',
            thead: '[&>tr]:bg-elevated/50 [&>tr]:after:content-none',
            tbody: '[&>tr]:last:[&>td]:border-b-0',
            th: 'py-2 first:rounded-l-lg last:rounded-r-lg border-y border-default first:border-l last:border-r',
            td: 'border-b border-default',
            separator: 'h-0'
          }"
          class="w-full"
        />
      </div>

      <!-- ????????????????????????????????????????
      <EntitiesDeleteModal
        v-if="selectedEntityById"
        v-model:open="deleteModalOpen"
        :id="selectedEntityById?.id"
        :name="selectedEntityById?.name"
        @deleted="fetch"
      />-->
    </template>
  </UDashboardPanel>
</template>
