<script setup lang="ts">
import { useApiStore } from '@/stores/api'
import type { TableColumn } from '@nuxt/ui'
import type { Entity } from '@/types'
import {UButton} from "#components";
import {usePaginatedSelect} from "@/composables/usePaginatedSelect";

const toast = useToast()
const api = useApiStore()

const entities = ref<Entity[]>([])
const page = ref(1)
const lastPage = ref<number>(Infinity)
const loading = ref(false)
const total = ref(0)

const search = ref('')
const typesFilter = ref('all')
const entityTypesMenu = useTemplateRef('entityTypesMenu')

const entityTypes = usePaginatedSelect({
  fetcher: api.getEntityTypes,
  menuRef: entityTypesMenu,
  map: (i: any) => ({
    id: i.id,
    name: i.name
  })
})

const deleteModalOpen = ref(false)
const selectedEntityById = ref<Entity | null>(null)

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
          'data-testid': 'edit-entity',
          icon: 'i-lucide-info',
          color: 'info',
          variant: 'ghost',
          onClick: () => {
            navigateTo(`/administration/entities/${row.original.id}`)
          }
        }),
        //@ts-ignore
        h(UButton, {
          'data-testid': 'delete-entity',
          icon: 'i-lucide-trash',
          color: 'error',
          variant: 'ghost',
          disabled: !useAuthStore().hasPermission('ENTITIES_DELETE'),
          onClick: () => {
            selectedEntityById.value = row.original
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
      per_page: 10,
      filter: {}
    }
    if (search.value) {
      params.filter.search = search.value
    }
    if (typesFilter.value !== 'all') {
      params.filter.type = typesFilter.value
    }
    const res = await api.getEntities(params)

    entities.value.push(...res.data.data)
    total.value = res.data.meta.total
    lastPage.value = res.data.meta.last_page
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

watch([search, typesFilter], () => {
  page.value = 1
  lastPage.value = Infinity
  entities.value = []
  fetch()
})

const scrollContainer = ref<HTMLElement | null>(null)

onMounted(() => {

  if(!useAuthStore().hasPermission('ENTITIES_LIST'))
  {
    useRouter().push('/inicio');
    return;
  }

  fetch()
  entityTypes.fetchItems()

  // ----------
  // Filters
  // ----------
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
          <h2 class="text-lg font-semibold">Entidades</h2>
          <p class="text-sm text-muted max-w-md">Lista de todas as Entidades.</p>
        </div>
        <EntitiesAddModal
          @created="fetch"
          v-if="useAuthStore().hasPermission('ENTITIES_CREATE')"
        />
      </div>
      <div class="flex flex-wrap items-center justify-between gap-1.5">
        <UInput
          v-model="search"
          class="max-w-sm"
          icon="i-lucide-search"
          placeholder="Filtrar entidades..."
        />
        <div class="flex flex-wrap items-center gap-1.5">
          <USelectMenu
            ref="entityTypesMenu"
            v-model="typesFilter"
            v-model:search-term="entityTypes.search.value"
            :items="[
              { id: 'all', name: 'Tipos' },
              ...entityTypes.items.value
            ]"
            :loading="entityTypes.loading.value"
            value-key="id"
            label-key="name"
            ignore-filter
            class="w-48"
            placeholder="Tipos"
          />
        </div>
      </div>
      <div ref="scrollContainer" class="overflow-x-auto max-h-[600px] overflow-y-auto">
        <UTable
          :data="entities"
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
