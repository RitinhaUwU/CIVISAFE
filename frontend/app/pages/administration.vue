<script setup lang="ts">
import type {NavigationMenuItem} from '@nuxt/ui'

const links = [[
  {
    label: 'Entidades',
    icon: 'i-lucide-building-2',
    to: '/administration/entities'
  },
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
  <UDashboardPanel id="settings" :ui="{ body: 'lg:py-12' }">
    <template #header>
      <UDashboardNavbar title="Settings">
        <template #leading>
          <UDashboardSidebarCollapse/>
        </template>
      </UDashboardNavbar>

      <UDashboardToolbar>
        <!-- NOTE: The `-mx-1` class is used to align with the `DashboardSidebarCollapse` button here. -->
        <UNavigationMenu :items="links" highlight class="-mx-1 flex-1"/>
      </UDashboardToolbar>
    </template>

    <template #body>
      <div class="flex flex-col gap-4 w-full mx-auto">
        <NuxtPage/>
      </div>
    </template>
  </UDashboardPanel>
</template>
