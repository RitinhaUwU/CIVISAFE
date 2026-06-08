<script setup lang="ts">
import Map from '../Map.vue'
import * as z from "zod"
import type { FormSubmitEvent } from "@nuxt/ui"
import { useApiStore } from "../../stores/api"
import { useAuthStore } from "../../stores/auth"

const props = defineProps<{
  modelValue: boolean
  coords: { lat: number, lng: number }
}>()

const apiStore = useApiStore()
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

const options = ref([])

const incidentStateMenu = useTemplateRef('incidentStateMenu')
const incidentPriorityMenu = useTemplateRef('incidentPriorityMenu')
const incidentTypeMenu = useTemplateRef('incidentTypeMenu')
const incidentMenu = useTemplateRef('incidentMenu')

const incidentStateItems = ref<any[]>([])
const incidentPriorityItems = ref<any[]>([])
const incidentTypeItems = ref<any[]>([])
const incidentItems = ref<any[]>([])

const incidentStatePage = ref(1)
const incidentPriorityPage = ref(1)
const incidentTypePage = ref(1)
const incidentPage = ref(1)

const incidentStateLastPage = ref(Infinity)
const incidentPriorityLastPage = ref(Infinity)
const incidentTypeLastPage = ref(Infinity)
const incidentLastPage = ref(Infinity)

const incidentStateLoading = ref(false)
const incidentPriorityLoading = ref(false)
const incidentTypeLoading = ref(false)
const incidentLoading = ref(false)

const incidentStateSearch = ref('')
const incidentPrioritySearch = ref('')
const incidentTypeSearch = ref('')
const incidentSearch = ref('')

const toast = useToast()

