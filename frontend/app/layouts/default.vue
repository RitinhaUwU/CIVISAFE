<script setup lang="ts">
import type {NavigationMenuItem} from '@nuxt/ui'
import { useAuthStore } from '@/stores/auth'
import LogoSection from "@/components/LogoSection.vue";

let { isNotificationsSlideoverOpen } = useDashboard()

const auth = useAuthStore()
const open = ref(false)

const links = ref([] satisfies NavigationMenuItem[][]);

const groups = computed(() => [{
  id: 'links',
  label: 'Ir para...',
  items: links.value.flat()
}])


onMounted(() => {

  //Navigation Setup

  let holder = [
    {
      label: 'Início',
      icon: 'i-lucide-house',
      to: '/inicio', onSelect: () => { open.value = false }
    }
  ] satisfies NavigationMenuItem[];

  if(auth.hasPermission('INCIDENTS_LIST')){
    holder.push({
      label: 'Ocorrências',
      icon: 'i-lucide-flame',
      to: '/incidents',
      onSelect: () => (open.value = false)
    });
  }

  if(auth.hasPermission('DONATION_LOG_LIST')){
    holder.push({
      label: 'Doações',
      icon: 'i-lucide-blocks',
      to: '/donations',
      onSelect: () => (open.value = false)
    });
  }

  if(auth.hasPermission('VOLUNTEERS_LIST')){
    holder.push({
      label: 'Voluntários',
      icon: 'i-lucide-users-round',
      to: '/volunteers',
      onSelect: () => (open.value = false)
    });
  }

  if(auth.hasPermission('FACILITIES_LIST')){
    holder.push({
      label: 'Instalações',
      icon: 'i-lucide-building',
      to: '/facilities',
      onSelect: () => (open.value = false)
    });
  }

  if(auth.hasPermission('USERS_VIEW_ANY')){
    holder.push({
      label: 'Utilizadores',
      icon: 'i-lucide-user',
      to: '/users',
      onSelect: () => (open.value = false)
    });
  }

  let administrationChildren = [];
  let incidentsChildren = [];
  let entitiesChildren = [];

  if(auth.hasPermission('INCIDENT_TYPES_LIST')){
    incidentsChildren.push({
      label: 'Tipos',
      to: '/administration/incidentTypes',
      onSelect: () => (open.value = false)
    });
  }

  if(auth.hasPermission('INCIDENT_PRIORITIES_LIST')){
    incidentsChildren.push({
      label: 'Prioridades',
      to: '/administration/incidentPriorities',
      onSelect: () => (open.value = false)
    });
  }

  if(auth.hasPermission('INCIDENT_STATES_LIST')){
    incidentsChildren.push({
      label: 'Estados',
      to: '/administration/incidentStates',
      onSelect: () => (open.value = false)
    });
  }

  if(incidentsChildren.length > 0)
  {
    administrationChildren.push({
        label: 'Ocorrências',
        children: incidentsChildren,
      })
  }

  if(auth.hasPermission('ENTITIES_LIST')){
    entitiesChildren.push({
      label: 'Entidades',
      to: '/administration/entities',
      onSelect: () => (open.value = false)
    });
  }

  if(auth.hasPermission('ENTITY_TYPES_LIST')){
    entitiesChildren.push({
      label: 'Tipos de Entidades',
      to: '/administration/entityTypes',
      onSelect: () => (open.value = false)
    });
  }

  if(entitiesChildren.length > 0)
  {
    administrationChildren.push({
      label: 'Entidades',
      children: entitiesChildren,
    })
  }

  if(auth.hasPermission('DONATION_GOODS_TYPES_LIST')){
    administrationChildren.push({
      label: 'Tipos de Bens Doáveis',
      to: '/administration/donationGoodsTypes',
      onSelect: () => (open.value = false)
    });
  }

  if(administrationChildren.length > 0)
  {
    holder.push({
      label: 'Administração',
      icon: 'i-lucide-wrench',
      type: 'trigger',
      children: administrationChildren
    })
  }


  links.value.push(holder);
  links.value.push([{
    label: 'Notificações',
    icon: 'i-lucide-message-circle',
    target: '_blank',
    onSelect: () => {
      isNotificationsSlideoverOpen.value = true
      open.value = false
    }
  }]);
});
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
            <LogoSection :collapsed="collapsed"/>
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
