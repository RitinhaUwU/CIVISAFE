<script setup lang="ts">
import Map from '../Map.vue'
import * as z from "zod"
import type { FormSubmitEvent } from "@nuxt/ui"
import { useApiStore } from "@/stores/api"
import { useAuthStore } from "@/stores/auth"
import {usePaginatedSelect} from "@/composables/usePaginatedSelect";
import {toDatetimeLocal} from "@/utils"

const props = defineProps<{
  modelValue: boolean
  coords: { lat: number, lng: number }
}>()

const api = useApiStore()
const authStore = useAuthStore()

const emit = defineEmits([
  'update:modelValue',
  'created'
])

const tabs = [
  {
    label: 'Geral',
    slot: 'geral',
    icon: 'i-lucide-users'
  },
  {
    label: 'Posto de Comando',
    slot: 'posto',
    icon: 'i-lucide-satellite-dish',
  }
]

const toast = useToast()

const selectOptionSchema = z.object({
  id: z.number(),
  name: z.string()
})

const schema = z.object({
  is_major: z.boolean(),
  identifier: z.string().min(1, 'O nº de identificação de ocorrência é obrigatório'),
  start_datetime: z.string().min(1, 'A data de alerta é obrigatória'),
  end_datetime: z.string().optional().nullable(),
  incident_state_id: selectOptionSchema.nullable().refine(val => val !== null, {message: 'O estado é obrigatório'}),
  incident_priority_id: selectOptionSchema.nullable().refine(val => val !== null, {message: 'A prioridade é obrigatória'}),
  incident_type_id: selectOptionSchema.nullable().refine(val => val !== null, {message: 'O tipo de ocorrência é obrigatório'}),
  incident_id: z.any().optional().nullable(),
  alert_source_relationship: z.string().optional().nullable(),
  alert_source_name: z.string().optional().nullable(),
  alert_source_contact: z.string().refine(value => !value || /^\+?[0-9]+(?: [0-9]+)*$/.test(value), 'Insira apenas números ou formato +000 000000000').optional().nullable(),
  operational_grid: z.string().optional().nullable(),
  coordinates: z.string().optional().nullable(),
  address: z.string().optional().nullable(),
  district: z.string().optional().nullable(),
  municipality: z.string().optional().nullable(),
  parish: z.string().optional().nullable(),
  common_place: z.string().optional().nullable(),
  obs: z.string().optional().nullable(),
  coordinates_pco: z.string().optional().nullable(),
  name_pco: z.string().optional().nullable(),
})

type Schema = z.output<typeof schema>

const state = reactive<Partial<Schema>>({
  is_major: false,
  identifier: '',
  start_datetime: toDatetimeLocal(new Date().toISOString()),
  end_datetime: '',
  incident_state_id: null as any,
  incident_priority_id: null as any,
  incident_type_id: null as any,
  user_id: authStore.currentUserID,
  incident_id: null,
  alert_source_relationship: '',
  alert_source_name: '',
  alert_source_contact: '',
  operational_grid: '',
  coordinates: '',
  address: '',
  district: '',
  municipality: '',
  parish: '',
  common_place: '',
  obs: '',
  coordinates_pco: '',
  name_pco: ''
})

const typeMenu = useTemplateRef('typeMenu')
const stateMenu = useTemplateRef('stateMenu')
const priorityMenu = useTemplateRef('priorityMenu')
const incidentsMenu = useTemplateRef('incidentsMenu')

const types = usePaginatedSelect({
  fetcher: api.getIncidentTypes,
  menuRef: typeMenu,
  map: (t: any) => ({
    id: t.id,
    name: `${t.code} - ${t.type}`
  })
})
const states = usePaginatedSelect({
  fetcher: api.getIncidentStates,
  menuRef: stateMenu,
  map: (s: any) => ({
    id: s.id,
    name: s.name,
    terminates_incident: s.terminates_incident
  })
})
const priorities = usePaginatedSelect({
  fetcher: api.getIncidentPriorities,
  menuRef: priorityMenu,
  map: (p: any) => ({
    id: p.id,
    name: `${p.name} - ${p.description}`
  })
})
const incidents = usePaginatedSelect({
  fetcher: api.getIncidents,
  menuRef: incidentsMenu,
  filters: () => ({
    terminates_incident: !state.is_major
  }),
  map: (i: any) => ({
    id: i.id,
    name: i.identifier
  })
})

function setDefaultIncidentState() {
  state.incident_state_id = states.items.value.find(s => s.name === 'Aberta') ?? null
}

