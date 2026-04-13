<script setup lang="ts">
import { useApiStore } from '@/stores/api'
import type { TableColumn } from '@nuxt/ui'
import { getPaginationRowModel } from '@tanstack/table-core'

const api = useApiStore()
const incidents = ref<Incident[]>([])
const loading = ref(false)
const total = ref(0)

const search = ref('')
const statusFilter = ref('all')
const prioritiesFilter = ref('all')

const deleteModalOpen = ref(false)
const selectedIncidentById = ref<Incident>(null)

type Incident = {
  id: number;
  identifier: string;
  incident_type_code: number;
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
    accessorKey: "incident_state_id",
    header: "Estado",
  },
  {
    accessorKey: "incident_priority_id",
    header: "Prioridade",
  },
  {
    accessorKey: "incident_type_code",
    header: "Categoria",
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

const pagination = ref({
  pageIndex: 0,
  pageSize: 10
})

const fetch = async() => {
  loading.value = true
  try {
    const params: any = {
      page: pagination.value.pageIndex + 1,
      per_page: pagination.value.pageSize,
    }
    if (search.value) {
      params.filter = {
        search: search.value
      }
    }
    const res = await api.getIncidents(params)

    incidents.value = res.data.data
    total.value = res.data.meta.total
    pagination.value.pageSize = res.data.meta.per_page
  } catch (e) {
    console.error("Erro ao carregar entidades: ", e)
  } finally {
    loading.value = false
  }
}

watch(pagination, fetch, {deep: true})

watch(search, () => {
  pagination.value.pageIndex = 0
  fetch()
})

onMounted(fetch)
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
            :items="[
              { label: 'Estado', value: 'all' },
              { label: 'Subscribed', value: 'subscribed' },
              { label: 'Unsubscribed', value: 'unsubscribed' },
              { label: 'Bounced', value: 'bounced' }
            ]"
            :ui="{ trailingIcon: 'group-data-[state=open]:rotate-180 transition-transform duration-200' }"
            placeholder="Filter status"
            class="min-w-28"
          />
          <UDropdownMenu
            :items="
              table?.tableApi
                ?.getAllColumns()
                .filter((column: any) => column.getCanHide())
                .map((column: any) => ({
                  label: upperFirst(column.id),
                  type: 'checkbox' as const,
                  checked: column.getIsVisible(),
                  onUpdateChecked(checked: boolean) {
                    table?.tableApi?.getColumn(column.id)?.toggleVisibility(!!checked)
                  },
                  onSelect(e?: Event) {
                    e?.preventDefault()
                  }
                }))
            "
            :content="{ align: 'end' }"
          >
          </UDropdownMenu>
          <USelect
            v-model="prioritiesFilter"
            :items="[
              { label: 'Prioridade', value: 'all' },
              { label: 'Subscribed', value: 'subscribed' },
              { label: 'Unsubscribed', value: 'unsubscribed' },
              { label: 'Bounced', value: 'bounced' }
            ]"
            :ui="{ trailingIcon: 'group-data-[state=open]:rotate-180 transition-transform duration-200' }"
            placeholder="Filter status"
            class="min-w-28"
          />
          <UDropdownMenu
            :items="
              table?.tableApi
                ?.getAllColumns()
                .filter((column: any) => column.getCanHide())
                .map((column: any) => ({
                  label: upperFirst(column.id),
                  type: 'checkbox' as const,
                  checked: column.getIsVisible(),
                  onUpdateChecked(checked: boolean) {
                    table?.tableApi?.getColumn(column.id)?.toggleVisibility(!!checked)
                  },
                  onSelect(e?: Event) {
                    e?.preventDefault()
                  }
                }))
            "
            :content="{ align: 'end' }"
          >
          </UDropdownMenu>
        </div>
      </div>

      <div class="overflow-x-auto">
        <UTable
          :data="incidents"
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
          class="w-full min-w-[640px]"
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

      <!-- ????????????????????????????????????????
      <EntitiesDeleteModal
        v-model:open="deleteModalOpen"
        :id="selectedEntityById?.id"
        :name="selectedEntityById?.name"
        @deleted="fetch"
      />-->
    </template>
  </UDashboardPanel>
</template>
