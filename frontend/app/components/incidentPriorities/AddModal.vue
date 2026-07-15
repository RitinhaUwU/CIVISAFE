<script setup lang="ts">
import * as z from 'zod'
import type { FormSubmitEvent } from '@nuxt/ui'
import { useApiStore } from '@/stores/api'
import {useAuthStore} from "~/stores/auth";

const apiStore = useApiStore()
const open = ref(false)
const emit = defineEmits(['created'])

const toast = useToast()

const schema = z.object({
  name: z.string().min(1, 'Nome é obrigatório'),
  description: z.string().optional().nullable(),
  hex_color: z.string().optional(),
  is_active: z.boolean().optional()
})

type Schema = z.output<typeof schema>

const state = reactive<Partial<Schema>>({
  name: '',
  description: '',
  hex_color: '#3b82f6',
  is_active: true
})

async function onSubmit(event: FormSubmitEvent<Schema>) {
  if (!useAuthStore().hasPermission('INCIDENT_PRIORITIES_CREATE')) return;

  if (!await checkServerAccess()) {
    useToast().add({
      title: 'Sem ligação à internet!',
      description: 'Não é possível guardar alterações sem estar ligado à internet. Tente novamente mais tarde',
      color: 'error'
    });
    return;
  }

  try {
    await apiStore.createIncidentPriority(event.data)

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
      is_active: true
    })

  } catch (e: any) {
    const errors = e.response?.data?.errors

    if (errors?.name) {
      toast.add({
        title: 'Erro de validação',
        description: 'Já existe uma Prioridade com esse nome',
        color: 'error'
      })
    } else {
      toast.add({
        title: 'Erro',
        description: 'Erro ao criar Prioridade',
        color: 'error'
      })
    }
  }
}
</script>

<template>
  <UModal
    v-model:open="open"
    title="Novo Tipo de Prioridade"
    description="Adicione um Novo Tipo de Prioridade"
  >
    <UButton
      icon="i-lucide-plus"
      label="Nova Prioridade"
      color="primary"
    />
    <template #body>
      <UForm
        :state="state"
        :schema="schema"
        class="space-y-5"
        @submit="onSubmit"
      >
        <UFormField label="Nome" name="name" required>
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
