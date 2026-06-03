<script setup lang="ts">
import InicioStats from '../components/inicio/InicioStats.vue'
import InicioFormRegisto from '../components/inicio/InicioFormRegisto.vue'
import { useApiStore } from '~/stores/api'

const apiStore = useApiStore()

const selectedCoords = ref<{ lat: number, lng: number }>({lat: 0, lng: 0})
const openModal = ref(false)

const incidents = ref([])

function handleMapClick(coords: { lat: number, lng: number }) {
  selectedCoords.value = coords
  openModal.value = true
}

const incidentsMap = computed(() =>
  incidents.value.filter(incident => incident.incidentState?.name !== 'Terminada')
)

async function refreshIncidents() {
  const res = await apiStore.getIncidents({
    page: 1,
    per_page: 1000
  })

  incidents.value = res.data.data
}

onMounted(async () => {
  await refreshIncidents()
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
      <InicioStats />
      <InicioFormRegisto v-model="openModal" :coords="selectedCoords" @created="refreshIncidents" />
      <Map :incidents="incidentsMap" @map-click="handleMapClick"/>
    </template>
  </UDashboardPanel>
</template>
