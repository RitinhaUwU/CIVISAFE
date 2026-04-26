<script setup lang="ts">
import InicioStats from '../components/inicio/InicioStats.vue'
import InicioFormRegisto from '../components/inicio/InicioFormRegisto.vue'

const selectedCoords = ref<{ lat: number, lng: number }>({lat: 0, lng: 0})
const openModal = ref(false)

function handleMapClick(coords: { lat: number, lng: number }) {
  selectedCoords.value = coords
  openModal.value = true
}
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
      <InicioFormRegisto v-model="openModal" :coords="selectedCoords" />
      <Map @map-click="handleMapClick"/>
    </template>
  </UDashboardPanel>
</template>
