<script setup lang="ts">
import type {NavigationMenuItem} from '@nuxt/ui'

const open = ref(false)

const links = [[{
  label: 'Início',
  icon: 'i-lucide-house',
  to: '/inicio',
  onSelect: () => {
    open.value = false
  }
}, {
  label: 'Utilizadores',
  icon: 'i-lucide-user',
  to: '/customers',
  onSelect: () => {
    open.value = false
  }
}, {
  label: 'Ocorrências',
  icon: 'i-lucide-flame',
  to: '/ocorrencias',
  onSelect: () => {
    open.value = false
  }
}, {
  label: 'Definições',
  to: '/settings',
  icon: 'i-lucide-settings',
  type: 'trigger',
  children: [{
    label: 'General',
    to: '/settings',
    exact: true,
    onSelect: () => {
      open.value = false
    }
  }, {
    label: 'Members',
    to: '/settings/members',
    onSelect: () => {
      open.value = false
    }
  }, {
    label: 'Notifications',
    to: '/settings/notifications',
    onSelect: () => {
      open.value = false
    }
  }, {
    label: 'Security',
    to: '/settings/security',
    onSelect: () => {
      open.value = false
    }
  }]
}, {
  label: 'Administração',
  icon: 'i-lucide-wrench',
  type: 'trigger',
  children: [{
    label: 'Ocorrências',
    children: [
      {
        label: 'Tipos',
        to: '/administration/incidentTypes',
        exact: true
      },
      {
        label: 'Prioridades',
        to: '/administration/incidentPriorities'
      },
      {
        label: 'Estados',
        to: '/administration/incidentStates'
      }
    ]
  }, {
    label: 'Entidades',
    children: [
      {
        label: 'Entidades',
        to: '/administration/entities',
        exact: true
      },
      {
        label: 'Tipos de Entidades',
        to: '/administration/entityTypes'
      }
    ]
  }]
}]] satisfies NavigationMenuItem[][]

const groups = computed(() => [{
  id: 'links',
  label: 'Ir para...',
  items: links.flat()
}])
</script>

<template>
  <div class="flex flex-col min-h-screen">
    <div class="flex flex-1 overflow-hidden">
      <UDashboardGroup unit="rem" class="flex-1">
        <UDashboardSidebar
          id="default"
          v-model:open="open"
          collapsible
          resizable
          class="bg-elevated/25"
          :ui="{ footer: 'lg:border-t lg:border-default' }"
        >
          <template #header="{ collapsed }">
            <TeamsMenu :collapsed="collapsed"/>
          </template>
          <template #default="{ collapsed }">
            <UDashboardSearchButton :collapsed="collapsed" class="bg-transparent ring-default"/>
            <UNavigationMenu
              :collapsed="collapsed"
              :items="links[0]"
              orientation="vertical"
              tooltip
              popover
            />
            <UNavigationMenu
              :collapsed="collapsed"
              :items="links[1]"
              orientation="vertical"
              tooltip
              class="mt-auto"
            />
          </template>
          <template #footer="{ collapsed }">
            <UserMenu :collapsed="collapsed"/>
          </template>
        </UDashboardSidebar>
        <UDashboardSearch :groups="groups"/>
        <div class="flex-1 overflow-auto">
          <slot/>
        </div>
        <NotificationsSlideover/>
      </UDashboardGroup>
    </div>
  </div>
</template>
