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
const prioritiesFilter = ref('all')
const states = ref([])
const priorities = ref([])

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

const fetchFilters = async () => {
  const [statesRes, prioritiesRes] = await Promise.all([
    api.getIncidentStates(),
    api.getIncidentPriorities(),
  ])

  states.value = statesRes.data.data
  priorities.value = prioritiesRes.data.data
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

const scrollContainer = ref<HTMLElement | null>(null)

onMounted(() => {
  fetch()
  fetchFilters()

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
          <USelect
            v-model="statusFilter"
            class="w-48"
            :items="[
              { label: 'Todos', value: 'all' },
              ...states.map(s => ({
              label: s.name,
              value: s.id
              }))
            ]"
          />
          <USelect
            v-model="prioritiesFilter"
            class="w-48"
            :items="[
              { label: 'Todas', value: 'all' },
              ...priorities.map(p => ({
              label: `${p.name} - ${p.description}`,
              value: p.id
              }))
            ]"
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
