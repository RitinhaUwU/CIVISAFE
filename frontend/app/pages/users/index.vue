<script setup lang="ts">
import type { TableColumn } from '@nuxt/ui'
import { useAuthStore } from '@/stores/auth'
import { useApiStore } from '@/stores/api'
import type {User} from "@/types"
import {UBadge, UButton, UTooltip} from "#components"
import {extractCursor} from '@/utils'

const api = useApiStore()
const auth = useAuthStore()
const toast = useToast()

const users = ref<User[]>([])
const nextCursor = ref<string | null>(null)
const loading = ref(false)

const search = ref('')

const deleteModalOpen = ref(false)
const selectedUserById = ref<User | null>(null)

const roleLabels = {
  admin: {
    label: 'Administrador',
    color: 'error'
  },
  user: {
    label: 'Utilizador',
    color: 'warning'
  },
  module_donations: {
    label: 'Acesso: Doações',
    color: 'success'
  },
  module_incidents: {
    label: 'Acesso: Ocorrências',
    color: 'success'
  },
  module_volunteers: {
    label: 'Acesso: Voluntários',
    color: 'success'
  }
}

const columns: TableColumn<User>[] = [
  {
    accessorFn: (row) => `#${row.id}`,
    header: "ID",
  },
  {
    accessorKey: "name",
    header: "Nome",
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
          const label = roleLabels[role] || {label: role, color: 'neutral'}

          return h(UBadge, {class: 'capitalize rounded-full', variant: 'subtle', color: label.color}, () => label.label)
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
              color: isLocked ? 'success' : 'error',
              onClick: () => patchUser(row.original),
              disabled: row.original.id === auth.currentUserID
            })
          }
        ),
        h(UButton, {
          'data-testid': 'edit-user',
          icon: 'i-lucide-info',
          color: 'info',
          variant: 'ghost',
          disabled: !(auth.hasPermission('USERS_VIEW_ANY') || (row.original.id === auth.currentUserID && auth.hasPermission('USERS_VIEW_OWN'))
          ),
          onClick: () => navigateTo(`/users/${row.original.id}`)
        }),
        h(UButton, {
          'data-testid': 'delete-user',
          icon: 'i-lucide-trash',
          color: 'error',
          variant: 'ghost',
          disabled: !auth.hasPermission('USERS_DELETE') || auth.currentUserID === row.original.id,
          onClick: () => {
            selectedUserById.value = row.original
            deleteModalOpen.value = true
          }
        })
      ].filter(Boolean))
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
    const res = await api.getUsers(params)

    const newUsers = res.data.data

    if (loadMore) {
      const existing = new Set(users.value.map(u => u.id))
      users.value.push(...newUsers.filter(u => !existing.has(u.id)))
    } else {
      users.value = newUsers
    }

    nextCursor.value = res.data.meta?.next_cursor
  } finally {
    loading.value = false
  }
}

const patchUser = async (user: User) => {
  if(user.id === auth.currentUserID && !auth.hasPermission('USERS_UPDATE_OWN')) return

  if(user.id !== auth.currentUserID && !auth.hasPermission('USERS_UPDATE_ANY')) return

  if (!await checkServerAccess()) {
    toast.add({
      title: 'Sem ligação à internet!',
      description: 'Não é possível guardar alterações sem estar ligado à internet. Tente novamente mais tarde',
      color: 'error'
    });
    return;
  }

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
      title: updated ? 'Conta bloqueada' : 'Conta ativada',
      description: `${user.name} foi ${updated ? 'bloqueado(a)' : 'ativado(a)'} com sucesso`,
      color: updated ? 'warning' : 'success'
    })
  } catch (e) {
    toast.add({
      title: 'Erro',
      description: 'Não foi possível atualizar o utilizador',
      color: 'error'
    })
  }
}

watchDebounced(search, async () => {
  nextCursor.value = null
  users.value = []
  await fetch(false)
}, {debounce: 300})

const scrollContainer = ref<HTMLElement | null>(null)

onMounted(() => {
  if(!auth.hasPermission('USERS_VIEW_ANY'))
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
      canLoadMore: () => !loading.value && nextCursor.value != null
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
          <UsersAddModal @created="fetch" v-if="auth.hasPermission('USERS_CREATE')" />
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
      <div ref="scrollContainer" class="overflow-x-auto max-h-[80vh] overflow-y-auto">
        <UTable
          v-if="loading || users.length > 0"
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
        <div v-else class="flex items-center justify-center py-12 text-center text-muted">
          Nenhum registo de utilizador encontrado.
        </div>
      </div>
      <UsersDeleteModal
        v-if="auth.hasPermission('USERS_DELETE') && selectedUserById"
        v-model:open="deleteModalOpen"
        :id="selectedUserById?.id"
        :name="selectedUserById?.name"
        @deleted="fetch"
      />
    </template>
  </UDashboardPanel>
</template>
