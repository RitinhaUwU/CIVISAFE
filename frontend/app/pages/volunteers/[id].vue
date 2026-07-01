<script setup lang="ts">
import { useRoute, useRouter } from 'vue-router'
import { useApiStore } from '@/stores/api'
import * as z from "zod";
import type {BreadcrumbItem} from "@nuxt/ui/components/Breadcrumb.vue";
import {usePaginatedSelect} from "@/composables/usePaginatedSelect";
import {toDatetimeLocal} from "@/utils"

const route = useRoute()
const router = useRouter()
const api = useApiStore()
const toast = useToast()

const saving = ref(false)

const incidentsMenu = useTemplateRef('incidentsMenu')
const incidents = usePaginatedSelect({
  fetcher: api.getIncidents,
  menuRef: incidentsMenu,
  filters: () => ({
    is_major: false
  }),
  map: (i: any) => ({id: i.id, name: i.identifier})
})

const selectOptionSchema = z.object({
  id: z.number(),
  name: z.string()
})

const schema = z.object({
  name: z.string().min(1, 'Nome é obrigatório'),
  contact: z.string().min(9, 'Número inválido').regex(/^\+?[0-9]+(?: [0-9]+)*$/, 'Insira apenas números ou formato +000 000000000'),
  email: z.string().email('Email inválido').nullable().optional(),
  classification: z.enum(['single', 'org', 'misc']),
  num_elements: z.number().min(1),
  mission: z.string().nullable().optional(),
  team_identification: z.string().nullable().optional(),
  has_accommodation: z.boolean(),
  location: z.string().nullable().optional(),
  has_meal: z.boolean(),
  meal_notes: z.string().nullable().optional(),
  meal_location: z.string().nullable().optional(),
  start_datetime: z.string(),
  end_datetime: z.string(),
  incident_id: selectOptionSchema.nullable().optional(),
})

type Schema = z.output<typeof schema>

const state = reactive<Partial<Schema>>({
  name: '',
  contact: '',
  email: '',
  classification: '',
  num_elements: 1,
  mission: '',
  team_identification: '',
  has_accommodation: false,
  location: '',
  has_meal: false,
  meal_notes: '',
  meal_location: '',
  start_datetime: '',
  end_datetime: '',
  incident_id: null as any
})

const handleSave = async () => {
  if (!useAuthStore().hasPermission('VOLUNTEERS_UPDATE')) return

  if (!await checkServerAccess()) {
    toast.add({
      title: 'Sem ligação à internet!',
      description: 'Não é possível guardar alterações sem estar ligado à internet. Tente novamente mais tarde',
      color: 'error'
    });
    return;
  }

  const result = schema.safeParse(state)

  if (!result.success) {
    result.error.issues.forEach((err) => {
      toast.add({
        title: 'Erro de validação',
        description: err.message,
        color: 'error'
      })
    })
    return
  }

  saving.value = true
  try {
    const payload = {
      ...result.data,
      incident_id: result.data.incident_id?.id ?? null
    }

    await api.updateVolunteer(parseInt(<string>route.params.id), payload)

    toast.add({
      title: 'Sucesso',
      description: 'Voluntário atualizado',
      color: 'success'
    })
  } catch (e) {
    toast.add({
      title: 'Erro',
      description: 'Erro ao atualizar',
      color: 'error'
    })
  } finally {
    saving.value = false
  }
}

const fetchVolunteer = async () => {
  const routeID = route.params.id;
  if (typeof routeID !== 'string') {
    toast.add({
      title: 'Voluntário inválido',
      description: 'O Caminho que o trouxe aqui aponta para um Voluntário inválido',
      color: 'error'
    });
    await useRouter().push('/volunteers');
    return;
  }

  const data = (await api.getVolunteer(parseInt(routeID))).data.data

  Object.assign(state, {
    ...data,
    num_elements: Number(data.num_elements),
    start_datetime: toDatetimeLocal(data.start_datetime),
    end_datetime: toDatetimeLocal(data.end_datetime),
    incident_id: data.incident ? {id: data.incident.id, name: data.incident.identifier} : null
  })

  if (data.incident) {
    incidents.prependSelected([{
      id: data.incident.id,
      name: data.incident.identifier
    }])
  }
}

const items = ref<BreadcrumbItem[]>([
  {
    label: 'Voluntários',
    icon: 'i-lucide-handshake',
    to: '/volunteers'
  },
  {
    label: 'Dados do Voluntário',
    icon: 'i-lucide-hand-helping',
  }
])

onMounted(async () => {
  if(!useAuthStore().hasPermission('VOLUNTEERS_LIST')) {
    await useRouter().push('/volunteers');
    return;
  }

  await fetchVolunteer()
  await incidents.fetchItems()
})
</script>

