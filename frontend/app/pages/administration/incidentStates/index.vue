<script setup lang="ts">
import { useApiStore } from '@/stores/api'
import type { TableColumn } from '@nuxt/ui'
import { getPaginationRowModel } from '@tanstack/table-core'

const toast = useToast()
const api = useApiStore()

const states = ref<States[]>([])
const loading = ref(false)
const total = ref(0)

const search = ref('')
const statusFilter = ref('all')
const terminatesFilter = ref('all')

const deleteModalOpen = ref(false)
const selectedStateById = ref<States | null>(null)

type States = {
  id: number;
  name: string;
  description: string;
  hex_color: string;
  terminates_incident: boolean;
  is_active: boolean;
}

const columns: TableColumn<States>[] = [
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
          icon: 'i-lucide-info',
          color: 'info',
          variant: 'ghost',
          onClick: () => {
            navigateTo(`/administration/incidentStates/${row.original.id}`)
          }
        }),
        h(UButton, {
          icon: 'i-lucide-trash',
          color: 'error',
          variant: 'ghost',
          onClick: () => {
            selectedStateById.value = row.original
            deleteModalOpen.value = true
          }
        })
      )
    }
  }
];

const pagination = ref({
  pageIndex: 0,
  pageSize: 10,
})

const fetch = async() => {
  loading.value = true
  try {
    const params: any = {
      page: pagination.value.pageIndex + 1,
      per_page: pagination.value.pageSize,
    };
    if (search.value) {
      params.filter = {
        search: search.value
      };
    }
    if (statusFilter.value !== 'all') {
      params.filter = {
        ...params.filter,
        status: statusFilter.value === true ? 1 : 0
      };
    }
    if (terminatesFilter.value !== 'all') {
      params.filter = {
        ...params.filter,
        terminates: terminatesFilter.value === true ? 1 : 0
      };
    }
    const res = await api.getIncidentStates(params)

    states.value = res.data.data
    total.value = res.data.meta.total
    pagination.value.pageSize = res.data.meta.per_page
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

watch(pagination, fetch, {deep: true})

watch([search, statusFilter, terminatesFilter], () => {
  pagination.value.pageIndex = 0
  fetch()
})

onMounted(fetch)
</script>

<template>
  <UDashboardPanel id="estados-ocorrencias">
    <template #body>
      <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3 mb-4">
        <div>
          <h2 class="text-lg font-semibold">Estados de Ocorrências</h2>
          <p class="text-sm text-muted max-w-md">Lista de todas os Tipos de Estado de Ocorrências.</p>
        </div>
        <IncidentStatesAddModal @created="fetch" />
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
            placeholder="Filter status"
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
            placeholder="Filter status"
            class="min-w-28"
          />
        </div>
      </div>
      <div class="overflow-x-auto">
        <UTable
          :data="states"
          :columns="columns"
          :loading="loading"
          v-model:pagination="pagination"
          :pagination-options="{
            getPaginationRowModel: getPaginationRowModel(),
            rowCount: total,
            manualPagination: true,
          }"
          :ui="{
            base: 'table-fixed border-separate border-spacing-0',
            thead: '[&>tr]:bg-elevated/50 [&>tr]:after:content-none',
            tbody: '[&>tr]:last:[&>td]:border-b-0',
            th: 'py-2 first:rounded-l-lg last:rounded-r-lg border-y border-default first:border-l last:border-r',
            td: 'border-b border-default',
            separator: 'h-0'
          }"
          class="w-full min-w-[500px]"
        />
      </div>

      <div class="flex justify-end border-t border-default pt-4 mt-auto">
        <UPagination
          :page="pagination.pageIndex + 1"
          :items-per-page="pagination.pageSize"
          :total="total"
          @update:page="(p) => (pagination.pageIndex = p - 1)"
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
