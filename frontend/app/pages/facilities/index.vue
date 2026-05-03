<script setup lang="ts">
import { useApiStore } from '@/stores/api'
import type { TableColumn } from '@nuxt/ui'
import { getPaginationRowModel } from '@tanstack/table-core'

const toast = useToast()
const api = useApiStore()

const facilities = ref<Facilities[]>([])
const loading = ref(false)
const total = ref(0)

const search = ref('')

const deleteModalOpen = ref(false)
const selectedFacilitiesById = ref<Facilities>(null)

type Facilities = {
  id: number;
  name: number;
  email: string;
  address: string;
  contact: string;
  description: boolean;
  created_at: Date;
  updated_at: Date;
}

const columns: TableColumn<Facilities | null>[] = [
  {
    accessorKey: "name",
    header: "Responsável",
    cell: ({ row }) => {
      return h('div', { class: 'flex flex-col' }, [
        h('span', { class: 'font-medium text-highlighted' }, row.original.name),
        h('span', { class: 'text-xs text-muted' }, row.original.email),
      ])
    }
  },
  {
    accessorKey: "contact",
    header: "Telefone",
  },
  {
    accessorKey: "address",
    header: "Sede",
  },
  {
    id: 'actions',
    cell: ({ row }) => {
      return h(
        'div',
        { class: 'text-right' },
        h(UButton, {
          icon: 'i-lucide-files',
          color: 'warning',
          variant: 'ghost',
          onClick: () => {
            navigateTo(`/facilities/${row.original.id}/files`)
          }
        }),
        h(UButton, {
          icon: 'i-lucide-info',
          color: 'info',
          variant: 'ghost',
          onClick: () => {
            navigateTo(`/facilities/${row.original.id}`)
          }
        }),
        h(UButton, {
          icon: 'i-lucide-trash',
          color: 'error',
          variant: 'ghost',
          onClick: () => {
            selectedFacilitiesById.value = row.original
            deleteModalOpen.value = true
          }
        })
      )
    }
  }
]

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
    }
    if (search.value) {
      params.filter = {
        search: search.value
      }
    }
    const res = await api.getFacilities(params)

    facilities.value = res.data.data
    total.value = res.data.meta.total
    pagination.value.pageSize = res.data.meta.per_page
  } catch (e) {
    toast.add({
      title: 'Erro',
      description: 'Erro ao carregar as instalações',
      color: 'error'
    })
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
  <UDashboardPanel id="instalações">
    <template #header>
      <UDashboardNavbar title="Intalações">
        <template #leading>
          <UDashboardSidebarCollapse />
        </template>
        <template #right>
          <FacilitiesAddModal @created="fetch" />
        </template>
      </UDashboardNavbar>
    </template>
    <template #body>
      <div class="flex flex-wrap items-center justify-between gap-1.5">
        <UInput
          v-model="search"
          class="max-w-sm"
          icon="i-lucide-search"
          placeholder="Filtrar intalações..."
        />
      </div>
      <div class="overflow-x-auto">
        <UTable
          :data="facilities"
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

      <FacilitiesDeleteModal
        v-if="selectedFacilitiesById"
        v-model:open="deleteModalOpen"
        :id="selectedFacilitiesById?.id"
        :name="selectedFacilitiesById?.name"
        @deleted="fetch"
      />
    </template>
  </UDashboardPanel>
</template>
