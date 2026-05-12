<script setup lang="ts">
import { useApiStore } from '@/stores/api'
import type { TableColumn } from '@nuxt/ui'
import { getPaginationRowModel } from '@tanstack/table-core'

const toast = useToast()
const api = useApiStore()

const entityTypes = ref<Types[]>([])
const page = ref(1)
const lastPage = ref<number>(Infinity)
const loading = ref(false)
const total = ref(0)

const search = ref('')

const deleteModalOpen = ref(false)
const selectedEntityTypeById = ref<Types | null>(null)

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
          'data-testid': 'edit-entity-type',
          icon: 'i-lucide-info',
          color: 'info',
          variant: 'ghost',
          onClick: () => {
            navigateTo(`/administration/entityTypes/${row.original.id}`)
          }
        }),
        h(UButton, {
          'data-testid': 'delete-entity-type',
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
    const res = await api.getEntityTypes(params)

    entityTypes.value.push(...res.data.data)
    total.value = res.data.meta.total
    lastPage.value = res.data.meta.last_page
  } catch (e) {
    toast.add({
      title: 'Erro',
      description: 'Erro ao carregar os tipos de entidade',
      color: 'error'
    })
  } finally {
    loading.value = false
  }
}

watch(search, () => {
  page.value = 1
  lastPage.value = Infinity
  entityTypes.value = []
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
      <div ref="scrollContainer" class="overflow-x-auto max-h-[600px] overflow-y-auto">
        <UTable
          :data="entityTypes"
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
      <EntityTypesDeleteModal
        v-if="selectedEntityTypeById"
        v-model:open="deleteModalOpen"
        :id="selectedEntityTypeById?.id"
        :name="selectedEntityTypeById?.name"
        @deleted="fetch"
      />
    </template>
  </UDashboardPanel>
</template>
