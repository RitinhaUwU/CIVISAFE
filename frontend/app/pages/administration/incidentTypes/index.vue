<script setup lang="ts">
import { useApiStore } from '@/stores/api'
import type { TableColumn } from '@nuxt/ui'
import {UButton} from '#components'
import type {IncidentType} from '@/types'
import {extractCursor} from '@/utils'

const toast = useToast()
const api = useApiStore()

const incidentTypes = ref<IncidentType[]>([])
const nextCursor = ref<string | null>(null)
const loading = ref(false)

const search = ref('')

const columns: TableColumn<IncidentType | null>[] = [
  {
    accessorKey: "code",
    header: "Código",
  },
  {
    accessorKey: "species",
    header: "Espécie",
    cell: ({ row }) => {
      return h(
        'div',
        {
          class: 'max-w-[180px] truncate',
          title: row.getValue("species")
        },
        row.getValue("species")
      )
    }
  },
  {
    accessorKey: "type",
    header: "Tipo",
    cell: ({ row }) => {
      return h(
        'div',
        {
          class: 'max-w-[180px] truncate',
          title: row.getValue("type")
        },
        row.getValue("type")
      )
    }
  },
  {
    accessorKey: "updated_at",
    header: "Última Atualização",
    cell: ({row}) => {
      return new Date(row.getValue("updated_at")).toLocaleString("pt-PT");
    },
  },
  {
    id: 'actions',
    cell: ({ row }) => {
      return h(
        'div',
        { class: 'text-right' },
        h(UButton, {
          'data-testid': 'edit-volunteer',
          icon: 'i-lucide-info',
          color: 'info',
          variant: 'ghost',
          onClick: () => {
            navigateTo(`/administration/incidentTypes/${row.original.id}`)
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
    const res = await api.getIncidentTypes(params)

    const newIncidentTypes = res.data.data

    if (loadMore) {
      const existing = new Set(incidentTypes.value.map(it => it.id))
      incidentTypes.value.push(...newIncidentTypes.filter(it => !existing.has(it.id)))
    } else {
      incidentTypes.value = newIncidentTypes
    }

    nextCursor.value = extractCursor(res.data.links?.next)
  } catch (e) {
    toast.add({
      title: 'Erro',
      description: 'Erro ao carregar os tipos de ocorrências',
      color: 'error'
    })
  } finally {
    loading.value = false
  }
}

watchDebounced(search, async () => {
  nextCursor.value = null
  incidentTypes.value = []
  await fetch(false)
}, {debounce: 300})

const scrollContainer = ref<HTMLElement | null>(null)

onMounted(() => {

  if(!useAuthStore().hasPermission('INCIDENT_TYPES_LIST'))
  {
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
  <UDashboardPanel id="tipos-ocorrencias">
    <template #body>
      <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3 mb-4">
        <div>
          <h2 class="text-lg font-semibold">Tipos de Ocorrências</h2>
          <p class="text-sm text-muted max-w-md">Lista de todas os Tipos de Ocorrências.</p>
        </div>
        <IncidentTypesUploadModal v-if="useAuthStore().hasPermission('INCIDENT_TYPES_UPLOAD')" />
      </div>
      <div class="flex flex-wrap items-center justify-between gap-1.5">
        <UInput
          v-model="search"
          class="max-w-sm"
          icon="i-lucide-search"
          placeholder="Filtrar tipos..."
        />
      </div>
      <div ref="scrollContainer" class="overflow-x-auto max-h-[70vh] overflow-y-auto">
        <UTable
          :data="incidentTypes"
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
    </template>
  </UDashboardPanel>
</template>
