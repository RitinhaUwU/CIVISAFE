<script setup lang="ts">
import { useApiStore } from '@/stores/api'
import type { TableColumn } from '@nuxt/ui'
import type {IncidentState} from "@/types";
import {UBadge, UButton} from "#components";

const toast = useToast()
const api = useApiStore()

const states = ref<IncidentState[]>([])
const nextCursor = ref<string | null>(null)
const loading = ref(false)

const search = ref('')
const statusFilter = ref<boolean|string>('all')
const terminatesFilter = ref<boolean|string>('all')

const deleteModalOpen = ref(false)
const selectedStateById = ref<IncidentState | null>(null)

const columns: TableColumn<IncidentState>[] = [
  {
    accessorKey: "name",
    header: "Nome",
    cell: ({ row }) => {
      return h(
        'div',
        { class: 'flex items-center gap-2' },
        [
          h('span', {
            class: 'size-3 rounded-full border',
            style: {
              backgroundColor: row.original.hex_color
            }
          }),
          h('span', { class: 'font-medium' }, row.original.name)
        ]
      )
    }
  },
  {
    accessorKey: "description",
    header: "Descrição",
    cell: ({ row }) => {
      return h(
        'div',
        {
          class: 'truncate max-w-[250px]'
        },
        row.original.description
      )
    }
  },
  {
    accessorKey: "terminates_incident",
    header: () => h('div', { class: 'text-center w-full' }, 'Ocorrência Termina'),
    //@ts-ignore
    meta: { class: 'text-center' },
    cell: ({ row }) => {
      const value = row.original.terminates_incident

      const color = value ? 'success' : 'error'
      const label = value ? 'Sim' : 'Não'

      return h(
        'div',
        { class: 'flex justify-center' },
        h(UBadge, { class: 'capitalize, rounded-full', variant: 'subtle', color }, () => label)
      )
    }
  },
  {
    accessorKey: "is_active",
    header: () => h('div', { class: 'text-center w-full' }, 'Estado'),
    cell: ({ row }) => {
      const value = row.original.is_active

      const color = value ? 'success' : 'error'
      const label = value ? 'Ativado' : 'Desativado'

      return h(
        'div',
        { class: 'flex justify-center' },
        h(UBadge, { class: 'capitalize, rounded-full', variant: 'subtle', color }, () => label)
      )
    }
  },
  {
    id: 'actions',
    cell: ({ row }) => {
      return h(
        'div',
        { class: 'text-right' },
        h(UButton, {
          'data-testid': 'edit-state',
          icon: 'i-lucide-info',
          color: 'info',
          variant: 'ghost',
          onClick: () => {
            navigateTo(`/administration/incidentStates/${row.original.id}`)
          }
        }),
        //@ts-ignore
        h(UButton, {
          'data-testid': 'delete-state',
          icon: 'i-lucide-trash',
          color: 'error',
          variant: 'ghost',
          disabled: !useAuthStore().hasPermission('INCIDENT_STATES_DELETE'),
          onClick: () => {
            selectedStateById.value = row.original
            deleteModalOpen.value = true
          }
        })
      )
    }
  }
];

const extractCursor = (url: string | null) => {
  if (!url) return null
  try {
    return new URL(url).searchParams.get('cursor')
  } catch {
    return null
  }
}

const fetch = async (loadMore = false) => {
  if (loading.value) return

  loading.value = true

  try {
    const params: any = {
      per_page: 10,
      filter: {},
      ...(loadMore && nextCursor.value ? { cursor: nextCursor.value } : {})
    };
    if (search.value) {
      params.filter.search = search.value
    }
    if (statusFilter.value !== 'all') {
      params.filter.status = statusFilter.value === true ? 1 : 0
    }
    if (terminatesFilter.value !== 'all') {
      params.filter.terminates = terminatesFilter.value === true ? 1 : 0
    }
    const res = await api.getIncidentStates(params)

    const newIncidentStates = res.data.data

    if (loadMore) {
      const existing = new Set(states.value.map(is => is.id))
      states.value.push(...newIncidentStates.filter(is => !existing.has(is.id)))
    } else {
      states.value = newIncidentStates
    }

    nextCursor.value = extractCursor(res.data.links?.next)
  } catch (e) {
    toast.add({
      title: 'Erro',
      description: 'Erro ao carregar os estados das ocorrências',
      color: 'error'
    })
  } finally {
    loading.value = false
  }
}

watch([search, statusFilter, terminatesFilter], async () => {
  nextCursor.value = null
  states.value = []
  fetch(false)
})

const scrollContainer = ref<HTMLElement | null>(null)

onMounted(() => {

  if(!useAuthStore().hasPermission('INCIDENT_STATES_LIST')){
    useRouter().push('/inicio');
    return;
  }

  fetch()

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
  <UDashboardPanel id="estados-ocorrencias">
    <template #body>
      <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3 mb-4">
        <div>
          <h2 class="text-lg font-semibold">Estados de Ocorrências</h2>
          <p class="text-sm text-muted max-w-md">Lista de todas os Tipos de Estado de Ocorrências.</p>
        </div>
        <IncidentStatesAddModal
          @created="fetch"
          v-if="useAuthStore().hasPermission('INCIDENT_STATES_CREATE')"
        />
      </div>
      <div class="flex flex-wrap items-center justify-between gap-1.5">
        <UInput
          v-model="search"
          class="max-w-sm"
          icon="i-lucide-search"
          placeholder="Filtrar estados..."
        />
        <div class="flex flex-wrap items-center gap-1.5">
          <USelect
            v-model="terminatesFilter"
            :items="[
              { label: 'Ocorrência Terminada', value: 'all' },
              { label: 'Sim', value: true },
              { label: 'Não', value: false }
            ]"
            :ui="{ trailingIcon: 'group-data-[state=open]:rotate-180 transition-transform duration-200' }"
            class="min-w-28"
          />
          <USelect
            v-model="statusFilter"
            :items="[
              { label: 'Estado', value: 'all' },
              { label: 'Ativo', value: true },
              { label: 'Desativado', value: false }
            ]"
            :ui="{ trailingIcon: 'group-data-[state=open]:rotate-180 transition-transform duration-200' }"
            class="min-w-28"
          />
        </div>
      </div>
      <div ref="scrollContainer" class="overflow-x-auto max-h-[600px] overflow-y-auto">
        <UTable
          :data="states"
          :columns="columns"
          :loading="loading"
          :ui="{
            base: 'table-fixed border-separate border-spacing-0',
            thead: '[&>tr]:bg-elevated/50 [&>tr]:after:content-none',
            tbody: '[&>tr]:last:[&>td]:border-b-0',
            th: 'py-2 first:rounded-l-lg last:rounded-r-lg border-y border-default first:border-l last:border-r',
            td: 'border-b border-default',
            separator: 'h-0'
          }"
          class="w-full"
        />
      </div>
      <IncidentStatesDeleteModal
        v-if="selectedStateById"
        v-model:open="deleteModalOpen"
        :id="selectedStateById?.id"
        :name="selectedStateById?.name"
        @deleted="fetch"
      />
    </template>
  </UDashboardPanel>
</template>