<template>
  <div class="min-h-screen flex flex-col">
    <header class="border-b border-stone-200 dark:border-stone-800">
      <div class="px-6 sm:px-8 py-6">
        <p class="text-[0.65rem] font-bold uppercase tracking-[0.15em] text-stone-400 mb-1">
          Voluntário
        </p>
        <div class="flex items-center justify-between w-full gap-4">
          <h1 class="text-2xl sm:text-3xl font-bold tracking-tight truncate max-w-full">
            {{ state.team_identification }}
          </h1>
          <div class="flex items-center gap-2">
            <UButton
              label="Guardar"
              color="primary"
              :loading="saving"
              @click="handleSave"
              :disabled="!useAuthStore().hasPermission('VOLUNTEERS_UPDATE')"
            />
          </div>
        </div>
      </div>
    </header>
    <div class="flex-1 overflow-y-auto px-6 sm:px-8 py-8 space-y-8">
      <UBreadcrumb :items="items" />
      <section class="space-y-3">
        <h2 class="font-bold">Dados do Responsável</h2>
        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
          <UFormField label="Nome">
            <UInput v-model="state.name" class="w-full" />
          </UFormField>
          <UFormField label="Contacto">
            <UInput v-model="state.contact" class="w-full" />
          </UFormField>
          <UFormField label="Email" class="sm:col-span-2">
            <UInput v-model="state.email" class="w-full" />
          </UFormField>
        </div>
      </section>
      <div class="h-px border-t border-stone-200 dark:border-stone-800" />
      <section class="space-y-3">
        <h2 class="font-bold">Equipa</h2>
        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
          <UFormField label="Identificação">
            <UInput v-model="state.team_identification" class="w-full" />
          </UFormField>
          <UFormField label="Classificação">
            <USelect
              v-model="state.classification"
              class="w-full"
              :items="[
                { label: 'Individual', value: 'single' },
                { label: 'Organização', value: 'org' },
                { label: 'Outro', value: 'misc' }
              ]"
            />
          </UFormField>
          <UFormField v-if="state.classification !== 'single'" label="Nº Elementos">
            <UInput type="number" v-model="state.num_elements" class="w-full" />
          </UFormField>
          <UFormField label="Ocorrência">
            <USelectMenu
              ref="incidentsMenu"
              v-model="state.incident_id"
              v-model:search-term="incidents.search.value"
              :items="incidents.items.value"
              :loading="incidents.loading.value"
              label-key="name"
              ignore-filter
              class="w-full"
              placeholder="Selecionar incidente"
            />
          </UFormField>
        </div>
      </section>
      <div class="h-px border-t border-stone-200 dark:border-stone-800" />
      <section class="space-y-3">
        <h2 class="font-bold">Missão</h2>
        <UTextarea v-model="state.mission" class="w-full" />
      </section>
      <div class="h-px border-t border-stone-200 dark:border-stone-800" />
      <section class="space-y-3">
        <h2 class="font-bold">Alojamento</h2>
        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
          <UFormField label="Necessita de Alojamento?" name="has_accommodation">
            <div class="flex items-center gap-3">
              <USwitch
                v-model="state.has_accommodation"
                checked-icon="i-lucide-check"
                unchecked-icon="i-lucide-x"
              />
              <span class="text-sm font-medium">{{ state.has_accommodation ? 'Sim' : 'Não' }}</span>
            </div>
          </UFormField>
          <UFormField v-if="state.has_accommodation" label="Localização">
            <UInput v-model="state.location" class="w-full" />
          </UFormField>
        </div>
      </section>
      <div class="h-px border-t border-stone-200 dark:border-stone-800" />
      <section class="space-y-3">
        <h2 class="font-bold">Refeição</h2>
        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
          <UFormField label="Necessita de Refeição?" name="has_meal">
            <div class="flex items-center gap-3">
              <USwitch
                v-model="state.has_meal"
                checked-icon="i-lucide-check"
                unchecked-icon="i-lucide-x"
              />
              <span class="text-sm font-medium">{{ state.has_meal ? 'Sim' : 'Não' }}</span>
            </div>
          </UFormField>
          <UFormField v-if="state.has_meal" label="Localização">
            <UInput v-model="state.meal_location" class="w-full" />
          </UFormField>
          <UFormField v-if="state.has_meal" label="Notas:">
            <UTextarea v-model="state.meal_notes" class="w-full" />
          </UFormField>
        </div>
      </section>
      <div class="h-px border-t border-stone-200 dark:border-stone-800" />
      <section class="space-y-3">
        <h2 class="font-bold">Período</h2>
        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
          <UFormField label="Início" required>
            <UInput type="datetime-local" v-model="state.start_datetime" />
          </UFormField>
          <UFormField label="Fim">
            <UInput type="datetime-local" v-model="state.end_datetime" />
          </UFormField>
        </div>
      </section>
    </div>
  </div>
</template>

<style scoped>

</style>
