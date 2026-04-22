<script setup lang="ts">
import { useApiStore } from '@/stores/api'
import type { TableColumn } from '@nuxt/ui'
import { getPaginationRowModel } from '@tanstack/table-core'

const toast = useToast()
const api = useApiStore()

const entities = ref<Entity[]>([])
const loading = ref(false)
const total = ref(0)

const search = ref('')

const deleteModalOpen = ref(false)
const selectedEntityById = ref<Entity | null>(null)

type Entity = {
  id: number;
  name: string;
  description: string;
  phone_contact: string;
  email_contact: string;
  address: string;
  logo: string;
  poc_name: string;
  poc_phone: string;
  poc_email: string;
  created_at: Date;
  updated_at: Date;
}

const columns: TableColumn<Entity>[] = [
  {
    accessorKey: "entityType.name",
    header: "Tipo",
  },
  {
    accessorKey: "name",
    header: "Nome",
  },
  {
    accessorKey: "phone_contact",
    header: "Telefone",
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
            navigateTo(`/administration/entities/${row.original.id}`)
          }
        }),
        h(UButton, {
          icon: 'i-lucide-trash',
          color: 'error',
          variant: 'ghost',
          onClick: () => {
            selectedEntityById.value = row.original
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
    const res = await api.getEntities(params)

    entities.value = res.data.data
    total.value = res.data.meta.total
    pagination.value.pageSize = res.data.meta.per_page
  } catch (e) {
    toast.add({
      title: 'Erro',
      description: 'Erro ao carregar entidades',
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
  <UDashboardPanel id="entidades">
    <template #body>
      <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3 mb-4">
        <div>
          <h2 class="text-lg font-semibold">Entidades</h2>
          <p class="text-sm text-muted max-w-md">Lista de todas as Entidades.</p>
        </div>
        <EntitiesAddModal @created="fetch" />
      </div>
      <div class="flex flex-wrap items-center justify-between gap-1.5">
        <UInput
          v-model="search"
          class="max-w-sm"
          icon="i-lucide-search"
          placeholder="Filtrar entidades..."
        />
      </div>
      <div class="overflow-x-auto">
        <UTable
          :data="entities"
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

      <EntitiesDeleteModal
        v-if="selectedEntityById"
        v-model:open="deleteModalOpen"
        :id="selectedEntityById?.id"
        :name="selectedEntityById?.name"
        @deleted="fetch"
      />
    </template>
  </UDashboardPanel>
</template>
