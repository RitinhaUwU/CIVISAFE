<script setup lang="ts">
import InicioFormRegisto from '@/components/inicio/InicioFormRegisto.vue'
import { useApiStore } from '@/stores/api'
import type {Incident} from '@/types'
import {useToast} from "@nuxt/ui/composables"

const api = useApiStore()

const selectedCoords = ref<{ lat: number, lng: number }>({lat: 0, lng: 0})
const openModal = ref(false)

const incidents = ref<Incident[]>([])

async function handleMapClick(coords: { lat: number, lng: number }) {
  if(!useAuthStore().hasPermission('INCIDENTS_CREATE'))
    return;

  if(!await checkServerAccess())
  {
    useToast().add({
      title: 'Não é possível adicionar ocorrências offline',
      color: 'error'
    });
    return;
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
  const res = await api.getIncidents({per_page: 1000})

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
      </UDashboardNavbar>
    </template>
    <template #body>
      <div class="flex flex-col lg:flex-row h-full min-h-0 gap-4">
        <InicioFormRegisto v-model="openModal" :coords="selectedCoords" @created="refreshIncidents"/>
        <div class="flex-1">
          <Map
            class="w-full h-full"
            :incidents="incidentsMap"
            @map-click="handleMapClick"
          />
        </div>
        <div class="w-full lg:w-96 shrink-0">
          <UCard class="h-[calc(100vh-80px)] flex flex-col" :ui="{ body: 'flex-1 overflow-hidden' }">
            <template #header>
              <h3 class="font-semibold">
                Incidentes Major Ativos
              </h3>
            </template>
            <div class="h-full overflow-y-auto pr-2 space-y-3">
              <div v-for="incident in majorIncidents" :key="incident.id" class="rounded-lg border border-orange-200 dark:border-orange-900 bg-orange-50/50 dark:bg-orange-950/20 p-4 transition-all hover:shadow-md">
                <div class="flex items-start justify-between gap-3">
                  <div>
                    <div class="font-semibold text-base">{{ incident.identifier }}</div>
                  </div>
                  <UBadge color="primary" variant="soft">{{ incident.incidentState?.name }}</UBadge>
                </div>
                <div class="mt-4 space-y-2 text-sm">
                  <div>
                    <span class="font-medium">Tipo: </span>
                    <span class="text-gray-600 dark:text-gray-300">{{ incident.incidentType?.code }}</span>
                  </div>
                  <div>
                    <span class="font-medium">Espécie: </span>
                    <span class="text-gray-600 dark:text-gray-300">{{ incident.incidentType?.species }}</span>
                  </div>
                  <div>
                    <span class="font-medium">Categoria: </span>
                    <span class="text-gray-600 dark:text-gray-300">{{ incident.incidentType?.type }}</span>
                  </div>
                </div>
                <div class="mt-4 flex justify-end">
                  <UButton
                    :to="`/incidents/${incident.id}/dashboard`"
                    color="primary"
                    size="sm"
                    icon="i-lucide-arrow-right"
                  >
                    Ver ocorrência
                  </UButton>
                </div>
              </div>
            </div>
          </UCard>
        </div>
      </div>
    </template>
  </UDashboardPanel>
</template>