const schema = z.object({
  is_major: z.boolean(),
  identifier: z.string().min(1, 'O nº de identificação de ocorrência é obrigatório'),
  start_datetime: z.string().min(1, 'A data de alerta é obrigatória'),
  end_datetime: z.string().optional().nullable(),
  incident_state_id: z.number({required_error: 'O estado é obrigatório'}).nullable().refine(val => val !== null, {message: 'O estado é obrigatório'}),
  incident_priority_id: z.number({required_error: 'A prioridade é obrigatória'}).nullable().refine(val => val !== null, {message: 'A prioridade é obrigatória'}),
  incident_type_id: z.number({required_error: 'O tipo de ocorrência é obrigatório'}).nullable().refine(val => val !== null, {message: 'O tipo de ocorrência é obrigatório'}),
  incident_id: z.any().optional().nullable(),
  alert_source_relationship: z.string().optional().nullable(),
  alert_source_name: z.string().optional().nullable(),
  alert_source_contact: z.string().refine(value => !value || /^\+?[0-9]+(?: [0-9]+)*$/.test(value), 'Insira apenas números ou formato +000 000000000').optional().nullable(),
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

const state = reactive<any>({
  is_major: false,
  identifier: '',
  start_datetime: '',
  end_datetime: '',
  incident_state_id: null,
  incident_priority_id: null,
  incident_type_id: null,
  user_id: authStore.currentUserID,
  incident_id: null,
  alert_source_relationship: '',
  alert_source_name: '',
  alert_source_contact: '',
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

async function onSubmit(event: FormSubmitEvent<Schema>) {
  try {
    const payload = {
      ...event.data,
      user_id: authStore.currentUserID,
      incident_id: !state.is_major && state.incident_id ? state.incident_id.id : null,
      children_incidents: state.is_major ? state.incident_id : []
    }

    await apiStore.createIncident(payload)

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
      start_datetime: '',
      end_datetime: '',
      incident_state_id: null,
      incident_priority_id: null,
      incident_type_id: null,
      user_id: authStore.currentUserID,
      incident_id: null,
      alert_source_relationship: '',
      alert_source_name: '',
      alert_source_contact: '',
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
  } catch (e) {
    console.error('Validation errors:', e.response?.data?.errors)
    console.log(e.response?.data)

    toast.add({
      title: 'Erro',
      description: 'Erro ao criar a ocorrência',
      color: 'error'
    })
  }
}

function updateCoordinates(coords: { lat: number, lng: number }) {
  state.coordinates = `${coords.lat}, ${coords.lng}`
}

watch(() => props.coords, (newCoords) => {
  if (newCoords) {
    state.coordinates = `${newCoords.lat}, ${newCoords.lng}`
  }
}, { immediate: true })

const fetchIncidentStates = async (search?: string) => {
  if (incidentStateLoading.value) return

  incidentStateLoading.value = true

  try {
    const res = await apiStore.getIncidentStates({
      page: incidentStatePage.value,
      per_page: 10,
      filter: {
        ...(search ? { search } : {})
      }
    })

    const data = res.data.data
    incidentStateLastPage.value = res.data.meta.last_page

    const mapped = data.map((s: any) => ({
      id: s.id,
      name: s.name
    }))

    const existingIds = new Set(incidentStateItems.value.map(i => i.id))

    incidentStateItems.value = [
      ...incidentStateItems.value,
      ...mapped.filter(i => !existingIds.has(i.id))
    ]
  } finally {
    incidentStateLoading.value = false
  }
}

const fetchIncidentPriorities = async (search?: string) => {
  if (incidentPriorityLoading.value) return

  incidentPriorityLoading.value = true

  try {
    const res = await apiStore.getIncidentPriorities({
      page: incidentPriorityPage.value,
      per_page: 10,
      filter: {
        ...(search ? { search } : {})
      }
    })

    const data = res.data.data
    incidentPriorityLastPage.value = res.data.meta.last_page

    const mapped = data.map((p: any) => ({
      id: p.id,
      name: `${p.name} - ${p.description}`
    }))

    const existingIds = new Set(incidentPriorityItems.value.map(i => i.id))

    incidentPriorityItems.value = [
      ...incidentPriorityItems.value,
      ...mapped.filter(i => !existingIds.has(i.id))
    ]
  } finally {
    incidentPriorityLoading.value = false
  }
}

const fetchIncidentTypes = async (search?: string) => {
  if (incidentTypeLoading.value) return

  incidentTypeLoading.value = true

  try {
    const res = await apiStore.getIncidentTypes({
      page: incidentTypePage.value,
      per_page: 10,
      filter: {
        ...(search ? { search } : {})
      }
    })

    const data = res.data.data
    incidentTypeLastPage.value = res.data.meta.last_page

    const mapped = data.map((t: any) => ({
      id: t.id,
      name: `${t.code} - ${t.species}`
    }))

    const existingIds = new Set(incidentTypeItems.value.map(i => i.id))

    incidentTypeItems.value = [
      ...incidentTypeItems.value,
      ...mapped.filter(i => !existingIds.has(i.id))
    ]
  } finally {
    incidentTypeLoading.value = false
  }
}

const fetchIncidents = async (search?: string) => {
  if (incidentLoading.value) return

  incidentLoading.value = true

  try {
    const res = await apiStore.getIncidents({
      page: incidentPage.value,
      per_page: 10,
      filter: {
        ...(search ? { search } : {}),
        is_major: !state.is_major
      }
    })

    const data = res.data.data
    incidentLastPage.value = res.data.meta.last_page

    const mapped = data.map((t: any) => ({
      id: t.id,
      name: t.identifier
    }))

    const existingIds = new Set(incidentItems.value.map(i => i.id))

    incidentItems.value = [
      ...incidentItems.value,
      ...mapped.filter(i => !existingIds.has(i.id))
    ]
  } finally {
    incidentLoading.value = false
  }
}

watchDebounced(incidentStateSearch, async (val) => {
  incidentStatePage.value = 1
  incidentStateItems.value = []
  incidentStateLastPage.value = Infinity
  await fetchIncidentStates(val)
}, { debounce: 300 })

watchDebounced(incidentPrioritySearch, async (val) => {
  incidentPriorityPage.value = 1
  incidentPriorityItems.value = []
  incidentPriorityLastPage.value = Infinity
  await fetchIncidentPriorities(val)
}, { debounce: 300 })

watchDebounced(incidentTypeSearch, async (val) => {
  incidentTypePage.value = 1
  incidentTypeItems.value = []
  incidentTypeLastPage.value = Infinity
  await fetchIncidentTypes(val)
}, { debounce: 300 })

watchDebounced(incidentSearch, async (val) => {
  incidentPage.value = 1
  incidentItems.value = []
  incidentLastPage.value = Infinity
  await fetchIncidents(val)
}, { debounce: 300 })

watch(
  () => state.is_major,
  async (isMajor) => {
    state.incident_id = isMajor ? [] : null

    if (isMajor) {
      state.coordinates = ''
    } else if (props.coords) {
      state.coordinates = `${props.coords.lat}, ${props.coords.lng}`
    }

    incidentPage.value = 1
    incidentItems.value = []
    incidentLastPage.value = Infinity

    await fetchIncidents(incidentSearch.value)
  }
)

onMounted(() => {
  fetchIncidentStates()
  fetchIncidentPriorities()
  fetchIncidentTypes()
  fetchIncidents()

  // Ocorrências Prioridades
  useInfiniteScroll(
    () => incidentPriorityMenu.value?.viewportRef,
    () => {
      if (incidentPriorityPage.value < incidentPriorityLastPage.value) {
        incidentPriorityPage.value++
        fetchIncidents(incidentPrioritySearch.value)
      }
    },
    {
      canLoadMore: () =>
        !incidentPriorityLoading.value &&
        incidentPriorityPage.value < incidentPriorityLastPage.value
    }
  )

  // Ocorrências Estados
  useInfiniteScroll(
    () => incidentStateMenu.value?.viewportRef,
    () => {
      if (incidentStatePage.value < incidentStateLastPage.value) {
        incidentStatePage.value++
        fetchIncidentStates(incidentStateSearch.value)
      }
    },
    {
      canLoadMore: () =>
        !incidentStateLoading.value &&
        incidentStatePage.value < incidentStateLastPage.value
    }
  )

  // Ocorrências Tipos
  useInfiniteScroll(
    () => incidentTypeMenu.value?.viewportRef,
    () => {
      if (incidentTypePage.value < incidentTypeLastPage.value) {
        incidentTypePage.value++
        fetchIncidentTypes(incidentTypeSearch.value)
      }
    },
    {
      canLoadMore: () =>
        !incidentTypeLoading.value &&
        incidentTypePage.value < incidentTypeLastPage.value
    }
  )

  // Ocorrências
  useInfiniteScroll(
    () => incidentMenu.value?.viewportRef,
    () => {
      if (incidentPage.value < incidentLastPage.value) {
        incidentPage.value++
        fetchIncidents(incidentSearch.value)
      }
    },
    {
      canLoadMore: () =>
        !incidentLoading.value &&
        incidentPage.value < incidentLastPage.value
    }
  )
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
        <div class="p-4 space-y-4 overflow-y-auto flex-1">
          <UTabs :items="tabs" class="w-full">
            <template #geral>
              <div class="mt-4 space-y-6">
                <UCheckbox v-model="state.is_major" label="Ocorrência Major"/>
                <div class="grid grid-cols-1 lg:grid-cols-3 gap-x-8 gap-y-5 items-start">
                  <div class="space-y-5">
                    <UFormField label="Nº Ocorrência:" name="identifier">
                      <UInput v-model="state.identifier" class="w-full" />
                    </UFormField>
                    <UFormField label="Estado:" name="incident_state_id">
                      <USelectMenu
                        ref="incidentStateMenu"
                        v-model="state.incident_state_id"
                        v-model:search-term="incidentStateSearch"
                        :items="incidentStateItems"
                        :loading="incidentStateLoading"
                        value-key="id"
                        label-key="name"
                        ignore-filter
                        class="w-full"
                        placeholder="Selecionar estado"
                      />
                    </UFormField>
                    <UFormField label="Prioridade:" name="incident_priority_id">
                      <USelectMenu
                        ref="incidentPriorityMenu"
                        v-model="state.incident_priority_id"
                        v-model:search-term="incidentPrioritySearch"
                        :items="incidentPriorityItems"
                        :loading="incidentPriorityLoading"
                        value-key="id"
                        label-key="name"
                        ignore-filter
                        class="w-full"
                        placeholder="Selecionar prioridade"
                      />
                    </UFormField>
                    <UFormField label="Tipo de Ocorrência:" name="incident_type_id">
                      <USelectMenu
                        ref="incidentTypeMenu"
                        v-model="state.incident_type_id"
                        v-model:search-term="incidentTypeSearch"
                        :items="incidentTypeItems"
                        :loading="incidentTypeLoading"
                        value-key="id"
                        label-key="name"
                        ignore-filter
                        class="w-full"
                        placeholder="Selecionar tipo"
                      />
                    </UFormField>
                    <UFormField label="Associar Evento:" name="incident_id">
                      <USelectMenu
                        ref="incidentMenu"
                        v-model="state.incident_id"
                        v-model:search-term="incidentSearch"
                        :items="incidentItems"
                        :loading="incidentLoading"
                        value-key="id"
                        label-key="name"
                        ignore-filter
                        :multiple="state.is_major"
                        class="w-full"
                        :placeholder="state.is_major ? 'Selecionar ocorrências associadas' : 'Selecionar ocorrência major'"
                      />
                    </UFormField>
                    <UFormField label="Descrição:" name="obs">
                      <UTextarea v-model="state.obs" class="w-full resize-none overflow-y-auto"/>
                    </UFormField>
                  </div>
                  <div class="space-y-5">
                    <UFormField label="Data Alerta:" name="start_datetime">
                      <UInput type="datetime-local" v-model="state.start_datetime" class="w-full"/>
                    </UFormField>
                    <UFormField label="Data Fim:" name="end_datetime">
                      <UInput type="datetime-local" v-model="state.end_datetime" class="w-full"/>
                    </UFormField>
                    <UFormField label="Fonte de Alerta:" name="alert_source_relationship">
                      <UInput v-model="state.alert_source_relationship" class="w-full"/>
                    </UFormField>
                    <UFormField label="Nome do Contacto:" name="alert_source_name">
                      <UInput v-model="state.alert_source_name" class="w-full"/>
                    </UFormField>
                    <UFormField label="Tlf. Contacto:" name="alert_source_contact">
                      <UInput v-model="state.alert_source_contact" class="w-full"/>
                    </UFormField>
                  </div>
                  <div class="space-y-5">
                    <UFormField label="Coordenadas:" name="coordinates">
                      <UInput v-model="state.coordinates" class="w-full"/>
                    </UFormField>
                    <UFormField label="Distrito:" name="district">
                      <UInput v-model="state.district" class="w-full"/>
                    </UFormField>
                    <UFormField label="Concelho:" name="municipality">
                      <UInput v-model="state.municipality" class="w-full"/>
                    </UFormField>
                    <UFormField label="Freguesia:" name="parish">
                      <UInput v-model="state.parish" class="w-full"/>
                    </UFormField>
                    <UFormField label="Localidade:" name="address">
                      <UInput v-model="state.address" class="w-full"/>
                    </UFormField>
                    <UFormField label="Ponto de Referência:" name="common_place">
                      <UInput v-model="state.common_place" class="w-full"/>
                    </UFormField>
                  </div>
                </div>
              </div>
            </template>
            <template #posto>
              <div class="mt-4 space-y-6">
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mb-4">
                  <div class="space-y-5">
                    <UFormField label="Nome:" name="name_pco">
                      <UInput v-model="state.name_pco" class="w-full"/>
                    </UFormField>
                  </div>
                  <div class="space-y-5">
                    <UFormField label="Coordenadas:" name="coordinates_pco">
                      <UInput v-model="state.coordinates_pco" class="w-full"/>
                    </UFormField>
                  </div>
                </div>
              </div>
            </template>
          </UTabs>
          <Map
            v-if="!state.is_major"
            :center="[props.coords?.lat, props.coords?.lng]"
            :zoom="13"
            class="w-full h-[400px] rounded-lg"
            @map-click="updateCoordinates"
          />
        </div>
        <div class="flex justify-end gap-2 p-4 bg-white shrink-0">
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

<style scoped>

</style>
