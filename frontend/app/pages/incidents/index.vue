<script setup lang="ts">
import { useApiStore } from '@/stores/api'
import {useAuthStore} from "@/stores/auth";
import type { TableColumn } from '@nuxt/ui'
import type {Incident} from "~/types";
import {UBadge, UButton} from "#components";
import {usePaginatedSelect} from "~/composables/usePaginatedSelect";
import state from "pusher-js/src/core/http/state";

const api = useApiStore()

const incidents = ref<Incident[]>([])
const page = ref(1)
const lastPage = ref<number>(Infinity)
const loading = ref(false)
const total = ref(0)

const search = ref('')

const is_majorFilter = ref<boolean|string>('all')

const statusFilter = ref('all')
const prioritiesFilter = ref('all')
const stateMenu = useTemplateRef('stateMenu')
const priorityMenu = useTemplateRef('priorityMenu')

const states = usePaginatedSelect({
  fetcher: api.getIncidentStates,
  menuRef: stateMenu,
  map: (s: any) => ({
    id: s.id,
    name: s.name
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

const createModalOpen = ref(false)
const deleteModalOpen = ref(false)
const selectedIncidentById = ref<Incident | null>(null)

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
        //@ts-ignore
        h(UButton, {
          icon: 'i-lucide-trash',
          color: 'error',
          variant: 'ghost',
          disabled: !useAuthStore().hasPermission('INCIDENTS_DELETE'),
          onClick: () => {
            selectedIncidentById.value = row.original
            deleteModalOpen.value = true
          }
        })
      )
    }
  }
]

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
    if (is_majorFilter.value !== 'all') {
      params.filter.is_major = is_majorFilter.value
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

watch([search, statusFilter, prioritiesFilter, is_majorFilter], () => {
  page.value = 1
  lastPage.value = Infinity
  incidents.value = []
  fetch()
})

const scrollContainer = ref<HTMLElement | null>(null)

onMounted(() => {
  if(!useAuthStore().hasPermission('INCIDENTS_LIST')){
    useRouter().push('/inicio');
    return;
  }

  fetch()
  states.fetchItems()
  priorities.fetchItems()

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
          <UDashboardSidebarCollapse @created="fetch" />
        </template>
        <template #right>
          <UButton
            label="Nova Ocorrência"
            icon="i-lucide-plus"
            @click="createModalOpen = true"
          />
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
            v-model="is_majorFilter"
            :items="[
              { label: 'Ocorrência Major', value: 'all' },
              { label: 'Sim', value: true },
              { label: 'Não', value: false }
            ]"
            class="min-w-48"
          />
          <USelectMenu
            ref="stateMenu"
            v-model="statusFilter"
            v-model:search-term="states.search.value"
            :items="[
              { id: 'all', name: 'Estados' },
              ...states.items.value
            ]"
            :loading="states.loading.value"
            value-key="id"
            label-key="name"
            ignore-filter
            class="w-48"
            placeholder="Estado"
          />
          <USelectMenu
            ref="priorityMenu"
            v-model="prioritiesFilter"
            v-model:search-term="priorities.search.value"
            :items="[
              { id: 'all', name: 'Prioridades' },
              ...priorities.items.value
            ]"
            :loading="priorities.loading.value"
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
      <IncidentsDeleteModal
        v-if="selectedIncidentById"
        v-model:open="deleteModalOpen"
        :id="selectedIncidentById?.id"
        :identifier="selectedIncidentById?.identifier"
        @deleted="fetch"
      />
      <InicioFormRegisto
        v-model="createModalOpen"
        :coords="{ lat: 39.9139, lng: -8.1547 }"
        @created="fetch"
      />
    </template>
  </UDashboardPanel>
</template>
