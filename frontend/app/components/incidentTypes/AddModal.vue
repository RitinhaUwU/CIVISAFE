<script setup lang="ts">
import * as z from 'zod'
import type { FormSubmitEvent } from '@nuxt/ui'
import { useApiStore } from '../../stores/api'

const apiStore = useApiStore()
const open = ref(false)
const emit = defineEmits(['created'])

const toast = useToast()

const schema = z.object({
  code: z.string().min(1, "O código é obrigatório").regex(/^\d+$/, "O código deve conter apenas números").transform(Number),
  species: z.string().trim().min(1, "Insira uma espécie"),
  type: z.string().trim().min(1, "Insira um tipo"),
  description: z.string().optional().nullable(),
  is_active: z.boolean().optional()
})

type Schema = z.output<typeof schema>

const state = reactive<Partial<Schema>>({
  code: '',
  species: '',
  type: '',
  description: '',
  is_active: true,
  created_at: '',
  updated_at: ''
})

async function onSubmit(event: FormSubmitEvent<Schema>) {

  try {
    await apiStore.createIncidentType(event.data)

    emit('created')
    open.value = false
    toast.add({
      title: 'Sucesso',
      description: 'Tipo de Incidente criado com sucesso',
      color: 'success'
    })

    Object.assign(state, {
      code: '',
      species: '',
      type: '',
      description: '',
      is_active: true,
      created_at: '',
      updated_at: ''
    })

  } catch (e: any) {
    console.log(e.response?.data)

    toast.add({
      title: 'Erro',
      description: 'Erro ao criar o Tipo de Entidade ou Código já está em uso',
      color: 'error'
    })
  }
}
</script>

<template>
  <UModal
    v-model:open="open"
    title="Novo Tipo"
    description="Adicione um Novo Tipo de Incidente"
  >
    <UButton
      icon="i-lucide-plus"
      label="Novo Tipo"
      color="primary"
    />
    <template #body>
      <UForm
        :state="state"
        :schema="schema"
        class="space-y-5"
        @submit="onSubmit"
      >
        <UFormField label="Código" name="code">
          <UInput v-model="state.code" class="w-full" />
        </UFormField>
        <UFormField label="Espécie" name="species">
          <UInput v-model="state.species" class="w-full" />
        </UFormField>
        <UFormField label="Tipo" name="type">
          <UInput v-model="state.type" class="w-full" />
        </UFormField>
        <UFormField label="Observações" name="description">
          <UTextarea v-model="state.description" class="w-full" />
        </UFormField>
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
