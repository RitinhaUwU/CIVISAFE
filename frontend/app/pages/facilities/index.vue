<script setup lang="ts">
import { useApiStore } from '@/stores/api'
import type { TableColumn } from '@nuxt/ui'
import {UButton} from '#components'
import type {Facilities} from '@/types'
import {extractCursor} from '@/utils'

const toast = useToast()
const api = useApiStore()

const facilities = ref<Facilities[]>([])
const nextCursor = ref<string | null>(null)
const loading = ref(false)

const search = ref('')

const deleteModalOpen = ref(false)
const selectedFacilitiesById = ref<Facilities | null>(null)

const columns: TableColumn<Facilities | null>[] = [
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
          disabled: !useAuthStore().hasPermission('FACILITIES_DELETE'),
          onClick: () => {
            selectedFacilitiesById.value = row.original
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
    const res = await api.getFacilities(params)

    const newFacilities = res.data.data

    if (loadMore) {
      const existing = new Set(facilities.value.map(f => f.id))
      facilities.value.push(...newFacilities.filter(f => !existing.has(f.id)))
    } else {
      facilities.value = newFacilities
    }

    nextCursor.value = extractCursor(res.data.links?.next)
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

watch(search, async () => {
  nextCursor.value = null
  facilities.value = []
  await fetch(false)
})

const scrollContainer = ref<HTMLElement | null>(null)

onMounted(() => {

  if(!useAuthStore().hasPermission('FACILITIES_LIST')){
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
      canLoadMore: () => !loading.value && !!nextCursor.value
    }
  )
})
</script>

<template>
  <UDashboardPanel id="instalações">
    <template #header>
      <UDashboardNavbar title="Instalações">
        <template #leading>
          <UDashboardSidebarCollapse />
        </template>
        <template #right>
          <FacilitiesAddModal @created="fetch" v-if="useAuthStore().hasPermission('FACILITIES_CREATE')" />
        </template>
      </UDashboardNavbar>
    </template>
    <template #body>
      <div class="flex flex-wrap items-center justify-between gap-1.5">
        <UInput
          v-model="search"
          class="max-w-sm"
          icon="i-lucide-search"
          placeholder="Filtrar instalações..."
        />
      </div>
      <div ref="scrollContainer" class="overflow-x-auto max-h-[600px] overflow-y-auto">
        <UTable
          :data="facilities"
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
