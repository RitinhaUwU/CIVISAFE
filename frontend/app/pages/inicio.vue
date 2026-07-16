<script setup lang="ts">
import InicioFormRegisto from '@/components/inicio/InicioFormRegisto.vue'
import { useApiStore } from '@/stores/api'
import type { Incident } from '@/types'
import { useToast } from '@nuxt/ui/composables'
import IncidentsIsMajorSideover from "~/components/IncidentsIsMajorSideover.vue";

const api = useApiStore()

const selectedCoords = ref<{ lat: number, lng: number }>({ lat: 0, lng: 0 })
const openModal = ref(false)
const openSlideover = ref(false)

const mapIncidents = ref<Incident[]>([])

const majorIncidents = ref<Incident[]>([])
const majorNextCursor = ref<string | null>(null)
const majorLoading = ref(false)
const majorScrollContainer = ref<HTMLElement | null>(null)

async function handleMapClick(coords: { lat: number, lng: number }) {
  if(!useAuthStore().hasPermission('INCIDENTS_CREATE'))
    return;

  if (!await checkServerAccess()) {
    useToast().add({
      title: 'Não é possível adicionar ocorrências offline',
      color: 'error'
    })
    return
  }
  selectedCoords.value = coords
  openModal.value = true
}

async function fetchMajorIncidents(loadMore = false) {
  if (majorLoading.value) return
  if (loadMore && !majorNextCursor.value) return

  majorLoading.value = true

  try {
    const res = await api.getIncidents({
      per_page: 10,
      filter: { is_major: true },
      ...(loadMore && majorNextCursor.value ? { cursor: majorNextCursor.value } : {})
    })

    const newIncidents = res.data.data.filter(
      (i: Incident) => i.incidentState?.terminates_incident !== true
    )

    if (loadMore) {
      const existing = new Set(majorIncidents.value.map(i => i.id))
      majorIncidents.value.push(...newIncidents.filter(i => !existing.has(i.id)))
    } else {
      majorIncidents.value = newIncidents
    }

    majorNextCursor.value = res.data.meta?.next_cursor
  } finally {
    majorLoading.value = false
  }
}

const lastBounds = ref<string | null>(null)

const loadedIncidentIds = new Set<number>()

async function refreshMapIncidents(bbox: string, cursor: string | null = null): Promise<void> {
  const res = await api.getIncidents({
    per_page: 10,
    filter: { bbox },
    ...(cursor ? { cursor } : {})
  })

  const newIncidents = res.data.data.filter((i: Incident) =>
    !i.is_major &&
    i.incidentState?.terminates_incident !== true &&
    !loadedIncidentIds.has(i.id)
  )

  newIncidents.forEach((i: Incident) => loadedIncidentIds.add(i.id))
  mapIncidents.value.push(...newIncidents)

  const nextCursor = res.data.meta?.next_cursor ?? null

  if (nextCursor) {
    await refreshMapIncidents(bbox, nextCursor)
  }
}

function handleBoundsChange(bbox: string) {
  lastBounds.value = bbox
  refreshMapIncidents(bbox)
}

async function refreshIncidents() {
  await fetchMajorIncidents()

  if (lastBounds.value) {
    await refreshMapIncidents(lastBounds.value)
  }
}

onMounted(async () => {
  if(useAuthStore().hasPermission('INCIDENTS_LIST')){
    await fetchMajorIncidents()

    useInfiniteScroll(
      majorScrollContainer,
      () => fetchMajorIncidents(true),
      {
        distance: 200,
        direction: 'right',
        canLoadMore: () => !majorLoading.value && majorNextCursor.value != null
      }
    )
  }
})
</script>

<template>
  <UDashboardPanel id="home">
    <template #header>
      <UDashboardNavbar title="Início" :ui="{ right: 'gap-3' }">
        <template #leading>
          <UDashboardSidebarCollapse />
        </template>
        <template #right>
          <UButton
            class="lg:hidden"
            variant="solid"
            icon="i-lucide-panel-right"
            @click="openSlideover = true"
          >
            <span>Incidentes Major Ativos</span>
          </UButton>
        </template>
      </UDashboardNavbar>
    </template>
    <template #body>
      <div class="flex flex-col h-full min-h-0 gap-4">
        <InicioFormRegisto v-model="openModal" :coords="selectedCoords" @created="refreshIncidents"/>
        <UCard v-if="majorIncidents.length" class="shrink-0 hidden lg:block">
          <template #header>
            <h3 class="font-semibold">Ocorrências Major Ativas</h3>
          </template>
          <div class="flex gap-4 overflow-x-auto pb-2">
            <div ref="majorScrollContainer" class="flex gap-4 overflow-x-auto pb-2">
              <div v-for="incident in majorIncidents" :key="incident.id" class="w-72 sm:w-80 shrink-0 rounded-lg border border-orange-200 dark:border-orange-900 bg-orange-50/50 dark:bg-orange-950/20 p-4 flex flex-col">
                <div class="flex items-start justify-between">
                  <div class="font-semibold">{{ incident.identifier }}</div>
                  <UBadge color="primary" variant="soft">
                    {{ incident.incidentState?.name }}
                  </UBadge>
                </div>
                <div class="mt-4 space-y-2 text-sm flex-1">
                  <div><span class="font-medium">Tipo:</span> {{ incident.incidentType?.code }}</div>
                  <div><span class="font-medium">Espécie:</span> {{ incident.incidentType?.species }}</div>
                  <div><span class="font-medium">Categoria:</span> {{ incident.incidentType?.type }}</div>
                </div>
                <footer class="pt-4 flex justify-end">
                  <UButton
                    :to="`/incidents/${incident.id}/dashboard`"
                    size="sm"
                    icon="i-lucide-arrow-right"
                    label="Ver ocorrência"
                  />
                </footer>
              </div>
            </div>
          </div>
        </UCard>
        <IncidentsIsMajorSideover
          v-model:open="openSlideover"
          :incidents="majorIncidents"
        />
        <div class="flex-1 min-h-[400px]">
          <Map
            class="w-full h-full"
            :incidents="mapIncidents"
            @map-click="handleMapClick"
            @bounds-change="handleBoundsChange"
          />
        </div>
      </div>
    </template>
  </UDashboardPanel>
</template>
