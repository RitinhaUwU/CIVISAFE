<script setup lang="ts">
import * as z from 'zod'
import type { FormSubmitEvent } from '@nuxt/ui'
import { useApiStore } from '@/stores/api'

const apiStore = useApiStore()
const open = ref(false)
const emit = defineEmits(['created'])

const incidents = ref<{ label: string; value: number }[]>([])

const toast = useToast()

const schema = z.object({
  name: z.string().min(1, 'Nome é obrigatório'),
  contact: z.string().min(9, 'Número inválido').regex(/^\+?[0-9]+(?: [0-9]+)*$/, 'Insira apenas números ou formato +000 000000000'),
  email: z.string().email('Email inválido'),
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
  incident_id: z.number().nullable().optional(),
})

type Schema = z.output<typeof schema>

const state = reactive<Partial<Schema>>({
  name: '',
  contact: '',
  email: '',
  classification: 'single',
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
  incident_id: null,
})

async function onSubmit(event: FormSubmitEvent<Schema>) {
  try {
    await apiStore.createVolunteer(event.data)

    emit('created')
    open.value = false

    toast.add({
      title: 'Sucesso',
      description: 'Voluntário criado com sucesso',
      color: 'success'
    })

    Object.assign(state, {
      name: '',
      contact: '',
      email: '',
      classification: 'single',
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
      incident_id: null,
    })
  } catch (e) {
    toast.add({
      title: 'Erro',
      description: 'Erro ao criar voluntário',
      color: 'error'
    })
  }
}

const fetchIncidents = async () => {
  const res = await apiStore.getIncidents()

  incidents.value = res.data.data.map((i: any) => ({
    label: i.identifier,
    value: i.id
  }))
}

onMounted(() => {
  fetchIncidents()
})
</script>

<template>
  <UModal v-model:open="open" title="Novo Voluntário" description="Criar Voluntário">
    <UButton icon="i-lucide-plus" label="Novo Voluntário" color="primary"/>
    <template #body>
      <UForm :state="state" :schema="schema" class="space-y-4" @submit="onSubmit">
        <h3 class="text-sm font-semibold text-muted">Dados do responsável</h3>
        <div class="grid grid-cols-1 lg:grid-cols-2 gap-3">
          <UFormField label="Nome" name="name">
            <UInput v-model="state.name" class="w-full" />
          </UFormField>
          <UFormField label="Contacto" name="contact">
            <UInput v-model="state.contact" class="w-full" />
          </UFormField>
          <UFormField label="Email" name="email">
            <UInput v-model="state.email" class="w-full" />
          </UFormField>
        </div>
        <div class="h-px border-t border-stone-200 dark:border-stone-800" />
        <h3 class="text-sm font-semibold text-muted">Dados da equipa</h3>
        <div class="grid grid-cols-1 lg:grid-cols-2 gap-3">
          <UFormField label="Identificação" name="team_identification">
            <UInput v-model="state.team_identification" class="w-full" />
          </UFormField>
          <UFormField label="Classificação" name="classification">
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
          <UFormField v-if="state.classification !== 'single'" label="Nº Elementos" name="num_elements">
            <UInput type="number" v-model="state.num_elements" />
          </UFormField>
        </div>
        <div class="h-px border-t border-stone-200 dark:border-stone-800" />
        <h3 class="text-sm font-semibold text-muted">Tipo de missão</h3>
        <UFormField label="Missão" name="mission">
          <UTextarea v-model="state.mission" class="w-full" />
        </UFormField>
        <UFormField label="Ocorrência" name="incident_id">
          <USelect
            v-model="state.incident_id"
            class="w-full"
            :items="incidents"
            placeholder="Selecionar ocorrência"
          />
        </UFormField>
        <div class="h-px border-t border-stone-200 dark:border-stone-800" />
        <h3 class="text-sm font-semibold text-muted">Informação de Alojamento</h3>
        <div class="grid grid-cols-1 lg:grid-cols-2 gap-3">
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
          <UFormField v-if="state.has_accommodation" label="Morada" name="location">
            <UInput v-model="state.location" class="w-full" />
          </UFormField>
        </div>
        <div class="h-px border-t border-stone-200 dark:border-stone-800" />
        <h3 class="text-sm font-semibold text-muted">Informação de Refeições</h3>
        <div class="grid grid-cols-1 lg:grid-cols-2 gap-3">
          <UFormField label="Necessita de Alimentação?" name="has_meal">
            <div class="flex items-center gap-3">
              <USwitch
                v-model="state.has_meal"
                checked-icon="i-lucide-check"
                unchecked-icon="i-lucide-x"
              />
              <span class="text-sm font-medium">{{ state.has_meal ? 'Sim' : 'Não' }}</span>
            </div>
          </UFormField>
          <UFormField v-if="state.has_meal" label="Refeitório:" name="meal_location">
            <UInput v-model="state.meal_location" class="w-full" />
          </UFormField>
          <UFormField v-if="state.has_meal" label="Quais refeições necessita:" name="meal_notes">
            <UTextarea v-model="state.meal_notes" class="w-full" />
          </UFormField>
        </div>
        <div class="h-px border-t border-stone-200 dark:border-stone-800" />
        <h3 class="text-sm font-semibold text-muted">Período</h3>
        <div class="grid grid-cols-1 lg:grid-cols-2 gap-3">
          <UFormField label="Entrada" name="start_datetime">
            <UInput type="datetime-local" v-model="state.start_datetime" />
          </UFormField>
          <UFormField label="Saída" name="end_datetime">
            <UInput type="datetime-local" v-model="state.end_datetime" />
          </UFormField>
        </div>
        <div class="flex justify-between gap-3 pt-2">
          <UButton
            label="Cancelar"
            color="neutral"
            variant="subtle"
            class="flex-1 justify-center"
            @click="open = false"
          />
          <UButton
            label="Guardar"
            color="primary"
            type="submit"
            class="flex-1 justify-center"
          />
        </div>
      </UForm>
    </template>
  </UModal>
</template>
