<script setup lang="ts">
import { useRoute } from 'vue-router'
import { useApiStore } from '@/stores/api'

const route = useRoute()
const router = useRouter()
const api = useApiStore()

const saving = ref(false)
const incidentTypes = ref([])
const incidentStates = ref([])
const incidentPriorities = ref([])

const state = reactive({
  identifier: '',
  incident_type_id: null as number,
  incident_state_id: null as number,
  incident_priority_id: null as number,
  user_id: null,
  coordinates: '',
  common_place: '',
  address: '',
  parish: '',
  municipality: '',
  district: '',
  command_post: '',
  is_major: '',
  alert_source_relationship: '',
  alert_source_name: '',
  alert_source_contact: '',
  obs: '',
  incident_id: null
})

const handleCancel = () => {
  router.back()
}

const fetchEntity = async () => {
  const res = await api.getIncident(route.params.id)
  const data = res.data.data

  Object.assign(state, {
    ...data,
    incident_type_id: data.incidentType?.id,
    incident_state_id: data.incidentState?.id,
    incident_priority_id: data.incidentPriority?.id,
  })
}

const fetchSelects = async () => {
  const [typesRes, statesRes, prioritiesRes] = await Promise.all([
    api.getIncidentTypes(),
    api.getIncidentStates(),
    api.getIncidentPriorities()
  ])

  incidentTypes.value = typesRes.data.data.map(t => ({
    label: `${t.code} - ${t.species}`,
    value: t.id
  }))

  incidentStates.value = statesRes.data.data.map(s => ({
    label: s.name,
    value: s.id
  }))

  incidentPriorities.value = prioritiesRes.data.data.map(p => ({
    label: `${p.name} - ${p.description}`,
    value: p.id
  }))
}

onMounted(async () => {
  await fetchSelects()
  await fetchEntity()
})
</script>

<template>
  <div class="min-h-screen flex flex-col">
    <header class="border-b border-stone-200 dark:border-stone-800">
      <div class="px-6 sm:px-8 py-6">
        <p class="text-[0.65rem] font-bold uppercase tracking-[0.15em] text-stone-400 mb-1">
          Ocorrência
        </p>
        <div class="flex items-center justify-between w-full gap-4">
          <h1 class="text-2xl sm:text-3xl font-bold tracking-tight text-stone-900 dark:text-stone-50">
            {{ state.identifier }}
          </h1>
          <div class="flex items-center gap-2">
            <UButton label="Voltar" color="neutral" variant="subtle" @click="handleCancel" />
          </div>
        </div>
      </div>
    </header>
    <div class="flex-1 flex flex-col min-h-0">
      <div class="flex-1 overflow-y-auto px-6 sm:px-8 py-8 pb-8">
        <div class="grid grid-cols-1 gap-8">
          <div class="space-y-6">
            <section class="space-y-2">
              <h2 class="font-bold">Dados Gerais</h2>
              <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                <UFormField label="Identificador">
                  <UInput v-model="state.identifier" class="w-full"/>
                </UFormField>
                <UFormField label="Tipo de Ocorrência" class="sm:col-span-2">
                  <USelect
                    v-model="state.incident_type_id"
                    :items="incidentTypes"
                    class="w-full"
                  />
                </UFormField>
                <UFormField label="Estado" class="sm:col-span-2">
                  <USelect
                    v-model="state.incident_state_id"
                    :items="incidentStates"
                    class="w-full"
                  />
                </UFormField>
                <UFormField label="Prioridade" class="sm:col-span-2">
                  <USelect
                    v-model="state.incident_priority_id"
                    :items="incidentPriorities"
                    class="w-full"
                  />
                </UFormField>
                <USwitch
                  v-model="state.is_major"
                  label="Ocorrência Major"
                />
              </div>
            </section>
            <div class="h-px border-t border-stone-200 dark:border-stone-800" />
            <section class="space-y-2">
              <h2 class="font-bold">Localização</h2>
              <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                <UFormField label="Morada">
                  <UInput v-model="state.address" class="w-full"/>
                </UFormField>
                <UFormField label="Freguesia">
                  <UInput v-model="state.parish" class="w-full"/>
                </UFormField>
                <UFormField label="Município">
                  <UInput v-model="state.municipality" class="w-full"/>
                </UFormField>
                <UFormField label="Distrito">
                  <UInput v-model="state.district" class="w-full"/>
                </UFormField>
              </div>
            </section>
            <div class="h-px border-t border-stone-200 dark:border-stone-800" />
            <section class="space-y-2">
              <h2 class="font-bold">Observações</h2>
              <UTextarea v-model="state.obs" :rows="5" class="w-full" />
            </section>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>

<style scoped>

</style>
