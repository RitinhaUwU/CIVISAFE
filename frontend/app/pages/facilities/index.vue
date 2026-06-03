<script setup lang="ts">
import { useApiStore } from '@/stores/api'
import type { TableColumn } from '@nuxt/ui'
import { getPaginationRowModel } from '@tanstack/table-core'
import {UButton} from "#components";
import type {Facilities} from "~/types";

const toast = useToast()
const api = useApiStore()

const facilities = ref<Facilities[]>([])
const page = ref(1)
const lastPage = ref<number>(Infinity)
const loading = ref(false)
const total = ref(0)

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
          icon: 'i-lucide-files',
          color: 'warning',
          variant: 'ghost',
          onClick: () => {
            navigateTo(`/facilities/${row.original.id}/files`)
          }
        }),
        //@ts-ignore
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
      params.filter = {
        search: search.value
      }
    }
    const res = await api.getFacilities(params)

    facilities.value.push(...res.data.data)
    total.value = res.data.meta.total
    lastPage.value = res.data.meta.last_page
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

watch(search, () => {
  page.value = 1
  lastPage.value = Infinity
  facilities.value = []
  fetch()
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
