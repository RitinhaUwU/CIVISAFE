<script setup lang="ts">
import { useApiStore } from '@/stores/api'
import type { TableColumn } from '@nuxt/ui'
import type {DonationGoodType} from "@/types";
import {UBadge, UButton} from "#components";
import {convertedMeasurementUnit} from "@/utils";

const toast = useToast()
const api = useApiStore()

const tiposBens = ref<DonationGoodType[]>([])
const page = ref(1)
const lastPage = ref<number>(Infinity)
const loading = ref(false)
const total = ref(0)

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

const fetch = async () => {
  if (loading.value) return
  if (page.value > lastPage.value) return

  loading.value = true
  try {
    const params: any = {
      page: page.value,
      per_page: 15
    }

    if (search.value) {
      params.filter = { search: search.value }
    }

    const res = await api.getDonationGoodTypes(params)

    tiposBens.value.push(...res.data.data)
    total.value = res.data.meta.total
    lastPage.value = res.data.meta.last_page
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

watch([search], () => {
  page.value = 1
  lastPage.value = Infinity
  tiposBens.value = []
  fetch();
})

const postDelete = (id: number) => {
  const idx = tiposBens.value.findIndex(x => x.id === id);
  tiposBens.value.splice(idx, 1);
}

const scrollContainer = ref<HTMLElement | null>(null)

onMounted(() => {

  if(!useAuthStore().hasPermission('DONATION_GOODS_TYPES_LIST')){
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
      <div ref="scrollContainer" class="overflow-x-auto max-h-[600px] overflow-y-auto">
        <UTable
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
