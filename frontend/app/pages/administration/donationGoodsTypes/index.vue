<script setup lang="ts">
import { useApiStore } from '@/stores/api'
import type { TableColumn } from '@nuxt/ui'
import type {DonationGoodType} from "@/types";
import {UBadge, UButton} from "#components";
import {convertedMeasurementUnit} from "@/utils";

const toast = useToast()
const api = useApiStore()

const tiposBens = ref<DonationGoodType[]>([])
const nextCursor = ref<string|null>(null)
const loading = ref(false)

const search = ref('')

const deleteModalOpen = ref(false)
const selectedGoodById = ref<DonationGoodType | null>(null)

const columns: TableColumn<DonationGoodType>[] = [
  {
    accessorKey: "name",
    header: "Nome",
    cell: ({ row }) => {
      return h(
        'div',
        { class: 'flex items-center gap-2' },
        [
          h('span', { class: 'font-medium' }, row.original.name)
        ]
      )
    }
  },
  {
    accessorKey: 'unit',
    header: () => h('div', { class: 'text-center w-full' }, 'Unidade de Medida'),
    cell: ({ row }) => {

      if(row.original.is_type_countable)
      {
        return h('div', { class: 'flex justify-center'}, convertedMeasurementUnit(row.original.unit));
      }
      else
      {
        return h(
          'div',
          { class: 'flex justify-center' },
          h(UBadge, { class: 'rounded-full', variant: 'subtle'}, () => "Não Aplicável")
        )
      }
    }
  },
  {
    accessorKey: "danger_level",
    header: () => h('div', { class: 'text-center w-full' }, 'Quantidade Crítica'),
    cell: ({ row }) => {
      if(row.original.danger_level)
      {
        return h('div', { class: 'flex justify-center'}, row.original.danger_level + " " + convertedMeasurementUnit(row.original.unit));
      }
      else
      {
        return h(
          'div',
          { class: 'flex justify-center' },
          h(UBadge, { class: 'rounded-full', variant: 'subtle'}, () => "Não Configurado")
        )
      }
    }
  },
  {
    id: 'actions',
    cell: ({ row }) => {
      return h(
        'div',
        { class: 'text-right' },
        [
          h(UButton, {
            'data-testid': 'edit-priority',
            icon: 'i-lucide-info',
            color: 'info',
            variant: 'ghost',
            onClick: () => {
              navigateTo(`/administration/donationGoodsTypes/${row.original.id}`)
            }
          }),
          h(UButton, {
            'data-testid': 'delete-priority',
            icon: 'i-lucide-trash',
            color: 'error',
            variant: 'ghost',
            disabled: !useAuthStore().hasPermission('DONATION_GOODS_TYPES_DELETE'),
            onClick: () => {
              selectedGoodById.value = row.original
              deleteModalOpen.value = true
            }
          })
        ]
      )
    }
  }
]

const fetch = async (loadMore: boolean = false) => {
  if (loading.value) return

  loading.value = true

  try {
    const params: any = {
      per_page: 10,
      ...(loadMore && nextCursor.value ? { cursor: nextCursor.value } : {})
    }

    if (search.value) {
      params.filter = { search: search.value }
    }

    const res = await api.getDonationGoodTypes(params)
    const newGoodTypes = res.data.data

    if (loadMore) {
      const existing = new Set(tiposBens.value.map(type => type.id))
      const filtered = newGoodTypes.filter(type => !existing.has(type.id))

      tiposBens.value.push(...filtered)
    } else {
      tiposBens.value = newGoodTypes
    }

    nextCursor.value = res.data.meta.next_cursor
  } catch (e) {
    toast.add({
      title: 'Erro',
      description: 'Erro ao carregar os Tipos de Bens.',
      color: 'error'
    })
  } finally {
    loading.value = false
  }
}

watchDebounced([search], async () => {
  nextCursor.value = null
  tiposBens.value = []
  await fetch(false);
}, {debounce: 300})

const postDelete = (id: number) => {
  const idx = tiposBens.value.findIndex(x => x.id === id);
  tiposBens.value.splice(idx, 1);
}

const scrollContainer = ref<HTMLElement | null>(null)

onMounted(async () => {

  if(!useAuthStore().hasPermission('DONATION_GOODS_TYPES_LIST')){
    await useRouter().push('/inicio');
    return;
  }

  await fetch()

  useInfiniteScroll(
    scrollContainer,
    () => {
      if(nextCursor.value == null) return;
      fetch(true)
    },
    {
      distance: 200,
      canLoadMore: () => !loading.value && nextCursor.value !== null
    }
  )
})
</script>

<template>
  <UDashboardPanel id="prioridades-ocorrencias">
    <template #body>
      <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3 mb-4">
        <div>
          <h2 class="text-lg font-semibold">Tipos de Bens Doáveis</h2>
          <p class="text-sm text-muted max-w-md">Lista de todos os Tipos de Bens Doáveis disponíveis no módulo de Doações.</p>
        </div>
        <DonationGoodTypesAddModal
          @created="fetch"
          v-if="useAuthStore().hasPermission('DONATION_GOODS_TYPES_CREATE')"
        />
      </div>
      <div class="flex flex-wrap items-center justify-between gap-1.5">
        <UInput
          v-model="search"
          class="max-w-sm"
          icon="i-lucide-search"
          placeholder="Filtrar Tipos de Bens..."
        />
      </div>
      <div ref="scrollContainer" class="overflow-x-auto max-h-[70vh] overflow-y-auto">
        <UTable
          v-if="loading || tiposBens.length > 0"
          :data="tiposBens"
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
          Nenhum registo de tipos de bens encontrado.
        </div>
      </div>
      <DonationGoodTypesDeleteModal
        v-if="selectedGoodById"
        v-model:open="deleteModalOpen"
        :id="selectedGoodById?.id"
        :name="selectedGoodById?.name"
        @deleted="postDelete"
      />
    </template>
  </UDashboardPanel>
</template>
