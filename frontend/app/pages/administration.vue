<script setup lang="ts">
import type {NavigationMenuItem} from '@nuxt/ui'

const route = useRoute()
const links = [[
  {
    label: 'Tipos',
    icon: 'i-lucide-book',
    to: '/administration/incidentTypes',
    exact: true
  },
  {
    label: 'Prioridades',
    icon: 'i-lucide-shield-alert',
    to: '/administration/incidentPriorities'
  },
  {
    label: 'Estados',
    icon: 'i-lucide-info',
    to: '/administration/incidentStates'
  }]] satisfies NavigationMenuItem[][]
</script>

<template>
  <UDashboardPanel
    id="settings"
    :ui="{ body: 'w-full max-w-none px-0 lg:py-12' }"
  >
    <template #header>
      <UDashboardNavbar title="Administração">
        <template #leading>
          <UDashboardSidebarCollapse/>
        </template>
      </UDashboardNavbar>

      <UDashboardToolbar v-if="route.path !== '/administration/entities' && route.path !== '/administration/entityTypes'">
        <!-- NOTE: The `-mx-1` class is used to align with the `DashboardSidebarCollapse` button here. -->
        <UNavigationMenu :items="links" highlight class="-mx-1 flex-1"/>
      </UDashboardToolbar>
    </template>

    <template #body>
      <NuxtPage/>
    </template>
  </UDashboardPanel>
</template>
