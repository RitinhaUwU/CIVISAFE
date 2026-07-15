<script setup lang="ts">
import * as z from 'zod'
import type { FormSubmitEvent } from '@nuxt/ui'
import {useApiStore} from "@/stores/api";

const apiStore = useApiStore()
const open = ref(false)
const emit = defineEmits(['created'])

const toast = useToast()

const schema = z.object({
  name: z.string().min(2, 'Nome demasiado curto'),
  email: z.string().email('Email inválido'),
  password: z.string().min(8, 'Mínimo 8 caracteres'),
  password_confirmation: z.string(),
  mobile: z.string().min(9, 'Número inválido').regex(/^\+?[0-9]+(?: [0-9]+)*$/, 'Insira apenas números ou formato +000 000000000'),
  locked: z.boolean(),
  role: z.enum(['admin', 'user'], { message: 'Selecione uma opção' }),
  module_incidents: z.boolean().optional(),
  module_volunteers: z.boolean().optional(),
  module_donations: z.boolean().optional()
})
  .refine((data) => data.password === data.password_confirmation, {
    message: 'Passwords não coincidem',
    path: ['password_confirmation']
  })

type Schema = z.infer<typeof schema>

const state = reactive<Partial<Schema>>({
  name: '',
  email: '',
  password: '',
  password_confirmation: '',
  mobile: '',
  locked: false,
  role: undefined,
  module_incidents: false,
  module_volunteers: false,
  module_donations: false
})

watch(() => state.role, (newRole) => {
  if (newRole === 'admin') {
    state.module_incidents = false
    state.module_volunteers = false
    state.module_donations = false
  }
})

async function onSubmit(event: FormSubmitEvent<Schema>) {
  if (!await checkServerAccess()) {
    useToast().add({
      title: 'Sem ligação à internet!',
      description: 'Não é possível guardar alterações sem estar ligado à internet. Tente novamente mais tarde',
      color: 'error'
    });
    return;
  }

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
      role: undefined,
      module_incidents: false,
      module_volunteers: false,
      module_donations: false
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
  <UModal
    v-model:open="open"
    title="Novo Utilizador"
    description="Criar Utilizador"
    :ui="{
      content: 'max-h-[90vh] overflow-y-auto w-full max-w-3xl'
    }"
  >
    <UButton label="Novo Utilizador" icon="i-lucide-plus" color="primary"/>
    <template #body>
      <UForm :state="state" :schema="schema" class="space-y-4" @submit="onSubmit">
        <UFormField label="Ativo?" name="locked" required>
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
          <UFormField label="Nome" name="name" required>
            <UInput v-model="state.name" class="w-full" />
          </UFormField>
          <UFormField label="Email" name="email" required>
            <UInput v-model="state.email" class="w-full" />
          </UFormField>
          <UFormField label="Telemóvel" name="mobile" required>
            <UInput v-model="state.mobile" class="w-full" />
          </UFormField>
          <UFormField label="Função" name="role" required>
            <USelect
              v-model="state.role"
              :items="[
                { label: 'Administrador', value: 'admin' },
                { label: 'Utilizador', value: 'user' }
              ]"
              placeholder="Selecione uma Função"
              class="w-full"
            />
          </UFormField>
        </div>

        <template v-if="state.role === 'user'">
          <div class="h-px border-t border-stone-200 dark:border-stone-800" />
          <h3 class="text-sm font-semibold text-muted">Acesso a Módulos</h3>
          <div class="grid grid-cols-1 lg:grid-cols-2 gap-3">
            <UFormField label="Módulo de Gestão de Ocorrências" name="incidents">
              <div class="flex items-center gap-3">
                <USwitch
                  checked-icon="i-lucide-check"
                  unchecked-icon="i-lucide-x"
                  v-model="state.module_incidents"
                />
                <span class="text-sm font-medium">{{ state.module_incidents ? 'Sim' : 'Não' }}</span>
              </div>
            </UFormField>

            <UFormField label="Módulo de Gestão de Voluntários" name="volunteers">
              <div class="flex items-center gap-3">
                <USwitch
                  checked-icon="i-lucide-check"
                  unchecked-icon="i-lucide-x"
                  v-model="state.module_volunteers"
                />
                <span class="text-sm font-medium">{{ state.module_volunteers ? 'Sim' : 'Não' }}</span>
              </div>
            </UFormField>

            <UFormField label="Módulo de Gestão de Doações" name="donations">
              <div class="flex items-center gap-3">
                <USwitch
                  checked-icon="i-lucide-check"
                  unchecked-icon="i-lucide-x"
                  v-model="state.module_donations"
                />
                <span class="text-sm font-medium">{{ state.module_donations ? 'Sim' : 'Não' }}</span>
              </div>
            </UFormField>
          </div>
        </template>

        <div class="h-px border-t border-stone-200 dark:border-stone-800" />
        <div class="grid grid-cols-1 lg:grid-cols-2 gap-3">
          <UFormField label="Palavra-Passe" name="password" required>
            <UInput v-model="state.password" type="password" data-testid="password" class="w-full" />
          </UFormField>
          <UFormField label="Confirmar Palavra-Passe" name="password_confirmation" required>
            <UInput v-model="state.password_confirmation" type="password" data-testid="password-confirmation" class="w-full" />
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