async function onSubmit(event: FormSubmitEvent<Schema>) {
  try {
    const payload = {
      ...event.data,
      user_id: authStore.currentUserID,
      incident_state_id: event.data.incident_state_id?.id,
      incident_priority_id: event.data.incident_priority_id?.id,
      incident_type_id: event.data.incident_type_id?.id,
      is_major: state.is_major,
      incident_id: state.is_major ? state.incident_id?.map((i: any) => i.id) : state.incident_id?.id ?? null,
      children_incidents: state.is_major ? (state.incident_id ?? []).map((i: any) => i.id) : []
    }

    await api.createIncident(payload)

    emit('created')
    emit('update:modelValue', false)

    toast.add({
      title: 'Sucesso',
      description: 'Ocorrência criada com sucesso',
      color: 'success'
    })

    Object.assign(state, {
      is_major: false,
      identifier: '',
      start_datetime: toDatetimeLocal(new Date().toISOString()),
      end_datetime: '',
      incident_state_id: null,
      incident_priority_id: null,
      incident_type_id: null,
      user_id: authStore.currentUserID,
      incident_id: null,
      alert_source_relationship: '',
      alert_source_name: '',
      alert_source_contact: '',
      operational_grid: '',
      coordinates: '',
      address: '',
      district: '',
      municipality: '',
      parish: '',
      common_place: '',
      obs: '',
      coordinates_pco: '',
      name_pco: ''
    })
    setDefaultIncidentState()
  } catch (e) {
    if (errors?.identifier?.length) {
      toast.add({
        title: 'Identificador duplicado',
        description: 'O identificador já se encontra em uso',
        color: 'error'
      })
      return
    }

    toast.add({
      title: 'Erro',
      description: 'Erro ao criar a ocorrência',
      color: 'error'
    })
  }
}

watch(() => state.is_major, async (isMajor) => {
  state.incident_id = isMajor ? [] : null

  if (isMajor) {
    state.coordinates = ''
  } else if (props.coords) {
    state.coordinates = `${props.coords.lat}, ${props.coords.lng}`
  }

  await incidents.reset()
})

watch(() => state.incident_state_id, (newState) => {
  if (newState?.terminates_incident) {
    state.end_datetime = toDatetimeLocal(new Date().toISOString())
  } else {
    state.end_datetime = ''
  }
})

// Map
function updateCoordinates(coords: { lat: number, lng: number }) {
  state.coordinates = `${coords.lat}, ${coords.lng}`
}

watch(() => props.coords, (newCoords) => {
  if (newCoords) {
    state.coordinates = `${newCoords.lat}, ${newCoords.lng}`
  }
}, { immediate: true })

onMounted(async() => {
  await Promise.all([
    states.fetchItems(),
    priorities.fetchItems(),
    types.fetchItems(),
    incidents.fetchItems()
  ])
  setDefaultIncidentState()
})
</script>

