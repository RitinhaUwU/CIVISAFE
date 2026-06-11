<script setup lang="ts">
import InicioStats from '@/components/inicio/InicioStats.vue'
import { useApiStore } from '~/stores/api'

const apiStore = useApiStore()

onMounted(async () => {
})
</script>

<template>
  <UDashboardPanel id="donations">
    <template #header>
      <UDashboardNavbar title="Doações" :ui="{ right: 'gap-3' }">
        <template #leading>
          <UDashboardSidebarCollapse />
        </template>
      </UDashboardNavbar>
    </template>

    <template #body>
      <InicioStats />
    </template>
  </UDashboardPanel>
</template>
