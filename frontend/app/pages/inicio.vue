<script setup lang="ts">
const { isNotificationsSlideoverOpen } = useDashboard()

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

        <template #right>
          <UTooltip text="Notifications" :shortcuts="['N']">
            <UButton
              color="neutral"
              variant="ghost"
              square
              @click="isNotificationsSlideoverOpen = true"
            >
              <UChip color="error" inset>
                <UIcon name="i-lucide-bell" class="size-5 shrink-0" />
              </UChip>
            </UButton>
          </UTooltip>

        </template>
      </UDashboardNavbar>
    </template>

    <template #body>
      <InicioStats />
      <InicioFormRegisto
        v-model="openModal"
        :coords="selectedCoords"
      />
      <Map @map-click="handleMapClick"/>
    </template>
  </UDashboardPanel>
</template>