<template>
  <UModal
    :open="props.modelValue"
    title="Registo Ocorrência"
    description="Criar Ocorrência"
    :ui="{ content: 'max-h-[90vh] overflow-y-auto w-full max-w-5xl' }"
    @update:open="emit('update:modelValue', $event)"
  >
    <template #body>
      <UForm
        :state="state"
        :schema="schema"
        @submit="onSubmit"
      >
        <div class="p-4 space-y-6 overflow-y-auto flex-1">
          <UTabs :items="tabs" class="w-full">
            <template #geral>
              <div class="mt-4 space-y-8">
                <div class="flex items-center justify-between rounded-lg border border-default bg-elevated/30 px-4 py-3">
                  <div>
                    <p class="text-sm font-medium text-highlighted">Ocorrência Major</p>
                    <p class="text-xs text-muted">Permite associar múltiplas ocorrências relacionadas</p>
                  </div>
                  <USwitch v-model="state.is_major" />
                </div>
                <div class="space-y-4">
                  <h3 class="text-xs font-semibold uppercase tracking-wide text-muted">Identificação</h3>
                  <div class="grid grid-cols-1 md:grid-cols-2 gap-x-6 gap-y-4">
                    <UFormField label="Nº Ocorrência" name="identifier" required>
                      <UInput v-model="state.identifier" class="w-full" placeholder="Ex: 2026/0001" />
                    </UFormField>
                    <UFormField :label="state.is_major ? 'Ocorrências Associadas' : 'Associar a Ocorrência Major'" name="incident_id">
                      <USelectMenu
                        ref="incidentsMenu"
                        v-model="state.incident_id"
                        v-model:search-term="incidents.search.value"
                        :items="incidents.items.value"
                        :loading="incidents.loading.value"
                        label-key="name"
                        :multiple="state.is_major"
                        class="w-full"
                        ignore-filter
                        :placeholder="state.is_major ? 'Selecionar ocorrências associadas' : 'Selecionar ocorrência major'"
                      />
                    </UFormField>
                  </div>
                  <UFormField label="Tipo de Ocorrência" name="incident_type_id" required>
                    <USelectMenu
                      ref="typeMenu"
                      v-model="state.incident_type_id"
                      v-model:search-term="types.search.value"
                      :items="types.items.value"
                      :loading="types.loading.value"
                      label-key="name"
                      class="w-full"
                      ignore-filter
                      placeholder="Selecionar tipo de ocorrência"
                    />
                  </UFormField>
                  <div class="grid grid-cols-1 md:grid-cols-2 gap-x-6 gap-y-4">
                    <UFormField label="Estado" name="incident_state_id" required>
                      <USelectMenu
                        ref="stateMenu"
                        v-model="state.incident_state_id"
                        v-model:search-term="states.search.value"
                        :items="states.items.value"
                        :loading="states.loading.value"
                        label-key="name"
                        class="w-full"
                        ignore-filter
                        placeholder="Selecionar estado"
                      />
                    </UFormField>
                    <UFormField label="Prioridade" name="incident_priority_id" required>
                      <USelectMenu
                        ref="priorityMenu"
                        v-model="state.incident_priority_id"
                        v-model:search-term="priorities.search.value"
                        :items="priorities.items.value"
                        :loading="priorities.loading.value"
                        label-key="name"
                        class="w-full"
                        ignore-filter
                        placeholder="Selecionar prioridade"
                      />
                    </UFormField>
                    <UFormField label="Data Alerta" name="start_datetime" required>
                      <UInput type="datetime-local" v-model="state.start_datetime" class="w-full"/>
                    </UFormField>
                    <UFormField label="Data Fim" name="end_datetime">
                      <UInput type="datetime-local" v-model="state.end_datetime" class="w-full"/>
                    </UFormField>
                  </div>
                </div>
                <USeparator />
                <div class="space-y-4">
                  <h3 class="text-xs font-semibold uppercase tracking-wide text-muted">Origem do Alerta</h3>
                  <div class="grid grid-cols-1 md:grid-cols-3 gap-x-6 gap-y-4">
                    <UFormField label="Fonte de Alerta" name="alert_source_relationship">
                      <UInput v-model="state.alert_source_relationship" class="w-full"/>
                    </UFormField>
                    <UFormField label="Nome do Contacto" name="alert_source_name">
                      <UInput v-model="state.alert_source_name" class="w-full"/>
                    </UFormField>
                    <UFormField label="Tlf. Contacto" name="alert_source_contact">
                      <UInput v-model="state.alert_source_contact" class="w-full" placeholder="+351 900000000"/>
                    </UFormField>
                  </div>
                </div>
                <USeparator />
                <div class="space-y-4">
                  <h3 class="text-xs font-semibold uppercase tracking-wide text-muted">Descrição</h3>
                  <UFormField name="obs">
                    <UTextarea
                      v-model="state.obs"
                      class="w-full resize-none overflow-y-auto"
                      :rows="4"
                      placeholder="Descreva a ocorrência..."
                    />
                  </UFormField>
                </div>
                <USeparator />
                <div class="space-y-4">
                  <h3 class="text-xs font-semibold uppercase tracking-wide text-muted">Localização</h3>
                  <UFormField label="Grelha Operacional" name="operational_grid">
                    <UInput v-model="state.operational_grid" class="w-full"/>
                  </UFormField>
                  <div class="grid grid-cols-1 md:grid-cols-2 gap-x-6 gap-y-4">
                    <UFormField label="Coordenadas" name="coordinates">
                      <UInput v-model="state.coordinates" class="w-full" icon="i-lucide-map-pin"/>
                    </UFormField>
                    <UFormField label="Ponto de Referência" name="common_place">
                      <UInput v-model="state.common_place" class="w-full"/>
                    </UFormField>
                  </div>
                  <div class="grid grid-cols-1 md:grid-cols-3 gap-x-6 gap-y-4">
                    <UFormField label="Distrito" name="district">
                      <UInput v-model="state.district" class="w-full"/>
                    </UFormField>
                    <UFormField label="Concelho" name="municipality">
                      <UInput v-model="state.municipality" class="w-full"/>
                    </UFormField>
                    <UFormField label="Freguesia" name="parish">
                      <UInput v-model="state.parish" class="w-full"/>
                    </UFormField>
                  </div>
                  <UFormField label="Localidade" name="address">
                    <UInput v-model="state.address" class="w-full"/>
                  </UFormField>
                </div>
                <Map
                  v-if="!state.is_major"
                  :selectedCoords="[props.coords?.lat, props.coords?.lng]"
                  :zoom="13"
                  :dropMarkerOnClick="true"
                  class="w-full h-[320px] rounded-lg border border-default overflow-hidden"
                  @map-click="updateCoordinates"
                />
              </div>
            </template>
            <template #posto>
              <div class="mt-4 space-y-4">
                <h3 class="text-xs font-semibold uppercase tracking-wide text-muted">Posto de Comando Operacional</h3>
                <div class="grid grid-cols-1 md:grid-cols-2 gap-x-6 gap-y-4">
                  <UFormField label="Nome" name="name_pco">
                    <UInput v-model="state.name_pco" class="w-full"/>
                  </UFormField>
                  <UFormField label="Coordenadas" name="coordinates_pco">
                    <UInput v-model="state.coordinates_pco" class="w-full" icon="i-lucide-map-pin"/>
                  </UFormField>
                </div>
              </div>
            </template>
          </UTabs>
        </div>
        <div class="flex justify-end gap-2 p-4 shrink-0">
          <UButton
            label="Cancelar"
            color="neutral"
            variant="ghost"
            @click="emit('update:modelValue', false)"
          />
          <UButton
            label="Guardar"
            type="submit"
            color="primary"
          />
        </div>
      </UForm>
    </template>
  </UModal>
</template>
