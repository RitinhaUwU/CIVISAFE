<script setup lang="ts">
import type {TableColumn} from "@nuxt/ui";
import moment from 'moment/min/moment-with-locales'

const open = ref(false)
moment.locale('pt');

const props = defineProps({
  stockTracker: {
    type: Map<number, { name: string, stock: number, danger_level: number|null, lastUpdated: number }>,
    required: true,
  }
})

const stock = computed(() => {
  return Array.from(props.stockTracker?.values() ?? []).sort((a, b) => {
    return a.name.localeCompare(b.name)
  })
})

const columns: TableColumn<any>[] = [
  {
    accessorKey: 'name',
    header: 'Tipo de Bem'
  },
  {
    accessorKey: 'stock',
    header: 'Stock',
    cell: ({ row }) => {
      if(row.original.danger_level !== null)
      {
        if(row.original.stock <= row.original.danger_level)
        {
          return h('span', {class: 'text-red-500'}, `${row.original.stock} / ${row.original.danger_level}`)
        }
        return `${row.original.stock} / ${row.original.danger_level}`;
      }
      else
      {
        return row.original.stock;
      }
    },
  },
  {
    accessorKey: 'lastUpdated',
    header: 'Última Atualização',
    cell: ({ row }) => {
      return moment(row.original.lastUpdated * 1000).fromNow()
    }
  },
]
</script>

<template>
  <UModal
    v-model:open="open"
    title="Stocks"
    :ui="{ content: 'max-h-[90vh] overflow-y-auto w-full max-w-5xl' }"
  >
    <UButton
      icon="i-lucide-package"
      label="Stocks"
      size="xl"
      class="h-fit p-4"
    />
    <template #body>
      <UTable :columns="columns" :data="stock"/>
    </template>
  </UModal>
</template>
