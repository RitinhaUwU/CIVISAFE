<script setup lang="ts">
import type {AuditLog, DonationGoodType} from "@/types";
import moment from 'moment/min/moment-with-locales'

const historyModalOpen = defineModel('open');
const selectedRowForHistoryModal = defineModel('selectedRow');
const categoryLookup = new Map<number, DonationGoodType>();
const timelineItems = ref<AuditLog[]>([]);
const auditLoading = ref(false);
const historyScrollContainer = ref<HTMLElement | null>(null)
const nextCursor = ref<string | null>(null)

const fetchCategories = async () => {
  try {
    const res = await useApiStore().getAllDonationGoodTypes(true)
    res.data.data.forEach((type: DonationGoodType) => {
      categoryLookup.set(type.id, type);
    })
  }catch (e) {
    useToast().add({
      title: 'Erro ao Carregar Categorias',
    })
  }
}

const actionIcon = (log: AuditLog) => {
  const icons: Record<string, string> = {
    'created': 'i-lucide-plus',
    'updated': 'i-lucide-square-pen',
    'deleted': 'i-lucide-trash-2',
  }
  return icons[log.action] ?? 'i-lucide-message-circle'
}

const fetchAuditLogs = async (loadMore: boolean) => {
  auditLoading.value = true;
  try
  {
    const params: any = {
      per_page: 10,
      ...(loadMore && nextCursor.value ? {cursor: nextCursor.value} : {})
    }

    const res = await useApiStore().getDistributionAudit(selectedRowForHistoryModal.value?.id, params);

    const newLogs = res.data.data.map((log: AuditLog) => ({
      ...log,
      icon: actionIcon(log)
    }))

    if (loadMore) {
      const existing = new Set(timelineItems.value.map(u => u.id))
      timelineItems.value.push(...newLogs.filter((u: AuditLog) => !existing.has(u.id)))
    } else {
      timelineItems.value = newLogs
    }

    nextCursor.value = res.data.next_cursor
  }
  catch (e) {
    useToast().add({
      title: 'Erro o Registo de Auditoria',
      description: 'Erro ao carregar o Registo de Auditoria para a Entrega.',
      color: 'error'
    });
    console.error(e);
  }
  finally {
    auditLoading.value = false;
  }
}

watch(historyModalOpen, async () => {
  if(historyModalOpen.value === true)
  {
    await fetchAuditLogs(false);

    useInfiniteScroll(
      historyScrollContainer,
      () => {
        if (!nextCursor.value) return
        fetchAuditLogs(true)
      },
      {
        distance: 200,
        canLoadMore: () => {
          return nextCursor.value != null
        }
      }
    )
  }
  else
  {
    timelineItems.value = [];
  }
});

const formatLogDiffValues = (key: string, value: any = undefined) => {
  if (value === null || value === undefined || value === '') {
    return '-'
  }

  if(key == "donation_goods_types_id" || key == "donation_goods_type_id")
  {
    const category = categoryLookup.get(value)
    if (category) {
      return `${category.name} (#${value})`
    }
  }

  return value
}

const readableKey = (key: string) => {
  const keys: Record<string, string> = {
    'quantity': 'Quantidade',
    'name': 'Nome',
    'contact': 'Contacto',
    'donation_distribution_id': 'ID Entrega',
    'donation_goods_type_id': 'ID Bem',
    'obs': 'Observações'
  }

  return keys[key] ?? key;
}

onMounted(async () => {
  await fetchCategories();
})
</script>

<template>
  <UModal
    v-model:open="historyModalOpen"
    :title="`Histórico da entrega a ${selectedRowForHistoryModal?.name} em ${new Date(selectedRowForHistoryModal?.created_at).toLocaleDateString()}`"
    :ui="{ content: 'max-h-[90vh] overflow-y-auto w-full max-w-5xl' }"
  >
    <template #body>

      <UEmpty
        v-if="timelineItems.length == 0 && !auditLoading"
        icon="i-lucide-scroll-text"
        title="Sem Registos de Auditoria a Mostrar"
        description="Quando alguma atividade que envolva o módulo de doações acontecer, ela irá aparecer aqui"
        variant="naked"
      />

      <div v-else ref="historyScrollContainer" class="overflow-x-auto max-h-dvh overflow-y-auto">
        <UTimeline :items="timelineItems" size="xl" :ui="{ date: 'float-end ms-1' }">
          <template #title="{ item }">
            <div class="flex flex-col gap-2">
              <div class="flex items-center justify-between gap-2">
                <div>
                  <span class="font-semibold">{{ item.user }}</span>
                  <span class="font-normal text-muted">&nbsp;{{ item.action }} {{ item.type }}</span>
                </div>
              </div>
              <div v-if="Object.keys(item.changes ?? {}).length">
                <UAccordion
                  :items="[{
                        label: 'Ver alterações',
                      }]"
                  class="text-sm text-stone-400 shrink-0 dark:text-stone-300"
                  :ui="{
                        trigger: 'px-3 py-2 ring ring-default rounded-md bg-default/30',
                        content: 'px-3 py-2'
                      }"
                >
                  <template #content>
                    <div class="space-y-1 text-xs px-3 py-2">
                      <div v-for="(value, key) in item.changes" :key="key" class="flex gap-2 flex-wrap">
                        <span class="text-stone-400 shrink-0">{{ readableKey(key) }}:</span>
                        <template v-if="key in (item.old_values ?? {})">
                          <span class="line-through text-red-400">{{
                              formatLogDiffValues(key, item.old_values?.[key])
                            }}</span>
                          <UIcon name="i-lucide-move-right"/>
                        </template>
                        <span class="text-green-500">{{ formatLogDiffValues(key, value) }}</span>
                      </div>
                    </div>
                  </template>
                </UAccordion>
              </div>
            </div>
          </template>
          <template #date="{ item }">{{ moment(item.date).fromNow() }}</template>
        </UTimeline>

        <div v-show="auditLoading" class="flex justify-center py-10">
          <UIcon name="i-lucide-loader-circle" class="animate-spin text-stone-400 size-6" />
        </div>

      </div>
    </template>
  </UModal>
</template>

<style scoped>

</style>
