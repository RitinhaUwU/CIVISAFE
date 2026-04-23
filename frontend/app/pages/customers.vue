<script setup lang="ts">
import { useApiStore } from '@/stores/api'
import type { TableColumn } from '@nuxt/ui'
import { getPaginationRowModel } from '@tanstack/table-core'

const api = useApiStore()
const users = ref<User[]>([])
const loading = ref(false)
const total = ref(0)

const search = ref('')

const deleteModalOpen = ref(false)
const selectedUserById = ref<User | null>(null)

type User = {
  id: number;
  name: string;
  email: string;
  password: string;
  mobile: string;
  locked: boolean;
  created_at: Date;
  updated_at: Date;
  deleted_at: Date;
}

const columns: TableColumn<User>[] = [
  {
    accessorFn: (row) => `#${row.id}`,
    header: "ID",
  },
  {
    accessorKey: "name",
    header: "Nome",
  },
  {
    accessorKey: "email",
    header: "Email",
  },
  {
    accessorKey: "mobile",
    header: "Contacto",
  },
  {
    accessorKey: "locked",
    header: () => h('div', { class: 'text-center w-full' }, 'Estado'),
    cell: ({ row }) => {
      const value = row.original.locked

      const color = value ? 'success' : 'error'
      const label = value ? 'Ativado' : 'Desativado'

      return h('div', { class: 'flex justify-center' },
        h(UButton, {
          color,
          onClick: () => { toggleUserLock(row.original) }
        }, () => label)
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
            //navigateTo(`/costumers/${row.original.id}`)
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

/*const toggleUserLock = async (user: User) => {
  try {
    await api.userUpdate(user.id)
    fetch()
  } catch (e) {
    console.error(e)
  }
}*/

const fetch = async() => {
  loading.value = true
  try {
    const params: any = {
      page: pagination.value.pageIndex + 1,
      per_page: pagination.value.pageSize
    }
    if (search.value) {
      params.filter = { search: search.value }
    }
    const res = await api.getUsers(params)

    users.value = res.data.data
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
  <UDashboardPanel id="utilizador">
    <template #header>
      <UDashboardNavbar title="Utilizadores">
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
          placeholder="Filtrar utilizadores..."
        />
      </div>
      <div class="overflow-x-auto">
        <UTable
          :data="users"
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
        v-if="selectedEntityById"
        v-model:open="deleteModalOpen"
        :id="selectedEntityById?.id"
        :name="selectedEntityById?.name"
        @deleted="fetch"
      />-->
    </template>
  </UDashboardPanel>
</template>
