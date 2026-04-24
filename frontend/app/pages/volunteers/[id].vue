<script setup lang="ts">
import { useRoute, useRouter } from 'vue-router'
import { useApiStore } from '@/stores/api'

const route = useRoute()
const router = useRouter()
const api = useApiStore()
const toast = useToast()

const saving = ref(false)
const incidents = ref<{ label: string; value: number }[]>([])

const state = reactive({
  name: '',
  contact: '',
  email: '',
  classification: '',
  num_elements: 1,
  mission: '',
  team_identification: '',
  has_accommodation: false,
  location: '',
  start_datetime: '',
  end_datetime: '',
  incident_id: null as number | null,
})

const handleSave = async () => {
  saving.value = true
  try {
    await api.updateVolunteer(route.params.id, state)

    toast.add({
      title: 'Sucesso',
      description: 'Voluntário atualizada',
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

const handleCancel = () => {
  router.back()
}

const fetchVolunteer = async () => {
  const res = await api.getVolunteer(route.params.id)
  const data = res.data.data

  Object.assign(state, data)
}

const fetchIncidents = async () => {
  const res = await api.getIncidents()

  incidents.value = res.data.data.map((i: any) => ({
    label: i.identifier,
    value: i.id
  }))
}

onMounted(async () => {
  await fetchIncidents()
  await fetchVolunteer()
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
          <h1 class="text-2xl sm:text-3xl font-bold tracking-tight">
            {{ state.team_identification }}
          </h1>
          <div class="flex items-center gap-2">
            <UButton label="Voltar" color="neutral" variant="subtle" @click="handleCancel" />
            <UButton label="Guardar" color="primary" :loading="saving" @click="handleSave" />
          </div>
        </div>
      </div>
    </header>
    <div class="flex-1 overflow-y-auto px-6 sm:px-8 py-8 space-y-8">
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
            <USelect
              v-model="state.incident_id"
              class="w-full"
              :items="incidents"
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
          <USwitch v-model="state.has_accommodation" label="Tem alojamento" />
          <UFormField v-if="state.has_accommodation" label="Localização">
            <UInput v-model="state.location" class="w-full" />
          </UFormField>
        </div>
      </section>
      <div class="h-px border-t border-stone-200 dark:border-stone-800" />
      <section class="space-y-3">
        <h2 class="font-bold">Período</h2>
        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
          <UFormField label="Início">
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
