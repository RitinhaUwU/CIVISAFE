<script setup lang="ts">
import type { TableColumn } from '@nuxt/ui'
import { useAuthStore } from '@/stores/auth'
import { useApiStore } from '@/stores/api'

const api = useApiStore()
const auth = useAuthStore()
const toast = useToast()

const users = ref<User[]>([])
const page = ref(1)
const lastPage = ref<number>(Infinity)
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
  mobile: number;
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
          onClick: () => navigateTo(`/users/${row.original.id}`)
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

const fetch = async() => {
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
    const res = await api.getUsers(params)

    users.value.push(...res.data.data)
    total.value = res.data.meta.total
    lastPage.value = res.data.meta.last_page
  } catch (e) {
    console.error("Erro ao carregar entidades: ", e)
  } finally {
    loading.value = false
  }
}

const patchUser = async (user: User) => {
  if (!auth.hasPermission('USERS_UPDATE_ANY') && !auth.hasPermission('USERS_UPDATE_OWN')) return

  try {
    const updated = !user.locked

    await api.patchUser(user.id, {
      locked: updated
    })

    const index = users.value.findIndex(u => u.id === user.id)
    if (index !== -1) {
      users.value[index].locked = updated
    }

    toast.add({
      title: updated ? 'Conta ativada' : 'Conta bloqueada',
      description: `${user.name} foi ${updated ? 'ativado(a)' : 'bloqueado(a)'} com sucesso`,
      color: updated ? 'success' : 'warning'
    })
  } catch (e) {
    toast.add({
      title: 'Erro',
      description: 'Não foi possível atualizar o utilizador',
      color: 'error'
    })
  }
}

watch(search, () => {
  page.value = 1
  lastPage.value = Infinity
  users.value = []
  fetch()
})

const scrollContainer = ref<HTMLElement | null>(null)

onMounted(() => {
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
      <div ref="scrollContainer" class="overflow-x-auto max-h-[600px] overflow-y-auto">
        <UTable
          :data="users"
          :columns="columns"
          :loading="loading"
          :ui="{
            base: 'table-auto border-separate border-spacing-0',
            thead: '[&>tr]:bg-elevated/50 [&>tr]:after:content-none',
            tbody: '[&>tr]:last:[&>td]:border-b-0',
            th: 'py-2 first:rounded-l-lg last:rounded-r-lg border-y border-default first:border-l last:border-r',
            td: 'border-b border-default',
            separator: 'h-0'
          }"
          class="w-full"
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
