<script setup lang="ts">
import { useRoute, useRouter } from 'vue-router'
import { useApiStore } from '@/stores/api'
import { useAuthStore } from '@/stores/auth'
import { formatTimeAgoIntl } from '@vueuse/core'
import Map from '@/components/Map.vue'
import moment from 'moment'
import 'moment/locale/pt'
import type {TableColumn} from "@nuxt/ui";

moment.locale('pt')

const route = useRoute()
const router = useRouter()
const api = useApiStore()
const authStore = useAuthStore()

const incident       = ref<any>(null)
const pcoList        = ref<any[]>([])
const logistics      = ref<any[]>([])
const logisticTotals = ref({ total_vehicles: 0, total_humans: 0 })
const timeline       = ref<any[]>([])
const loading        = ref(true)

const activePCOs = computed(() => pcoList.value.filter(p => !p.end_pco_datetime))
const mainActivePCO = computed(() => activePCOs.value[0] ?? null)

const now = ref(Date.now())
const isActive = computed(() => !incident.value?.end_datetime)

let interval: ReturnType<typeof setInterval>

onMounted(() => {
  interval = setInterval(() => {
    now.value = Date.now()
  }, 60000) // Atualiza a cada minuto
})

onUnmounted(() => {
  clearInterval(interval)
})

const duration = computed(() => {
  if (!incident.value?.start_datetime) return '—'

  const start = moment(incident.value.start_datetime)
  const end = incident.value?.end_datetime ? moment(incident.value.end_datetime) : moment(now.value)

  const diff = moment.duration(end.diff(start))

  const days = Math.floor(diff.asDays())
  const hours = diff.hours()
  const minutes = diff.minutes()

  const parts = []

  if (days) parts.push(`${days} dia${days > 1 ? 's' : ''}`)
  if (hours) parts.push(`${hours} hora${hours > 1 ? 's' : ''}`)
  if (minutes) parts.push(`${minutes} minuto${minutes > 1 ? 's' : ''}`)

  return parts.join(', ')
})

const logisticsByEntity = computed(() => {
  const map: Record<string, { name: string; vehicles: number; humans: number }> = {}
  for (const item of logistics.value) {
    const name = item.entity?.name ?? 'Desconhecido'
    if (!map[name]) map[name] = { name, vehicles: 0, humans: 0 }
    map[name].vehicles += item.vehicle_count ?? 0
    map[name].humans   += item.human_count   ?? 0
  }
  return Object.values(map)
})

const associatedIncidents = computed(() => {
  return incident.value?.children_incidents ?? []
})

const associatedIncidentColumns: TableColumn<any>[] = [
  {
    accessorKey: 'identifier',
    header: 'Nº Ocorrência'
  },
  {
    accessorKey: 'state',
    header: () => h('div', { class: 'text-center w-full' }, 'Estado'),
    meta: { class: 'text-center' },
    cell: ({ row }) => {
      const state = row.original.incidentState
      return h(
        'div',
        { class: 'flex justify-center' },
        h(
          UBadge,
          {class: 'capitalize rounded-full border', style: { backgroundColor: state?.hex_color }},
          () => state?.name
        )
      )
    }
  },
  {
    header: 'Prioridade',
    cell: ({ row }) => {
      const p = row.original.incidentPriority
      return h('div', { class: 'flex flex-col' }, [
        h('p', { class: 'font-medium text-highlighted' }, p?.name ?? '—'),
        h('p', { class: 'text-xs text-muted' }, p?.description ?? '—')
      ])
    }
  },
  {
    header: 'Categoria',
    cell: ({ row }) => {
      const type = row.original.incidentType
      return h('div', { class: 'flex flex-col' }, [
        h(
          'p',
          { class: 'font-medium text-highlighted' },
          type ? `${type.code} - ${type.species}` : '—'
        ),
        h(
          'p',
          { class: 'text-xs text-muted' },
          type?.type ?? '—'
        )
      ])
    }
  },
  {
    accessorKey: 'start_datetime',
    header: 'Data de Início',
    cell: ({ row }) => { return formatDate(row.original.start_datetime)}
  }
]

const mapIncidents = computed(() => {
  if (incident.value?.is_major) {
    return associatedIncidents.value.filter(i => i.coordinates)
  }

  return incident.value?.coordinates ? [incident.value] : []
})

const mapCenter = computed(() => {
  const incidents = mapIncidents.value

  if (!incidents.length) {
    return [38.7223, -9.1393]
  }

  const coords = incidents
    .map(i => {
      const [lat, lng] = i.coordinates.split(',').map((v: string) => Number(v.trim()))
      return !isNaN(lat) && !isNaN(lng) ? [lat, lng] : null
    })
    .filter(Boolean)

  if (!coords.length) {
    return [38.7223, -9.1393]
  }

  const avgLat = coords.reduce((s, c) => s + c![0], 0) / coords.length
  const avgLng = coords.reduce((s, c) => s + c![1], 0) / coords.length

  return [avgLat, avgLng]
})

