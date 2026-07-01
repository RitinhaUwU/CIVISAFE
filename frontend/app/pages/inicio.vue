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

const incidents = ref<Incident[]>([])

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

const majorIncidents = computed(() =>
  incidents.value.filter(incident =>
    incident.is_major &&
    incident.incidentState?.terminates_incident !== true
  )
)

const incidentsMap = computed(() =>
  incidents.value.filter(incident =>
    !incident.is_major &&
    incident.incidentState?.terminates_incident !== true
  )
)

async function refreshIncidents() {
  const res = await api.getIncidents({ per_page: 1000 })
  incidents.value = res.data.data
}

onMounted(async () => {
  if(useAuthStore().hasPermission('INCIDENTS_LIST'))
  {
    await refreshIncidents()
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
            <h3 class="font-semibold">Incidentes Major Ativos</h3>
          </template>
          <div class="flex gap-4 overflow-x-auto pb-2">
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
        </UCard>
        <IncidentsIsMajorSideover
          v-model:open="openSlideover"
          :incidents="majorIncidents"
        />
        <div class="flex-1 min-h-[400px]">
          <Map
            class="w-full h-full"
            :incidents="incidentsMap"
            @map-click="handleMapClick"
          />
        </div>
      </div>
    </template>
  </UDashboardPanel>
</template>
