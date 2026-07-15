<script setup lang="ts">
import { useApiStore } from '@/stores/api'
import type { TableColumn } from '@nuxt/ui'
import type {Volunteer} from '@/types'
import {UBadge, UButton} from '#components'
import {extractCursor} from '@/utils'

const api = useApiStore()
const toast = useToast()

const volunteers = ref<Volunteer[]>([])
const nextCursor = ref<string | null>(null)
const loading = ref(false)

const search = ref('')
const accommodationFilter = ref('all')
const mealFilter = ref('all')
const classificationFilter = ref('all')

const deleteModalOpen = ref(false)
const selectedVolunteerById = ref<Volunteer | null>(null)

const classificationMap: Record<string, string> = {
  single: 'Individual',
  org: 'Organização',
  misc: 'Outro'
}

const columns: TableColumn<Volunteer>[] = [
  {
    accessorFn: (row) => `#${row.id}`,
    header: "ID",
  },
  {
    accessorKey: "team_identification",
    header: "Equipa",
    cell: ({ row }) => {
      return h(
        'div',
        { class: 'max-w-[180px] truncate' },
        row.original.team_identification
      )
    }
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
    accessorKey: "contact",
    header: "Contacto",
  },
  {
    accessorKey: "classification",
    header: "Tipo de Equipas",
    cell: ({ row }) => {
      return h('div', { class: 'flex flex-col' }, [
        h('span', { class: 'font-medium text-highlighted' }, classificationMap[row.original.classification]),
        h('span', { class: 'text-xs text-muted' }, `${ row.original.num_elements } elemento(s)`),
      ])
    }
  },
  {
    accessorKey: "has_accommodation",
    header: () => h('div', { class: 'text-center w-full' }, 'Alojamento'),
    meta: { class: 'text-center' },
    cell: ({ row }) => {
      const value = row.original.has_accommodation

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
    accessorKey: "has_meal",
    header: () => h('div', { class: 'text-center w-full' }, 'Refeição'),
    meta: { class: 'text-center' },
    cell: ({ row }) => {
      const value = row.original.has_meal

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
    id: 'actions',
    cell: ({ row }) => {
      return h(
        'div',
        { class: 'text-right' },
        h(UButton, {
          'data-testid': 'edit-volunteer',
          icon: 'i-lucide-info',
          color: 'info',
          variant: 'ghost',
          onClick: () => {
            navigateTo(`/volunteers/${row.original.id}`)
          }
        }),
        //@ts-ignore
        h(UButton, {
          'data-testid': 'delete-volunteer',
          icon: 'i-lucide-trash',
          color: 'error',
          variant: 'ghost',
          disabled: !useAuthStore().hasPermission('VOLUNTEERS_DELETE'),
          onClick: () => {
            selectedVolunteerById.value = row.original
            deleteModalOpen.value = true
          }
        })
      )
    }
  }
]

const fetch = async(loadMore = false) => {
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
    if (classificationFilter.value !== 'all') {
      params.filter.classification = classificationFilter.value
    }
    if (accommodationFilter.value !== 'all') {
      params.filter.has_accommodation = accommodationFilter.value
    }
    if (mealFilter.value !== 'all') {
      params.filter.has_meal = mealFilter.value
    }
    const res = await api.getVolunteers(params)

    const newVolunteers = res.data.data

    if (loadMore) {
      const existing = new Set(volunteers.value.map(v => v.id))
      volunteers.value.push(...newVolunteers.filter(v => !existing.has(v.id)))
    } else {
      volunteers.value = newVolunteers
    }

    nextCursor.value = extractCursor(res.data.links?.next)
  } catch (e) {
    toast.add({
      title: 'Erro',
      description: 'Não foi possível atualizar o voluntário',
      color: 'error'
    })
  } finally {
    loading.value = false
  }
}

watchDebounced([search, accommodationFilter, mealFilter, classificationFilter], async () => {
  nextCursor.value = null
  volunteers.value = []
  await fetch(false)
}, {debounce: 300})

const scrollContainer = ref<HTMLElement | null>(null)

onMounted(async () => {

  if(!useAuthStore().hasPermission('VOLUNTEERS_LIST'))
  {
    await useRouter().push('/inicio');
    return;
  }

  await fetch()

  useInfiniteScroll(
    scrollContainer,
    () => {
      if (!nextCursor.value) return
      fetch(true)
    },
    {
      distance: 200,
      canLoadMore: () => !loading.value && !!nextCursor.value
    }
  )
})
</script>

<template>
  <UDashboardPanel id="voluntario">
    <template #header>
      <UDashboardNavbar title="Voluntários">
        <template #leading>
          <UDashboardSidebarCollapse />
        </template>
        <template #right>
          <VolunteersAddModal
            @created="fetch"
            v-if="useAuthStore().hasPermission('VOLUNTEERS_CREATE')"
          />
        </template>
      </UDashboardNavbar>
    </template>
    <template #body>
      <div class="flex flex-wrap items-center justify-between gap-1.5">
        <UInput
          v-model="search"
          class="max-w-sm"
          icon="i-lucide-search"
          placeholder="Filtrar voluntários..."
        />
        <div class="flex flex-wrap items-center gap-1.5">
          <USelect
            v-model="classificationFilter"
            :items="[
              { label: 'Tipo de Equipa', value: 'all' },
              { label: 'Individual', value: 'single' },
              { label: 'Organização', value: 'org' },
              { label: 'Outro', value: 'misc' }
            ]"
          />
          <USelect
            v-model="accommodationFilter"
            :items="[
              { label: 'Alojamento', value: 'all' },
              { label: 'Sim', value: true },
              { label: 'Não', value: false }
            ]"
          />
          <USelect
            v-model="mealFilter"
            :items="[
              { label: 'Refeição', value: 'all' },
              { label: 'Sim', value: true },
              { label: 'Não', value: false }
            ]"
          />
        </div>
      </div>
      <div ref="scrollContainer" class="overflow-x-auto max-h-[80vh] overflow-y-auto">
        <UTable
          v-if="loading || volunteers.length > 0"
          :data="volunteers"
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
        <div v-else class="flex items-center justify-center py-12 text-center text-muted">
          Nenhum registo de voluntário encontrado.
        </div>
      </div>
      <VolunteersDeleteModal
        v-if="selectedVolunteerById"
        v-model:open="deleteModalOpen"
        :id="selectedVolunteerById?.id"
        :team_identification="selectedVolunteerById?.team_identification"
        @deleted="fetch"
      />
    </template>
  </UDashboardPanel>
</template>
