<script setup lang="ts">
import * as z from 'zod'
import type { FormSubmitEvent } from '@nuxt/ui'
import {useApiStore} from "../../stores/api";

const apiStore = useApiStore()
const open = ref(false)
const emit = defineEmits(['created'])
const users = ref([])

const toast = useToast()

const schema = z.object({
  name: z.string().min(2, 'Nome demasiado curto'),
  email: z.string().email('Email inválido'),
  password: z.string().min(8, 'Mínimo 8 caracteres'),
  password_confirmation: z.string(),
  mobile: z.string().min(9, 'Número inválido'),
  locked: z.boolean(),
  role: z.enum(['admin', 'manager', 'user'])
}).refine((data) => data.password === data.password_confirmation, {
  message: 'Passwords não coincidem',
  path: ['password_confirmation']
})

type Schema = z.output<typeof schema>

const state = reactive<Partial<Schema>>({
  name: '',
  email: '',
  password: '',
  password_confirmation: '',
  mobile: '',
  locked: false,
  role: ''
})

async function onSubmit(event: FormSubmitEvent<Schema>) {
  try {
    await apiStore.createUser(event.data)

    emit('created')
    open.value = false
    toast.add({
      title: 'Sucesso',
      description: 'Utilizador criado com sucesso',
      color: 'success'
    })

    Object.assign(state, {
      name: '',
      email: '',
      password: '',
      password_confirmation: '',
      mobile: '',
      locked: false,
      role: ''
    })
  } catch (e: any) {
    toast.add({
      title: 'Erro',
      description: 'Erro ao criar o utilizador',
      color: 'error'
    })
  }
}
</script>

<template>
  <UModal v-model:open="open" title="Novo Utilizador" description="Crie um Nova Utilizador">
    <UButton
      label="Novo Utilizador"
      icon="i-lucide-plus"
      color="primary"
    />
    <template #body>
      <UForm :state="state" :schema="schema" class="space-y-5" @submit="onSubmit">
        <UFormField label="Ativo?" name="locked">
          <div class="flex items-center gap-3">
            <USwitch
              :model-value="!state.locked"
              @update:model-value="(val) => state.locked = !val"
              checked-icon="i-lucide-check"
              unchecked-icon="i-lucide-x"
            />
            <span class="text-sm font-medium">{{ state.locked ? 'Não' : 'Sim' }}</span>
          </div>
        </UFormField>
        <div class="h-px border-t border-stone-200 dark:border-stone-800" />
        <h3 class="text-sm font-semibold text-muted">Dados do Utilizador</h3>
        <div class="grid grid-cols-1 lg:grid-cols-2 gap-3">
          <UFormField label="Nome" name="name">
            <UInput v-model="state.name" class="w-full" />
          </UFormField>
          <UFormField label="Email" name="email">
            <UInput v-model="state.email" class="w-full" />
          </UFormField>
          <UFormField label="Telemóvel" name="mobile">
            <UInput v-model="state.mobile" class="w-full" />
          </UFormField>
          <UFormField label="Função" name="role">
            <USelect
              v-model="state.role"
              :items="[
                { label: 'Administrador', value: 'admin' },
                { label: 'Gestor', value: 'manager' },
                { label: 'Utilizador', value: 'user' }
              ]"
              class="w-full"
            />
          </UFormField>
        </div>
        <div class="h-px border-t border-stone-200 dark:border-stone-800" />
        <div class="grid grid-cols-1 lg:grid-cols-2 gap-3">
          <UFormField label="Palavra-Passe" name="password">
            <UInput v-model="state.password" type="password" class="w-full" />
          </UFormField>
          <UFormField label="Confirmar Palavra-Passe" name="password_confirmation">
            <UInput v-model="state.password_confirmation" type="password" class="w-full" />
          </UFormField>
        </div>
        <div class="col-span-1 lg:col-span-2 flex justify-between gap-2">
          <UButton label="Cancelar" color="neutral" variant="subtle" class="flex-1 justify-center" @click="open = false"/>
          <UButton label="Guardar" color="primary" type="submit" class="flex-1 justify-center"/>
        </div>
      </UForm>
    </template>
  </UModal>
</template>
