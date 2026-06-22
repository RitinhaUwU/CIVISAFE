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

const incidentsMap = computed(() =>
  incidents.value.filter(incident => incident.incidentState?.name !== 'Terminada' && incident.incidentState?.name !== 'Transitou Para Outra Divisão')
)

async function refreshIncidents() {
  const res = await api.getIncidents({
    page: 1,
    per_page: 1000
  })

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
      <div class="flex-1 h-full">
        <InicioFormRegisto v-model="openModal" :coords="selectedCoords" @created="refreshIncidents" />
        <Map class="w-full h-full" :incidents="incidentsMap" @map-click="handleMapClick"/>
      </div>
    </template>
  </UDashboardPanel>
</template>
