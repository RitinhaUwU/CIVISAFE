<script setup lang="ts">
import moment from 'moment/min/moment-with-locales'
import type {DonationGoodType, AuditLog} from "@/types";

const api = useApiStore()
const auth = useAuthStore()
const toast = useToast()

const logs = ref<AuditLog[]>([])
const nextCursor = ref<string | null>(null)
const loading = ref<boolean>(false);
const categoryLookup = new Map<number, DonationGoodType>();

const fetch = async (loadMore = false) => {
  loading.value = true
  try {
    const params: any = {
      per_page: 10,
      ...(loadMore && nextCursor.value ? {cursor: nextCursor.value} : {})
    }
    const res = await api.getAllDonationsAudit(params)

    const newLogs = res.data.data.map((log: AuditLog) => ({
      ...log,
      icon: actionIcon(log)
    }))

    if (loadMore) {
      const existing = new Set(logs.value.map(u => u.id))
      logs.value.push(...newLogs.filter((u: AuditLog) => !existing.has(u.id)))
    } else {
      logs.value = newLogs
    }

    nextCursor.value = res.data.next_cursor
  }
  catch (e) {
    toast.add({
      title: 'Erro ao Carregar Registo de Auditoria',
      color: 'error',
    })
    console.error(e)
  }
  finally {
    loading.value = false
  }
}

const fetchCategories = async () => {
  try {
    const res = await api.getAllDonationGoodTypes(true)
    res.data.data.forEach((type: DonationGoodType) => {
      categoryLookup.set(type.id, type);
    })
  }catch (e) {
    toast.add({
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

const readableKey = (key: string) => {
  const keys: Record<string, string> = {
    'donation_log_id': 'ID Doação',
    'donation_goods_types_id': 'ID Bem',
    'quantity': 'Quantidade',
    'date': 'Data',
    'name': 'Nome',
    'contact': 'Contacto',
    'email': 'Email',
    'donor_type': 'Tipo de Doador',
    'donation_distribution_id': 'ID Entrega',
    'donation_goods_type_id': 'ID Bem',
    'obs': 'Observações',
    'reason': 'Motivo',
    'adjustment_type': 'Tipo de Ajuste'
  }

  return keys[key] ?? key;
}

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

  if(key == "adjustment_type")
  {
    if(value == "add")
    {
      return "Adicionou Stock"
    }
    else
    {
      return "Removeu Stock"
    }
  }

  if(key == "reason")
  {
    switch (value)
    {
      case "diffCorrection":
        return "Correção de Diferença"

      case "brokenItem":
        return "Item Danificado"

      case "lost":
        return "Perda/Roubo"

      case "other":
        return "Outro"
    }
  }

  return value
}

const scrollContainer = ref<HTMLElement | null>(null)

onMounted(async () => {
  if (!await checkServerAccess()) {
    useToast().add({
      title: "O Módulo de Doações só está disponível Online",
      color: "warning"
    })
    await useRouter().push('/inicio');
    return;
  }

  if (!useAuthStore().hasRole('module_donations') && !useAuthStore().hasRole('admin')) {
    await useRouter().push('/inicio');
    throw new Error('User does not have access to the donations module');
  }

  await fetchCategories()
  await fetch()

  useInfiniteScroll(
    scrollContainer,
    () => {
      if (!nextCursor.value) return
      fetch(true)
    },
    {
      distance: 200,
      canLoadMore: () => {
        return nextCursor.value != null
      }
    }
  )
})
</script>

<template>
  <UDashboardPanel id="donation-maintenance">
    <template #header>
      <UDashboardNavbar title="Doações - Gestão de Quebras" :ui="{ right: 'gap-3' }">
        <template #leading>
          <UDashboardSidebarCollapse/>
        </template>
        <template #right>
          <DonationAuditStockUpdateModal />
        </template>
      </UDashboardNavbar>
    </template>

    <template #body>

      <UEmpty
        v-if="logs.length == 0 && !loading"
        icon="i-lucide-scroll-text"
        title="Sem Registos de Auditoria a Mostrar"
        description="Quando alguma atividade que envolva o módulo de doações acontecer, ela irá aparecer aqui"
        variant="naked"
      />

      <div v-else ref="scrollContainer" class="overflow-x-auto max-h-[85vh] overflow-y-auto">
        <UTimeline :items="logs" size="xl" :ui="{ date: 'float-end ms-1' }">
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

        <div v-show="loading" class="flex justify-center py-10">
          <UIcon name="i-lucide-loader-circle" class="animate-spin text-stone-400 size-6" />
        </div>

      </div>
    </template>
  </UDashboardPanel>
</template>

<style scoped>

</style>
