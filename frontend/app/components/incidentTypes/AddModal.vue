<script setup lang="ts">
import * as z from 'zod'
import type { FormSubmitEvent } from '@nuxt/ui'
import { useApiStore } from '../../stores/api'

const apiStore = useApiStore()
const open = ref(false)
const emit = defineEmits(['created'])

const toast = useToast()

const chip = computed(() => ({ backgroundColor: color.value }))

const schema = z.object({
  name: z.string().min(1, 'Nome é obrigatório'),
  description: z.string().optional().nullable(),
  hex_color: z.string().optional(),
  terminates_incident: z.boolean().optional(),
  is_active: z.boolean().optional()
})

type Schema = z.output<typeof schema>

const state = reactive<Partial<Schema>>({
  name: '',
  description: '',
  hex_color: '#3b82f6',
  terminates_incident: false,
  is_active: true
})

async function onSubmit(event: FormSubmitEvent<Schema>) {
  try {
    await apiStore.createIncidentState(event.data)

    emit('created')
    open.value = false
    toast.add({
      title: 'Sucesso',
      description: 'Entidade criada com sucesso',
      color: 'success'
    })

    Object.assign(state, {
      name: '',
      description: '',
      hex_color: '#3b82f6',
      terminates_incident: false,
      is_active: true
    })

  } catch (e: any) {
    console.log(e.response?.data)

    toast.add({
      title: 'Erro',
      description: 'Erro ao criar entidade',
      color: 'error'
    })
  }
}
</script>

<template>
  <UModal
    v-model:open="open"
    title="Nova Entidade"
    description="Adicione uma Nova Entidade"
  >
    <UButton
      icon="i-lucide-plus"
      label="Nova Entidade"
      color="primary"
    />
    <template #body>
      <UForm
        :state="state"
        :schema="schema"
        class="space-y-5"
        @submit="onSubmit"
      >
        <USwitch
           v-model="state.terminates_incident"
           label="Termina uma Ocorrência?"
           unchecked-icon="i-lucide-x"
           checked-icon="i-lucide-check"
        />
        <UFormField label="Nome" name="name">
          <UInput v-model="state.name" class="w-full" />
        </UFormField>
        <UFormField label="Observações" name="description">
          <UTextarea v-model="state.description" class="w-full" />
        </UFormField>
        <div class="p-2">
          <div class="flex items-center justify-between w-full">
            <UPopover>
              <div class="flex items-center gap-3 cursor-pointer">
                <span
                  :style="{ backgroundColor: state.hex_color }"
                  class="size-5 rounded-full border hover:scale-110 transition"
                />
                <div>
                  <p class="text-sm font-medium">Cor</p>
                  <p class="text-xs text-gray-500">{{ state.hex_color }}</p>
                </div>
              </div>

              <template #content>
                <div class="p-3">
                  <UColorPicker v-model="state.hex_color" />
                </div>
              </template>
            </UPopover>
          </div>
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
