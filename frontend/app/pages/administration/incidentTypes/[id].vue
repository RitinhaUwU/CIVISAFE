<script setup lang="ts">
import {useRoute} from 'vue-router'
import {useApiStore} from '@/stores/api'
import type {BreadcrumbItem} from "@nuxt/ui/components/Breadcrumb.vue";

const route = useRoute()
const router = useRouter()
const api = useApiStore()

const state = reactive({
  code: '',
  species: '',
  type: '',
  description: '',
})

const toast = useToast()

const fetchEntity = async () => {
  if(!useAuthStore().hasPermission('INCIDENT_TYPES_LIST'))
  {
    await useRouter().push('/inicio');
    return;
  }

  const routeID = route.params.id;
  if (typeof routeID !== 'string') {
    toast.add({
      title: 'Tipo de Ocorrência inválido',
      description: 'O Caminho que o trouxe aqui aponta para um Tipo de Ocorrência inválido',
      color: 'error'
    });
    await useRouter().push('/incidentTypes');
    return;
  }
  const res = await api.getIncidentType(parseInt(routeID));

  Object.assign(state, res.data.data)
}

const items = ref<BreadcrumbItem[]>([
  {
    label: 'Tipos de Ocorrência',
    icon: 'i-lucide-flame',
    to: '/administration/incidentTypes'
  },
  {
    label: 'Dados do Tipo de Ocorrênia',
    icon: 'i-lucide-brick-wall-fire',
  }
])

onMounted(fetchEntity)
</script>

<template>
  <div class="min-h-screen flex flex-col">
    <header class="border-b border-stone-200 dark:border-stone-800">
      <div class="px-6 sm:px-8 py-6">
        <p class="text-[0.65rem] font-bold uppercase tracking-[0.15em] text-stone-400 mb-1">
          Tipo de Entidade
        </p>
        <div class="flex items-center justify-between w-full gap-4">
          <h1 class="text-2xl sm:text-3xl font-bold tracking-tight truncate max-w-full">
            {{ state.type }}
          </h1>
        </div>
      </div>
    </header>
    <div class="flex-1 overflow-y-auto px-6 sm:px-8 py-8 space-y-8">
      <UBreadcrumb :items="items"/>
      <div class="grid grid-cols-1 gap-8">
        <div class="space-y-6">
          <section class="space-y-2">
            <h2 class="font-bold">Dados Gerais</h2>
            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
              <UFormField label="Código" class="sm:col-span-2">
                <UInput v-model="state.code" class="w-full" disabled/>
              </UFormField>
              <UFormField label="Espécie" class="sm:col-span-2">
                <UInput v-model="state.species" class="w-full" disabled/>
              </UFormField>
              <UFormField label="Tipo" class="sm:col-span-2">
                <UInput v-model="state.type" class="w-full" disabled/>
              </UFormField>
            </div>
          </section>
          <div class="h-px border-t border-stone-200 dark:border-stone-800"/>
          <section class="space-y-2">
            <h2 class="font-bold">Descrição</h2>
            <UTextarea v-model="state.description" :rows="5" class="w-full" disabled/>
          </section>
        </div>
      </div>
    </div>
  </div>
</template>

<style scoped>

</style>
