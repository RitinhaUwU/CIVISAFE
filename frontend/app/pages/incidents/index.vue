<script setup lang="ts">
import { useApiStore } from '@/stores/api'
import {useAuthStore} from "@/stores/auth";
import type { TableColumn } from '@nuxt/ui'
import type {Incident} from "@/types";
import {UBadge, UButton} from "#components";
import {usePaginatedSelect} from "@/composables/usePaginatedSelect";
import {extractCursor} from '@/utils';

const api = useApiStore()

const incidents = ref<Incident[]>([])
const nextCursor = ref<string | null>(null)
const loading = ref(false)

const search = ref('')

const is_majorFilter = ref<boolean|string>('all')

const allStatesOption = {
  id: 'all',
  name: 'Estados'
}

const allPrioritiesOption = {
  id: 'all',
  name: 'Prioridades'
}

const statusFilter = ref(allStatesOption)
const prioritiesFilter = ref(allPrioritiesOption)

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
          icon: 'i-lucide-notebook-text',
          color: 'warning',
          variant: 'ghost',
          onClick: () => {
            navigateTo(`/incidents/${row.original.id}/dashboard`)
          }
        }),
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

const fetch = async (loadMore = false) => {
  if (loading.value) return

  loading.value = true

  try {
    const params: any = {
      per_page: 10,
      filter: {},
      ...(loadMore && nextCursor.value ? { cursor: nextCursor.value } : {})
    }
    if (search.value) {
      params.filter.search = search.value
    }
    if (statusFilter.value?.id !== 'all') {
      params.filter.state = statusFilter.value?.id
    }
    if (prioritiesFilter.value?.id !== 'all') {
      params.filter.priority = prioritiesFilter.value?.id
    }
    if (is_majorFilter.value !== 'all') {
      params.filter.is_major = is_majorFilter.value
    }
    const res = await api.getIncidents(params)

    const newIncidents = res.data.data

    if (loadMore) {
      const existing = new Set(incidents.value.map(i => i.id))
      incidents.value.push(...newIncidents.filter(i => !existing.has(i.id)))
    } else {
      incidents.value = newIncidents
    }

    nextCursor.value = extractCursor(res.data.links?.next)
  } catch (e) {
    console.error("Erro ao carregar entidades: ", e)
  } finally {
    loading.value = false
  }
}

watchDebounced([search, statusFilter, prioritiesFilter, is_majorFilter], async () => {
  nextCursor.value = null
  incidents.value = []
  await fetch(false)
}, {debounce: 300})

const scrollContainer = ref<HTMLElement | null>(null)

onMounted(async () => {
  if(!useAuthStore().hasPermission('INCIDENTS_LIST')){
    await useRouter().push('/inicio');
    return;
  }

  await fetch()
  await states.fetchItems()
  await priorities.fetchItems()

  useInfiniteScroll(
    scrollContainer,
    () => {
      if (!nextCursor.value) return
      fetch(true)
    },
    {
      distance: 200,
      canLoadMore: () => !loading.value && !!nextCursor.value
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
              allStatesOption,
              ...states.items.value
            ]"
            :loading="states.loading.value"
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
              allPrioritiesOption,
              ...priorities.items.value
            ]"
            :loading="priorities.loading.value"
            label-key="name"
            ignore-filter
            class="w-48"
            placeholder="Prioridade"
          />
        </div>
      </div>
      <div ref="scrollContainer" class="overflow-x-auto max-h-[80vh] overflow-y-auto">
        <UTable
          v-if="loading || incidents.length > 0"
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
        <div v-else class="flex items-center justify-center py-12 text-center text-muted">
          Nenhum registo de ocorrência encontrado.
        </div>
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
