<script setup lang="ts">
import SearchableSelect from './SearchableSelect.vue'
import Map from '../Map.vue'
import { useApiStore } from "../../stores/api"
import * as z from "zod"
import type { FormSubmitEvent } from "@nuxt/ui"
import { useAuthStore } from "../../stores/auth";

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

const schema = z.object({})

type Schema = z.output<typeof schema>

const state = reactive<any>({
  geral: {
    is_major: false,
    identifier: '',
    start_datetime: '',
    end_datetime: '',
    incident_state_id: null,
    incident_priority_id: null,
    incident_type_id: null,
    user_id: authStore.currentUserID,
    incident_id: [],
    alert_source_relationship: '',
    alert_source_name: '',
    alert_source_contact: '',
    coordinates: '',
    address: '',
    district: '',
    municipality: '',
    parish: '',
    common_place: '',
    obs: ''
  },

  posto: {
    coordenadas: '',
    data_montagem: '',
    hora_montagem: '',
    resp_logistica: '',
    resp_operacoes: '',
    resp_posto: '',
    resp_planeamento: ''
  }
})

const tabItems = [
  {
    label: 'Geral',
    icon: 'i-lucide-users',
    slot: 'geral'
  },
  {
    label: 'Posto de Comando',
    icon: 'i-lucide-satellite-dish',
    slot: 'posto'
  }
]

function resetForm() {
  Object.assign(state, {
    geral: {
      is_major: false,
      identifier: '',
      start_datetime: '',
      end_datetime: '',
      incident_state_id: null,
      incident_priority_id: null,
      incident_type_id: null,
      user_id: authStore.currentUserID,
      incident_id: [],
      alert_source_relationship: '',
      alert_source_name: '',
      alert_source_contact: '',
      coordinates: '',
      address: '',
      district: '',
      municipality: '',
      parish: '',
      common_place: '',
      obs: ''
    },

    posto: {
      coordenadas: '',
      data_montagem: '',
      hora_montagem: '',
      resp_logistica: '',
      resp_operacoes: '',
      resp_posto: '',
      resp_planeamento: ''
    }
  })
}

async function onSubmit(event: FormSubmitEvent<Schema>) {
  try {
    const payload = {
      ...state,
      geral: { ...state.geral, incident_id: state.geral.incident_id.map((i: any) => i.id)}
    }

    console.log(payload)

    await apiStore.createIncident(payload)

    emit('created')
    emit('update:modelValue', false)

    toast.add({
      title: 'Sucesso',
      description: 'Ocorrência criada com sucesso',
      color: 'success'
    })

    resetForm()
  } catch (e) {
    toast.add({
      title: 'Erro',
      description: 'Erro ao criar a ocorrência',
      color: 'error'
    })
  }
}

function updateCoordinates(coords: { lat: number, lng: number }) {
  state.geral.coordinates = `${coords.lat}, ${coords.lng}`
}

watch(() => props.coords, (newCoords) => {
  if (newCoords) {
    state.geral.coordinates = `${newCoords.lat}, ${newCoords.lng}`
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
    const mapped = data.map((s: any) => ({ id: s.id, name: s.name }))
    const existingIds = new Set(incidentStateItems.value.map(i => i.id))
    incidentStateItems.value = [...incidentStateItems.value, ...mapped.filter(i => !existingIds.has(i.id))]
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
    const mapped = data.map((p: any) => ({ id: p.id, name: `${p.name} - ${p.description}` }))
    const existingIds = new Set(incidentPriorityItems.value.map(i => i.id))
    incidentPriorityItems.value = [...incidentPriorityItems.value, ...mapped.filter(i => !existingIds.has(i.id))]
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
    const mapped = data.map((t: any) => ({ id: t.id, name: `${t.code} - ${t.species}` }))
    const existingIds = new Set(incidentTypeItems.value.map(i => i.id))
    incidentTypeItems.value = [...incidentTypeItems.value, ...mapped.filter(i => !existingIds.has(i.id))]
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
        ...(search ? { search } : {})
      }
    })

    const data = res.data.data
    incidentLastPage.value = res.data.meta.last_page
    const mapped = data.map((t: any) => ({ id: t.id, name: t.identifier }))
    const existingIds = new Set(incidentItems.value.map(i => i.id))
    incidentItems.value = [...incidentItems.value, ...mapped.filter(i => !existingIds.has(i.id))]
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

