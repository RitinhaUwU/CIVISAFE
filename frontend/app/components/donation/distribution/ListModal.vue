<script setup lang="ts">
import type {TableColumn} from "@nuxt/ui";
import moment from 'moment/min/moment-with-locales'
import {UButton} from "#components";
import type {DonationDistribution} from "@/types";

const open = ref(false)
const historyModalOpen = ref(false)
const selectedRowForHistoryModal = ref<DonationDistribution>();
moment.locale('pt');

const emit = defineEmits<{
  selected: [record: DonationDistribution]
}>()

const columns: TableColumn<any>[] = [
  {
    accessorKey: 'created_at',
    header: 'Data',
    cell: ({ row }) => {
      return moment(row.original.created_at).format('YYYY-MM-DD HH:mm')
    }
  },
  {
    accessorKey: 'name',
    header: 'Nome'
  },
  {
    accessorKey: 'contact',
    header: 'Contacto'
  },
  {
    header: 'Número de Bens',
    cell: ({row}) => {
      return row.original.goods.length;
    }
  },
  {
    id: 'actions',
    cell: ({row}) => {
      return h('div', {class: 'text-right flex gap-1 justify-end'}, [
        (useAuthStore().hasPermission('DONATION_LOG_UPDATE')) &&
        h(UButton, {
          icon: 'i-lucide-pencil',
          color: 'warning',
          variant: 'ghost',
          onClick: () => {
            emit('selected', <DonationDistribution>row.original);
            open.value = false;
          }
        }),
        h(UButton, {
          icon: 'i-lucide-clock',
          color: 'info',
          variant: 'ghost',
          onClick: () => {
            selectedRowForHistoryModal.value = <DonationDistribution>row.original;
            historyModalOpen.value = true;
          }
        })
      ])
    }
  },
]

const distributions = ref<DonationDistribution[]>([])
const nextCursor = ref<string | null>(null)
const loading = ref(false)

const fetch = async(loadMore: boolean = false) => {
  if (loading.value) return

  loading.value = true
  try {
    const params: any = {
      per_page: 10,
      ...(loadMore && nextCursor.value ? { cursor: nextCursor.value } : {})
    }
    const res = await useApiStore().getAllDistributions(params);

    if(loadMore)
    {
      const existing = new Set(distributions.value.map(dist => dist.id))
      distributions.value.push(...res.data.data.filter(dist => !existing.has(dist.id)))
    }
    else
    {
      distributions.value = res.data.data
    }

    nextCursor.value = res.data.meta.next_cursor
  } catch (e) {
    console.error("Erro ao carregar distribuições de bens: ", e)
  } finally {
    loading.value = false
  }
}

const scrollContainer = ref<HTMLElement | null>(null)

const handleUpdateToDistribution = (event: {resource: any}) => {
  const idx = distributions.value.findLastIndex(dist => dist.id === event.resource.id);

  if(idx !== -1) {
    // Update event
    distributions.value[idx] = event.resource
  }
  else
  {
    distributions.value.push(event.resource)
    distributions.value.sort((a, b) => b.id - a.id)
  }
}

onMounted(() => {
  const {$echo} = useNuxtApp();

  $echo.private('DonationStocks')
    .listen('.distribution.created', handleUpdateToDistribution)
    .listen('.distribution.updated', handleUpdateToDistribution);

  fetch(false)

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
  <UModal
    v-model:open="open"
    title="Lista de Entregas Realizadas"
    :ui="{ content: 'max-h-[90vh] overflow-y-auto w-full max-w-5xl' }"
  >
    <UButton
      icon="i-lucide-list"
      label="Lista de Entregas"
      size="xl"
      class="h-fit p-4"
    />
    <template #body>
      <div ref="scrollContainer" class="overflow-x-auto max-h-150 overflow-y-auto">
        <UTable :columns="columns" :data="distributions"/>
      </div>
      <DonationAuditLogModal
        v-model:open="historyModalOpen"
        v-model:selectedRow="selectedRowForHistoryModal"
      />
    </template>
  </UModal>
</template>

