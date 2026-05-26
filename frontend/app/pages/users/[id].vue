<script setup lang="ts">
import { useRoute, useRouter } from 'vue-router'
import { useApiStore } from '../../stores/api'
import {useAuthStore} from '../../stores/auth'
import * as z from "zod";

const route = useRoute()
const router = useRouter()
const auth = useAuthStore()
const api = useApiStore()
const toast = useToast()

const saving = ref(false)

const schema = z.object({
  name: z.string().min(2, 'Nome demasiado curto'),
  email: z.string().email('Email inválido'),
  password: z.string().min(8, 'Mínimo 8 caracteres').optional().or(z.literal('')),
  password_confirmation: z.string().optional(),
  mobile: z.string().min(9, 'Número inválido').regex(/^\+?[0-9]+(?: [0-9]+)*$/, 'Insira apenas números ou formato +000 000000000'),
  locked: z.boolean(),
  role: z.enum(['admin', 'manager', 'user'], 'Selecione uma opção')
}).refine((data) => data.password === data.password_confirmation, {
  message: 'Passwords não coincidem',
  path: ['password_confirmation']
})

type Schema = z.output<typeof schema>

const state = reactive<Partial<Schema>>({
  name: '',
  email: '',
  mobile: '',
  locked: false,
  role: 'user',
  password: '',
  password_confirmation: ''
})

const handleSave = async () => {
  if (!auth.hasPermission('USERS_UPDATE_ANY') && !auth.hasPermission('USERS_UPDATE_OWN')) return

  const result = schema.safeParse(state)

  if (!result.success) {
    result.error.issues.forEach((err) => {
      toast.add({
        title: 'Erro de validação',
        description: err.message,
        color: 'error'
      })
    })
    return
  }

  saving.value = true
  try {
    const payload: any = {
      name: state.name,
      email: state.email,
      mobile: state.mobile,
      locked: state.locked,
      role: state.role,
    }

    if (state.password) {
      payload.password = state.password
      payload.password_confirmation = state.password_confirmation
    }

    await api.updateUser(route.params.id, payload)

    toast.add({
      title: 'Sucesso',
      description: 'Utilizador atualizado',
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

const fetchUser = async () => {
  const res = await api.getUser(route.params.id)
  const data = res.data.data

  Object.assign(state, {
    name: data.name,
    email: data.email,
    mobile: data.mobile,
    locked: data.locked,
    role: data.roles?.[0],
    password: '',
    password_confirmation: ''
  })
}

const items = ref<BreadcrumbItem[]>([
  {
    label: 'Utilizadores',
    icon: 'i-lucide-users',
    to: '/users'
  },
  {
    label: 'Dados do Utilizador',
    icon: 'i-lucide-user',
  }
])

onMounted(async () => {
  await fetchUser()
})
</script>

<template>
  <div class="min-h-screen flex flex-col">
    <header class="border-b border-stone-200 dark:border-stone-800">
      <div class="px-6 sm:px-8 py-6">
        <p class="text-[0.65rem] font-bold uppercase tracking-[0.15em] text-stone-400 mb-1">
          Utilizador
        </p>
        <div class="flex items-center justify-between w-full gap-4">
          <h1 class="text-2xl sm:text-3xl font-bold tracking-tight truncate max-w-full">
            {{ state.name }}
          </h1>
          <div class="flex items-center gap-2">
            <UButton label="Guardar" color="primary" :loading="saving" @click="handleSave" />
          </div>
        </div>
      </div>
    </header>
    <div class="flex-1 overflow-y-auto px-6 sm:px-8 py-8 space-y-8">
      <UBreadcrumb :items="items" />
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
      <section class="space-y-3">
        <h2 class="font-bold">Dados do Utilizador</h2>
        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
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
      </section>
      <section class="space-y-3">
        <h2 class="font-bold">Alterar Palavra-Passe</h2>
        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
          <UFormField label="Nova Palavra-Passe">
            <UInput v-model="state.password" type="password" class="w-full" />
          </UFormField>
          <UFormField label="Confirmar Palavra-Passe">
            <UInput v-model="state.password_confirmation" type="password" class="w-full" />
          </UFormField>
        </div>
      </section>
    </div>
  </div>
</template>

<style scoped>

</style>
