<script setup lang="ts">
import { useApiStore } from '@/stores/api'
import type { TableColumn } from '@nuxt/ui'
import type { EntityType} from "@/types";
import {UButton} from "#components";

const toast = useToast()
const api = useApiStore()

const entityTypes = ref<EntityType[]>([])
const nextCursor = ref<string | null>(null)
const loading = ref(false)

const search = ref('')

const deleteModalOpen = ref(false)
const selectedEntityTypeById = ref<EntityType | null>(null)

const columns: TableColumn<EntityType>[] = [
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
        //@ts-ignore
        h(UButton, {
          'data-testid': 'delete-entity-type',
          icon: 'i-lucide-trash',
          color: 'error',
          variant: 'ghost',
          disabled: !useAuthStore().hasPermission('ENTITY_TYPES_DELETE'),
          onClick: () => {
            selectedEntityTypeById.value = row.original
            deleteModalOpen.value = true
          }
        })
      )
    }
  }
]

const extractCursor = (url: string | null) => {
  if (!url) return null
  try {
    return new URL(url).searchParams.get('cursor')
  } catch {
    return null
  }
}

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
    const res = await api.getEntityTypes(params)

    const newEntetyTypes = res.data.data

    if (loadMore) {
      const existing = new Set(entityTypes.value.map(et => et.id))
      entityTypes.value.push(...newEntetyTypes.filter(et => !existing.has(et.id)))
    } else {
      entityTypes.value = newEntetyTypes
    }

    nextCursor.value = extractCursor(res.data.links?.next)
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

watch(search, async () => {
  nextCursor.value = null
  entityTypes.value = []
  await fetch(false)
})

const scrollContainer = ref<HTMLElement | null>(null)

onMounted(() => {

  if(!useAuthStore().hasPermission('ENTITY_TYPES_LIST'))
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
      canLoadMore: () => !loading.value && !!nextCursor.value
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
        <EntityTypesAddModal
          @created="fetch"
          v-if="useAuthStore().hasPermission('ENTITY_TYPES_CREATE')"
        />
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
