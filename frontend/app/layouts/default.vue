<script setup lang="ts">
import type {NavigationMenuItem} from '@nuxt/ui'
import { useAuthStore } from '../stores/auth'

let { isNotificationsSlideoverOpen } = useDashboard()

const auth = useAuthStore()
const open = ref(false)

const links = [
  [
    {
      label: 'Início',
      icon: 'i-lucide-house',
      to: '/inicio', onSelect: () => { open.value = false }
    },
    auth.hasPermission('USERS_VIEW_ANY') && {
      label: 'Utilizadores',
      icon: 'i-lucide-user',
      to: '/users',
      onSelect: () => (open.value = false)
    },
    auth.hasPermission('INCIDENTS_LIST') && {
      label: 'Ocorrências',
      icon: 'i-lucide-flame',
      to: '/incidents',
      onSelect: () => (open.value = false)
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
      }, {
        label: 'Voluntário',
        to: '/volunteers'
      }]
    }
  ], [{
    label: 'Notificações',
    icon: 'i-lucide-message-circle',
    target: '_blank',
    onSelect: () => {
      isNotificationsSlideoverOpen.value = true
      open.value = false
    }
  }]
] satisfies NavigationMenuItem[][]

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
