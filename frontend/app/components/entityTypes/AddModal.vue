<script setup lang="ts">
import * as z from 'zod'
import type { FormSubmitEvent } from '@nuxt/ui'
import { useApiStore } from '../../stores/api'
import {useAuthStore} from "~/stores/auth";

const apiStore = useApiStore()
const open = ref(false)
const emit = defineEmits(['created'])

const toast = useToast()

const schema = z.object({
  name: z.string().min(1, 'Nome é obrigatório'),
  description: z.string().optional().nullable()
})

type Schema = z.output<typeof schema>

const state = reactive<Partial<Schema>>({
  name: '',
  description: ''
})

async function onSubmit(event: FormSubmitEvent<Schema>) {
  if (!useAuthStore().hasPermission('ENTITY_TYPES_CREATE')) return;

  if (!await checkServerAccess()) {
    useToast().add({
      title: 'Sem ligação à internet!',
      description: 'Não é possível guardar alterações sem estar ligado à internet. Tente novamente mais tarde',
      color: 'error'
    });
    return;
  }

  try {
    await apiStore.createEntityType(event.data)

    emit('created')
    open.value = false
    toast.add({
      title: 'Sucesso',
      description: 'Tipo de Entidade criado com sucesso',
      color: 'success'
    })

    Object.assign(state, {
      name: '',
      description: ''
    })
  } catch (e: any) {
    toast.add({
      title: 'Erro',
      description: 'Erro ao criar o tipo de entidade',
      color: 'error'
    })
  }
}
</script>

<template>
  <UModal v-model:open="open" title="Novo Tipo de Entidade" description="Adicione um Novo Tipo de Entidade">
    <UButton icon="i-lucide-plus" label="Novo Tipo de Entidade" color="primary" @click="open = true"/>
    <template #body>
      <UForm :state="state" :schema="schema" class="space-y-5" @submit="onSubmit">
        <UFormField label="Nome:" name="name" required>
          <UInput v-model="state.name" class="w-full" />
        </UFormField>
        <UFormField label="Observações:" name="description">
          <UTextarea v-model="state.description" class="w-full" />
        </UFormField>

        <div class="col-span-1 lg:col-span-2 flex justify-between gap-2">
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
