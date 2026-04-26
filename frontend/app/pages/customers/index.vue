<script setup lang="ts">
import type { TableColumn } from '@nuxt/ui'
import { getPaginationRowModel } from '@tanstack/table-core'
import { useAuthStore } from '@/stores/auth'
import { useApiStore } from '@/stores/api'

const api = useApiStore()
const auth = useAuthStore()
const users = ref<User[]>([])
const loading = ref(false)
const total = ref(0)

const search = ref('')

const deleteModalOpen = ref(false)
const selectedUserById = ref<User | null>(null)

const roleLabels = {
  admin: 'Administrador',
  manager: 'Gestor',
  user: 'Utilizador',
}

type User = {
  id: number;
  name: string;
  email: string;
  mobile: string;
  locked: boolean;
  created_at: Date;
  updated_at: Date;
  deleted_at: Date;
  roles: string[];
}

const columns: TableColumn<User>[] = [
  {
    accessorFn: (row) => `#${row.id}`,
    header: "ID",
  },
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
    accessorKey: "mobile",
    header: "Contacto",
  },
  {
    accessorKey: "roles",
    header: () => h('div', { class: 'text-center w-full' }, 'Função'),
    cell: ({ row }) => {
      const roles = row.original.roles || []
      return h('div', { class: 'flex gap-2 justify-center' },
        roles.map(role => {
          const label = roleLabels[role] || role
          let color = 'neutral'
          if (role === 'admin') color = 'error'
          if (role === 'manager') color = 'warning'
          if (role === 'user') color = 'success'

          return h(UBadge, {class: 'capitalize rounded-full', variant: 'subtle', color}, () => label)
        })
      )
    }
  },
  {
    id: 'actions',
    cell: ({ row }) => {
      const isLocked = row.original.locked
      return h('div', { class: 'text-right flex gap-1 justify-end' }, [
        (auth.hasPermission('USERS_UPDATE_ANY') || auth.hasPermission('USERS_UPDATE_OWN')) && h(
          UTooltip,
          { text: row.original.id === auth.currentUserID ? 'Não podes alterar a tua própria conta' : (isLocked ? 'Clique para ativar conta' : 'Clique para desativar conta') },
          { default: () => h(UButton, {
              icon: isLocked ? 'i-lucide-lock-keyhole-open' : 'i-lucide-lock-keyhole',
              variant: 'ghost',
              color: 'primary',
              onClick: () => patchUser(row.original),
              disabled: row.original.id === auth.currentUserID
            })
          }
        ),
        auth.hasPermission('USERS_VIEW_ANY') && (auth.hasPermission('USERS_UPDATE_ANY') || auth.hasPermission('USERS_UPDATE_OWN')) && h(UButton, {
          icon: 'i-lucide-info',
          color: 'info',
          variant: 'ghost',
          onClick: () => navigateTo(`/customers/${row.original.id}`)
        }),
        auth.hasPermission('USERS_DELETE') && h(UButton, {
          icon: 'i-lucide-trash',
          color: 'error',
          variant: 'ghost',
          onClick: () => {
            selectedUserById.value = row.original
            deleteModalOpen.value = true
          }
        })
      ].filter(Boolean))
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

const patchUser = async (user: User) => {
  if (!auth.hasPermission('USERS_UPDATE_ANY') && !auth.hasPermission('USERS_UPDATE_OWN')) return

  try {
    await api.patchUser(user.id, {
      locked: !user.locked
    })
    await fetch()
  } catch (e) {
    console.error(e)
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
          <UDashboardSidebarCollapse @created="fetch" />
        </template>
        <template #right>
          <CustomersAddModal @created="fetch" v-if="auth.hasPermission('USERS_CREATE')" />
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

      <CustomersDeleteModal
        v-if="auth.hasPermission('USERS_DELETE') && selectedUserById"
        v-model:open="deleteModalOpen"
        :id="selectedUserById?.id"
        :name="selectedUserById?.name"
        @deleted="fetch"
      />
    </template>
  </UDashboardPanel>
</template>
