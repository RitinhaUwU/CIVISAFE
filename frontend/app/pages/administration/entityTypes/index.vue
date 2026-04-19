<script setup lang="ts">
import {useApiStore} from '@/stores/api'
import type {TableColumn} from '@nuxt/ui'
import {getPaginationRowModel} from '@tanstack/table-core'

const api = useApiStore()
const entityTypes = ref<Types[]>([])
const loading = ref(false)
const total = ref(0)

const search = ref('')

const deleteModalOpen = ref(false)
const selectedEntityTypeById = ref<Types>(null)

type Types = {
  id: number;
  name: string;
  description: string;
  created_at: Date;
  updated_at: Date;
}

const columns: TableColumn<Types>[] = [
  {
    accessorKey: "name",
    header: "Nome",
  },
  {
    accessorKey: "description",
    header: "Descrição",
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
          icon: 'i-lucide-info',
          color: 'info',
          variant: 'ghost',
          onClick: () => {
            navigateTo(`/administration/entityTypes/${row.original.id}`)
          }
        }),
        h(UButton, {
          icon: 'i-lucide-trash',
          color: 'error',
          variant: 'ghost',
          onClick: () => {
            selectedEntityTypeById.value = row.original
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
    const res = await api.getEntityTypes(params)

    entityTypes.value = res.data.data
    total.value = res.data.meta.total
    pagination.value.pageSize = res.data.meta.per_page
  } catch (e) {
    console.error("Erro ao carregar os tipos de entidade: ", e)
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
  <UDashboardPanel id="entidades">
    <template #body>
      <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3 mb-4">
        <div>
          <h2 class="text-lg font-semibold">Tipos de Entidades</h2>
          <p class="text-sm text-muted max-w-md">Lista de todos os tipos de Entidade.</p>
        </div>
        <EntityTypesAddModal @created="fetch" />
      </div>
      <div class="flex flex-wrap items-center justify-between gap-1.5">
        <UInput
          v-model="search"
          class="max-w-sm"
          icon="i-lucide-search"
          placeholder="Filtrar tipos..."
        />
      </div>
      <div class="overflow-x-auto">
        <UTable
          :data="entityTypes"
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

      <EntityTypesDeleteModal
        v-model:open="deleteModalOpen"
        :id="selectedEntityTypeById?.id"
        :name="selectedEntityTypeById?.name"
        @deleted="fetch"
      />
    </template>
  </UDashboardPanel>
</template>