onMounted(() => {
  fetchIncidentStates()
  fetchIncidentPriorities()
  fetchIncidentTypes()
  fetchIncidents()

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
    title="Nova Ocorrência"
    description="Criar Ocorrência"
    :ui="{ content: 'max-h-[90vh] overflow-y-auto w-full max-w-5xl' }"
    @update:open="emit('update:modelValue', $event)"
  >
    <template #content>
      <UForm
        :state="state"
        :schema="schema"
        class="flex flex-col h-[90vh]"
        @submit="onSubmit"
      >
        <div class="p-4 space-y-4 overflow-y-auto flex-1">
          <h2 class="text-lg font-semibold">
            Registo de Ocorrências
          </h2>
          <UTabs :items="tabItems">
            <template #geral>
              <UModal
                :open="props.modelValue"
                title="Nova Ocorrência"
                description="Criar Ocorrência"
                :ui="{ content: 'max-h-[90vh] overflow-y-auto w-full max-w-5xl' }"
                @update:open="emit('update:modelValue', $event)"
              >
                <template #content>
                  <UForm
                    :state="state"
                    :schema="schema"
                    class="flex flex-col h-[90vh]"
                    @submit="onSubmit"
                  >
                    <div class="p-4 space-y-4 overflow-y-auto flex-1">
                      <h2 class="text-lg font-semibold">
                        Registo de Ocorrências
                      </h2>
                      <UTabs :items="tabItems">
                        <template #geral>
                          <div class="mt-4 space-y-6">
                            <UCheckbox
                              v-model="state.geral.is_major"
                              label="Ocorrência Major"
                            />
                            <div class="grid grid-cols-1 lg:grid-cols-3 gap-x-8 gap-y-5 items-start">
                              <div class="space-y-5">
                                <UFormField label="Nº Ocorrência:" name="num_ocorrencia">
                                  <UInput v-model="state.geral.identifier" class="w-full"/>
                                </UFormField>
                                <UFormField label="Estado:" name="incident_state_id">
                                  <USelectMenu
                                    ref="incidentStateMenu"
                                    v-model="state.geral.incident_state_id"
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
                                    v-model="state.geral.incident_priority_id"
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
                                    v-model="state.geral.incident_type_id"
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
                                <UFormField v-if="state.geral.is_major" label="Associar Evento:" name="incident_id">
                                  <USelectMenu
                                    ref="incidentMenu"
                                    v-model="state.geral.incident_id"
                                    v-model:search-term="incidentSearch"
                                    :items="incidentItems"
                                    :loading="incidentLoading"
                                    label-key="name"
                                    ignore-filter
                                    multiple
                                    class="w-full"
                                    placeholder="Selecionar eventos"
                                  />
                                </UFormField>
                                <UFormField label="Descrição:" name="descricao">
                                  <UTextarea v-model="state.geral.obs" class="w-full resize-none overflow-y-auto"/>
                                </UFormField>
                              </div>
                              <div class="space-y-5">
                                <UFormField label="Data Alerta:" name="start_datetime">
                                  <UInput type="datetime-local" v-model="state.geral.start_datetime" class="w-full"/>
                                </UFormField>
                                <UFormField label="Data Fim:" name="end_datetime">
                                  <UInput type="datetime-local" v-model="state.geral.end_datetime" class="w-full"/>
                                </UFormField>
                                <UFormField label="Fonte de Alerta:" name="fonte_alerta">
                                  <UInput v-model="state.geral.alert_source_relationship" class="w-full"/>
                                </UFormField>
                                <UFormField label="Nome do Contacto:" name="nome_contacto">
                                  <UInput v-model="state.geral.alert_source_name" class="w-full"/>
                                </UFormField>
                                <UFormField label="Tlf. Contacto:" name="tel_contacto">
                                  <UInput v-model="state.geral.alert_source_contact" class="w-full"/>
                                </UFormField>
                              </div>
                              <div class="space-y-5">
                                <UFormField label="Coordenadas:" name="coordenadas">
                                  <UInput v-model="state.geral.coordinates" class="w-full"/>
                                </UFormField>
                                <UFormField label="Localidade:" name="localidade">
                                  <UInput v-model="state.geral.address" class="w-full"/>
                                </UFormField>
                                <UFormField label="Distrito:" name="distrito">
                                  <UInput v-model="state.geral.district" class="w-full"/>
                                </UFormField>
                                <UFormField label="Concelho:" name="concelho">
                                  <UInput v-model="state.geral.municipality" class="w-full"/>
                                </UFormField>
                                <UFormField label="Freguesia:" name="freguesia">
                                  <UInput v-model="state.geral.parish" class="w-full"/>
                                </UFormField>
                                <UFormField label="Ponto de Referência:" name="ponto_referencia">
                                  <UInput v-model="state.geral.common_place" class="w-full"/>
                                </UFormField>
                              </div>
                            </div>
                          </div>
                        </template>
                        <template #posto>
                          <div class="mt-4 space-y-6">
                            <div class="grid grid-cols-1 lg:grid-cols-3 gap-x-8 gap-y-5 items-start">
                              <div class="space-y-5">
                                <UFormField label="Coordenadas:" name="coordenadas">
                                  <UInput v-model="state.posto.coordenadas" class="w-full"/>
                                </UFormField>
                                <UFormField label="Data Montagem:" name="data_montagem">
                                  <UInput type="datetime-local" v-model="state.posto.data_montagem" class="w-full"/>
                                </UFormField>
                              </div>
                              <div class="space-y-5">
                                <UFormField label="Resp. Posto de Comando:" name="posto_comando">
                                  <UInputMenu v-model="state.posto.resp_posto" :items="options" class="w-full"/>
                                </UFormField>
                                <UFormField label="Resp. Célula de Logística:" name="celula_logistica">
                                  <UInputMenu v-model="state.posto.resp_logistica" :items="options" class="w-full"/>
                                </UFormField>
                              </div>
                              <div class="space-y-5">
                                <UFormField label="Resp. Célula de Operações:" name="celula_operacoes">
                                  <UInputMenu v-model="state.posto.resp_operacoes" :items="options" class="w-full"/>
                                </UFormField>
                                <UFormField label="Resp. Célula de Planeamento:" name="celula_planeamento">
                                  <UInputMenu v-model="state.posto.resp_planeamento" :items="options" class="w-full"/>
                                </UFormField>
                              </div>
                            </div>
                          </div>
                        </template>
                      </UTabs>
                      <Map
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
          </UTabs>
        </div>
      </UForm>
    </template>
  </UModal>
</template>
<style scoped>
</style>