const cardUi = {
  container: 'gap-y-1.5',
  wrapper: 'items-start',
  leading: 'p-2.5 rounded-full bg-primary/10 ring ring-inset ring-primary/25 flex-col',
  title: 'font-normal text-muted text-xs uppercase'
}

const recentTimeline = computed(() => timeline.value.slice(0, 5))

const formatDate = (d: string) => {
  if (!d) return '—'
  return new Intl.DateTimeFormat('pt-PT', {
    day: '2-digit', month: '2-digit', year: 'numeric',
    hour: '2-digit', minute: '2-digit'
  }).format(new Date(d))
}

const timeAgo = (date: string) => formatTimeAgoIntl(new Date(date), { locale: 'pt-PT' })

const incidentId = computed(() => Number(route.params.id))

onMounted(async () => {
  if (!authStore.hasPermission('INCIDENTS_LIST')) {
    await router.push('/inicio')
    return
  }

  try {
    const [incRes, pcoRes, logRes, tlRes] = await Promise.all([
      api.getIncident(incidentId.value),
      api.getIncidentPCOs(incidentId.value),
      api.getIncidentLogistics(incidentId.value),
      api.getIncidentTimeline(incidentId.value),
    ])

    incident.value = incRes.data.data
    pcoList.value = pcoRes?.data?.data ?? []
    logistics.value = logRes?.data?.data ?? []
    logisticTotals.value = logRes?.data?.meta ?? { total_vehicles: 0, total_humans: 0 }
    timeline.value = tlRes.data ?? []
  } finally {
    loading.value = false
  }
})
</script>

