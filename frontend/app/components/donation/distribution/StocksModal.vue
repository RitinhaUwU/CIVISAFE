<script setup lang="ts">
import type {TableColumn} from "@nuxt/ui";
import moment from 'moment/min/moment-with-locales'
import UButton from "@nuxt/ui/components/Button.vue";
import {useApiStore} from "~/stores/api";
import {useToast} from "@nuxt/ui/composables";

const open = ref(false)
moment.locale('pt');
const localSwitchStatus = ref(false)

const props = defineProps({
  stockTracker: {
    type: Map<number, { name: string, stock: number, danger_level: number|null, lastUpdated: number }>,
    required: true,
  }
})

const stock_unlocked = defineModel('stockUnlocked', {
  type: Boolean,
  required: true,
})

const handleStatusSwitch = async () => {
  if (!useAuthStore().hasPermission('SETTING_DONATION_DISTRIBUTION_STOCK_UNLOCK')) {
    return;
  }

  if (!await checkServerAccess()) {
    useToast().add({
      title: 'Sem ligação à internet!',
      description: 'Não é possível guardar alterações sem estar ligado à internet. Tente novamente mais tarde',
      color: 'error'
    });
    localSwitchStatus.value = !localSwitchStatus.value;
    return;
  }

  try
  {
    await useApiStore().updateStockUnlock(!localSwitchStatus.value);

    useToast().add({
      title: "Sucesso!",
      description: "Stock " + (stock_unlocked.value ? 'desbloqueado' : 'bloqueado') + " com sucesso!",
      color: "success",
    });
  }
  catch (error) {
    useToast().add({
      title: "Erro ao alterar o estado do bloqueio do stock",
      description: "Ocorreu um erro ao alterar o estado do bloqueio do stock.",
      color: "error",
    });
    console.error(error);
  }
}

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

watch(stock_unlocked, () => {
  localSwitchStatus.value = !stock_unlocked.value;
})
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

      <USwitch
        v-model="localSwitchStatus"
        v-if="useAuthStore().hasPermission('SETTING_DONATION_DISTRIBUTION_STOCK_UNLOCK')"
        class="justify-end"
        label="Desbloquear Stock (Remove Limitação de Stock)"
        @click="handleStatusSwitch"
      />

      <UTable :columns="columns" :data="stock"/>
    </template>
  </UModal>
</template>
