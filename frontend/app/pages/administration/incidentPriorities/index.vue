<script setup lang="ts">
import { useApiStore } from '@/stores/api'
import type { TableColumn } from '@nuxt/ui'
import type { IncidentPriority } from "@/types";
import {UBadge, UButton} from "#components";

const toast = useToast()
const api = useApiStore()

const priorities = ref<IncidentPriority[]>([])
const page = ref(1)
const lastPage = ref<number>(Infinity)
const loading = ref(false)
const total = ref(0)

const search = ref('')
const statusFilter = ref<boolean|string>('all')

const deleteModalOpen = ref(false)
const selectedPriorityById = ref<IncidentPriority | null>(null)

const columns: TableColumn<IncidentPriority>[] = [
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
            style: { backgroundColor: row.original.hex_color }
          }),
          h('span', { class: 'font-medium' }, row.original.name)
        ]
      )
    }
  },
  {
    accessorKey: "description",
    header: "Descrição",
  },
  {
    accessorKey: "is_active",
    header: () => h('div', { class: 'text-center w-full' }, 'Estado'),
    cell: ({ row }) => {
      const value = row.original.is_active;
      const color = value ? 'success' : 'error';
      const label = value ? 'Ativado' : 'Desativado';

      return h(
        'div',
        { class: 'flex justify-center' },
        h(UBadge, { class: 'rounded-full', variant: 'subtle', color }, () => label)
      )
    }
  },
  {
    id: 'actions',
    cell: ({ row }) => {
      return h(
        'div',
        { class: 'text-right' },
        [
          h(UButton, {
            'data-testid': 'edit-priority',
            icon: 'i-lucide-info',
            color: 'info',
            variant: 'ghost',
            onClick: () => {
              navigateTo(`/administration/incidentPriorities/${row.original.id}`)
            }
          }),
          h(UButton, {
            'data-testid': 'delete-priority',
            icon: 'i-lucide-trash',
            color: 'error',
            variant: 'ghost',
            disabled: !useAuthStore().hasPermission('INCIDENT_PRIORITIES_DELETE'),
            onClick: () => {
              selectedPriorityById.value = row.original
              deleteModalOpen.value = true
            }
          })
        ]
      )
    }
  }
]

const fetch = async () => {
  if (loading.value) return
  if (page.value > lastPage.value) return

  loading.value = true
  try {
    const params: any = {
      page: page.value,
      per_page: 10
    }

    if (search.value) {
      params.filter = { search: search.value }
    }

    if (statusFilter.value !== 'all') {
      params.filter = {
        ...params.filter,
        status: statusFilter.value === true ? 1 : 0
      }
    }
    const res = await api.getIncidentPriorities(params)

    priorities.value.push(...res.data.data)
    total.value = res.data.meta.total
    lastPage.value = res.data.meta.last_page
  } catch (e) {
    toast.add({
      title: 'Erro',
      description: 'Erro ao carregar os tipos de prioridades',
      color: 'error'
    })
  } finally {
    loading.value = false
  }
}

watch([search, statusFilter], () => {
  page.value = 1
  lastPage.value = Infinity
  priorities.value = []
  fetch();
})

const scrollContainer = ref<HTMLElement | null>(null)

onMounted(() => {

  if(!useAuthStore().hasPermission('INCIDENT_PRIORITIES_LIST')){
    useRouter().push('/inicio');
    return;
  }

  fetch()

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
  <UDashboardPanel id="prioridades-ocorrencias">
    <template #body>
      <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3 mb-4">
        <div>
          <h2 class="text-lg font-semibold">Prioridades de Ocorrências</h2>
          <p class="text-sm text-muted max-w-md">Lista de todas os Tipos de Prioridades.</p>
        </div>
          <IncidentPrioritiesAddModal
            @created="fetch"
            v-if="useAuthStore().hasPermission('INCIDENT_PRIORITIES_CREATE')"
          />
      </div>
      <div class="flex flex-wrap items-center justify-between gap-1.5">
        <UInput
          v-model="search"
          class="max-w-sm"
          icon="i-lucide-search"
          placeholder="Filtrar prioridades..."
        />
        <div class="flex flex-wrap items-center gap-1.5">
          <USelect
            v-model="statusFilter"
            :items="[
              { label: 'Estado', value: 'all' },
              { label: 'Ativo', value: true },
              { label: 'Desativado', value: false }
            ]"
            :ui="{ trailingIcon: 'group-data-[state=open]:rotate-180 transition-transform duration-200' }"
            placeholder="Filter status"
            class="min-w-28"
          />
        </div>
      </div>
      <div ref="scrollContainer" class="overflow-x-auto max-h-[600px] overflow-y-auto">
        <UTable
          :data="priorities"
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
      <IncidentPrioritiesDeleteModal
        v-if="selectedPriorityById"
        v-model:open="deleteModalOpen"
        :id="selectedPriorityById?.id"
        :name="selectedPriorityById?.name"
        :description="selectedPriorityById?.description"
        @deleted="fetch"
      />
    </template>
  </UDashboardPanel>
</template>