<template>
  <div class="min-h-screen flex flex-col bg-stone-50 dark:bg-stone-950">
    <div v-if="loading" class="flex items-center justify-center flex-1 py-32">
      <UIcon name="i-lucide-loader-circle" class="animate-spin text-stone-400 size-8" />
    </div>
    <template v-else-if="incident">
      <header class="border-b border-stone-200 dark:border-stone-800 bg-white dark:bg-stone-900 px-6 sm:px-8 py-6">
        <div class="space-y-4">
          <div class="flex items-start justify-between gap-4 flex-wrap">
            <div>
              <p class="text-[0.65rem] font-bold uppercase tracking-[0.15em] text-stone-400 mb-1">Dashboard · Ocorrência</p>
              <h1 class="text-2xl sm:text-3xl font-bold tracking-tight">{{ incident.identifier }}</h1>
            </div>
            <div class="flex items-center gap-2 flex-wrap">
              <UBadge
                v-if="incident.incidentState"
                :style="{ backgroundColor: incident.incidentState.hex_color }"
                variant="solid"
                size="lg"
                class="rounded-full px-3"
              >
                {{ incident.incidentState.name }}
              </UBadge>
              <UBadge
                v-if="incident.incidentPriority"
                :style="{ backgroundColor: incident.incidentPriority.hex_color }"
                variant="solid"
                size="lg"
                class="rounded-full px-3"
              >
                {{ incident.incidentPriority.description }}
              </UBadge>
            </div>
          </div>
          <div class="flex items-center gap-6 flex-wrap text-sm text-stone-500 dark:text-stone-400">
            <span v-if="incident.incidentType" class="flex items-center gap-1.5">
              <UIcon name="i-lucide-tag" class="size-4" />
              {{ incident.incidentType.code }} — {{ incident.incidentType.species }}
            </span>
            <span class="flex items-center gap-1.5">
              <UIcon name="i-lucide-bell" class="size-4" />
              Alerta: {{ formatDate(incident.start_datetime) }}
            </span>
            <span v-if="duration" class="flex items-center gap-1.5 font-medium text-stone-700 dark:text-stone-300">
              <UIcon name="i-lucide-timer" class="size-4" />
              {{ duration }}
              {{ isActive ? 'em curso' : 'encerrada' }}
            </span>
          </div>
        </div>
      </header>
      <main class="flex-1 w-full space-y-4 px-6 sm:px-8 py-6">
        <UPageGrid class="xl:grid-cols-3 gap-4 sm:gap-6 xl:gap-px">
          <UPageCard
            title="Operacionais"
            icon="i-lucide-users"
            variant="subtle"
            :ui="cardUi"
            class="lg:rounded-none first:rounded-l-lg last:rounded-r-lg hover:z-1"
          >
            <div class="flex flex-row justify-between">
              <div class="flex items-center gap-2">
                <span class="text-2xl font-semibold text-highlighted">{{ logisticTotals.total_humans }}</span>
              </div>
              <UButton
                variant="ghost"
                size="xs"
                label="Ver todos"
                trailing-icon="i-lucide-arrow-right"
              />
            </div>
          </UPageCard>
          <UPageCard
            title="Veículos"
            icon="i-lucide-ambulance"
            variant="subtle"
            :ui="cardUi"
            class="lg:rounded-none first:rounded-l-lg last:rounded-r-lg hover:z-1"
          >
            <div class="flex flex-row justify-between">
              <div class="flex items-center gap-2">
                <span class="text-2xl font-semibold text-highlighted">{{ logisticTotals.total_vehicles }}</span>
              </div>
              <UButton
                variant="ghost"
                size="xs"
                label="Ver todos"
                trailing-icon="i-lucide-arrow-right"
              />
            </div>
          </UPageCard>
          <UPageCard
            title="Funções PCO Ativas"
            icon="i-lucide-satellite-dish"
            variant="subtle"
            :ui="cardUi"
            class="lg:rounded-none first:rounded-l-lg last:rounded-r-lg hover:z-1"
          >
            <div class="flex flex-row justify-between">
              <div class="flex items-center gap-2">
                <span class="text-2xl font-semibold text-highlighted">{{ activePCOs.length }}</span>
              </div>
              <UButton
                variant="ghost"
                size="xs"
                label="Ver todos"
                trailing-icon="i-lucide-arrow-right"
              />
            </div>
          </UPageCard>
        </UPageGrid>
        <div v-if="incident.is_major" class="rounded-xl border border-stone-200 dark:border-stone-800 bg-white dark:bg-stone-900 shadow-sm overflow-hidden">
          <div class="flex items-center justify-between px-5 py-4 border-b border-stone-100 dark:border-stone-800">
            <h2 class="font-semibold flex items-center gap-2">
              <UIcon name="i-lucide-network" class="size-4 text-primary-500"/>
              Ocorrências Associadas
            </h2>
          </div>
          <UTable v-if="associatedIncidents.length" :data="associatedIncidents" :columns="associatedIncidentColumns">
            <template #start_datetime-cell="{ row }">{{ formatDate(row.original.start_datetime) }}</template>
            <template #identifier-cell="{ row }">
              <UButton
                variant="link"
                color="primary"
                :to="`/incidents/${row.original.id}`"
              >
                {{ row.original.identifier }}
              </UButton>
            </template>
            <template #incidentState.name-cell="{ row }">
              <UBadge
                v-if="row.original.incidentState"
                :style="{ backgroundColor: row.original.incidentState.hex_color }"
                variant="solid"
              >
                {{ row.original.incidentState.name }}
              </UBadge>
            </template>
          </UTable>
          <div v-else class="px-5 py-8 text-center text-sm text-stone-400">
            Esta ocorrência major não possui ocorrências associadas.
          </div>
        </div>
        <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
          <div class="rounded-xl border border-stone-200 dark:border-stone-800 bg-white dark:bg-stone-900 shadow-sm overflow-hidden">
            <div class="flex items-center justify-between px-5 py-4 border-b border-stone-100 dark:border-stone-800">
              <h2 class="font-semibold flex items-center gap-2">
                <UIcon name="i-lucide-satellite-dish" class="size-4 text-primary-500" />
                Funções de Posto de Comando Ativos
              </h2>
              <UButton
                variant="ghost"
                size="xs"
                label="Ver todos"
                trailing-icon="i-lucide-arrow-right"
              />
            </div>
            <div v-if="activePCOs.length" class="space-y-3 px-5 py-4">
              <UCollapsible v-for="pco in activePCOs" :key="pco.id" class="border border-default rounded-lg">
                <template #default="{ open }">
                  <UButton class="w-full flex items-center justify-between px-4 py-3 text-left">
                    <div class="flex items-center gap-2">
                      <span class="font-medium">{{ pco.function_pco }}</span>
                    </div>
                    <UIcon name="i-lucide-chevron-down" class="size-4 transition-transform" :class="{ 'rotate-180': open }"/>
                  </UButton>
                </template>
                <template #content>
                  <div class="border-t border-default p-4">
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                      <div v-if="pco.resp_pco" class="flex items-center gap-3">
                        <UIcon name="i-lucide-user" class="size-4 mt-0.5 text-primary"/>
                        <div>
                          <p class="text-xs uppercase text-muted">Responsável</p>
                          <p class="font-medium">{{ pco.resp_pco }}</p>
                        </div>
                      </div>
                      <div v-if="pco.category_pco" class="flex items-center gap-3">
                        <UIcon name="i-lucide-shield" class="size-4 mt-0.5 text-primary"/>
                        <div>
                          <p class="text-xs uppercase text-muted">Categoria</p>
                          <p class="font-medium">{{ pco.category_pco }}</p>
                        </div>
                      </div>
                      <div v-if="pco.contact1_pco" class="flex items-center gap-3">
                        <UIcon name="i-lucide-phone" class="size-4 mt-0.5 text-primary"/>
                        <div>
                          <p class="text-xs uppercase text-muted">Contacto</p>
                          <p class="font-medium">{{ pco.contact1_pco }}</p>
                        </div>
                      </div>
                      <div v-if="pco.start_pco_datetime" class="flex items-center gap-3">
                        <UIcon name="i-lucide-calendar" class="size-4 mt-0.5 text-primary"/>
                        <div>
                          <p class="text-xs uppercase text-muted">Ativado em</p>
                          <p class="font-medium">{{ formatDate(pco.start_pco_datetime) }}</p>
                        </div>
                      </div>
                      <div v-if="pco.localization_pco" class="md:col-span-2 flex items-center gap-3">
                        <UIcon name="i-lucide-map-pin" class="size-4 mt-0.5 text-primary"/>
                        <div>
                          <p class="text-xs uppercase text-muted">Localização</p>
                          <p class="font-medium">{{ pco.localization_pco }}</p>
                        </div>
                      </div>
                    </div>
                  </div>
                </template>
              </UCollapsible>
            </div>
            <div v-else class="px-5 py-8 text-center text-sm text-stone-400">
              Sem funções de posto de comando ativos
            </div>
          </div>
          <div class="rounded-xl border border-stone-200 dark:border-stone-800 bg-white dark:bg-stone-900 shadow-sm overflow-hidden">
            <div class="px-5 py-4 border-b border-stone-100 dark:border-stone-800">
              <h2 class="font-semibold flex items-center gap-2">
                <UIcon name="i-lucide-map-pin" class="size-4 text-primary-500" />
                Localização
              </h2>
            </div>
            <div class="px-5 py-4 space-y-3">
              <div class="grid grid-cols-2 gap-x-6 gap-y-2 text-sm">
                <div v-if="incident.address">
                  <span class="text-stone-400 text-xs uppercase tracking-wide">Morada</span>
                  <p class="font-medium">{{ incident.address }}</p>
                </div>
                <div v-if="incident.common_place">
                  <span class="text-stone-400 text-xs uppercase tracking-wide">Ponto de Referência</span>
                  <p class="font-medium">{{ incident.common_place }}</p>
                </div>
                <div v-if="incident.parish">
                  <span class="text-stone-400 text-xs uppercase tracking-wide">Freguesia</span>
                  <p class="font-medium">{{ incident.parish }}</p>
                </div>
                <div v-if="incident.municipality">
                  <span class="text-stone-400 text-xs uppercase tracking-wide">Município</span>
                  <p class="font-medium">{{ incident.municipality }}</p>
                </div>
                <div v-if="incident.district">
                  <span class="text-stone-400 text-xs uppercase tracking-wide">Distrito</span>
                  <p class="font-medium">{{ incident.district }}</p>
                </div>
              </div>
              <Map
                v-if="mapIncidents.length"
                :center="mapCenter"
                :zoom="13"
                :incidents="mapIncidents"
                class="w-full h-24 rounded-lg"
                :interactive="false"
              />
            </div>
          </div>
        </div>
        <div class="rounded-xl border border-stone-200 dark:border-stone-800 bg-white dark:bg-stone-900 shadow-sm overflow-hidden">
          <div class="flex items-center justify-between px-5 py-4 border-b border-stone-100 dark:border-stone-800">
            <h2 class="font-semibold flex items-center gap-2">
              <UIcon name="i-lucide-ambulance" class="size-4 text-primary-500" />
              Meios e Recursos
            </h2>
            <UButton
              variant="ghost"
              size="xs"
              label="Ver todos"
              trailing-icon="i-lucide-arrow-right"
            />
          </div>
          <div v-if="logisticsByEntity.length > 0">
            <UTable
              :data="logisticsByEntity"
              :columns="[
                { accessorKey: 'name',     header: 'Entidade' },
                { accessorKey: 'vehicles', header: 'Veículos' },
                { accessorKey: 'humans',   header: 'Operacionais' },
              ]"
            >
              <template #body-bottom>
                <tr class="border-t-2 border-stone-300 dark:border-stone-600 font-semibold bg-stone-50 dark:bg-stone-800/50">
                  <td class="px-4 py-3 text-sm text-stone-600 dark:text-stone-400">Total</td>
                  <td class="px-4 py-3 text-sm">{{ logisticTotals.total_vehicles }}</td>
                  <td class="px-4 py-3 text-sm">{{ logisticTotals.total_humans }}</td>
                </tr>
              </template>
            </UTable>
          </div>
          <div v-else class="px-5 py-8 text-center text-sm text-stone-400">
            Sem meios registados
          </div>
        </div>
      </main>
    </template>
  </div>
</template>
