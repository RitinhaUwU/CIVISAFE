<script setup lang="ts">
import {useRoute, useRouter} from 'vue-router'
import {useApiStore} from '@/stores/api'
import {useAuthStore} from '@/stores/auth'
import * as z from "zod";
import type {BreadcrumbItem} from "@nuxt/ui/components/Breadcrumb.vue";

const route = useRoute()
const auth = useAuthStore()
const api = useApiStore()
const toast = useToast()

const saving = ref(false)

const schema = z.object({
  name: z.string().min(2, 'Nome demasiado curto'),
  email: z.string().email('Email inválido'),
  password: z.string().min(8, 'Mínimo 8 caracteres').or(z.literal('')),
  password_confirmation: z.string().or(z.literal('')),
  current_password: z.string().or(z.literal('')),
  mobile: z.string().min(9, 'Número inválido').regex(/^\+?[0-9]+(?: [0-9]+)*$/, 'Insira apenas números ou formato +000 000000000'),
  locked: z.boolean(),
  role: z.enum(['admin', 'user'], { message: 'Selecione uma opção' }),
  module_incidents: z.boolean().optional(),
  module_volunteers: z.boolean().optional(),
  module_donations: z.boolean().optional()
}).superRefine((data, ctx) => {
  if (data.password !== '') {
    if (data.password.length < 8) {
      ctx.addIssue({
        code: z.ZodIssueCode.custom,
        path: ['password'],
        message: 'Mínimo 8 caracteres'
      })
    }

    if (data.password !== data.password_confirmation) {
      ctx.addIssue({
        code: z.ZodIssueCode.custom,
        path: ['password_confirmation'],
        message: 'As palavras-passe não coincidem.'
      })
    }

    if (
      auth.currentUserID === Number(route.params.id) &&
      data.current_password === ''
    ) {
      ctx.addIssue({
        code: z.ZodIssueCode.custom,
        path: ['current_password'],
        message: 'Introduza a palavra-passe atual.'
      })
    }
  }
})

type Schema = z.output<typeof schema>

const state = reactive<Partial<Schema>>({
  name: '',
  email: '',
  password: '',
  password_confirmation: '',
  current_password: '',
  mobile: '',
  locked: false,
  role: undefined,
  module_incidents: false,
  module_volunteers: false,
  module_donations: false
})

const handleSave = async () => {
  if (auth.currentUserID === parseInt(<string>route.params.id) && !auth.hasPermission('USERS_UPDATE_OWN')) return

  if (auth.currentUserID !== parseInt(<string>route.params.id) && !auth.hasPermission('USERS_UPDATE_ANY')) return

  if (!await checkServerAccess()) {
    toast.add({
      title: 'Sem ligação à internet!',
      description: 'Não é possível guardar alterações sem estar ligado à internet. Tente novamente mais tarde',
      color: 'error'
    });
    return;
  }

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

      if (auth.currentUserID === Number(route.params.id)) {
        payload.current_password = state.current_password
      }
    }

    await api.updateUser(parseInt(<string>route.params.id), payload)

    state.current_password = ''
    state.password = ''
    state.password_confirmation = ''

    toast.add({
      title: 'Sucesso',
      description: 'Utilizador atualizado',
      color: 'success'
    })
  } catch (e: any) {
    console.log(e.response?.status)
    console.log(e.response?.data)

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
  const routeID = route.params.id;
  if (typeof routeID !== 'string') {
    toast.add({
      title: 'Utilizador inválido',
      description: 'O Caminho que o trouxe aqui aponta para um utilizador inválido',
      color: 'error'
    });
    await useRouter().push('/users');
    return;
  }

  if(!auth.hasPermission('USERS_VIEW_ANY'))
  {
    await useRouter().push('/inicio');
    return;
  }

  if(auth.currentUserID == parseInt(routeID) && !auth.hasPermission('USERS_VIEW_OWN'))
  {
    await useRouter().push('/users');
    return;
  }

  const data = (await api.getUser(parseInt(routeID))).data.data

  Object.assign(state, {
    name: data.name,
    email: data.email,
    password: '',
    password_confirmation: '',
    current_password: '',
    mobile: data.mobile,
    locked: data.locked,
    role: data.roles?.[0],
    module_incidents: data.roles.includes('module_incidents'),
    module_volunteers: data.roles.includes('module_volunteers'),
    module_donations: data.roles.includes('module_donations'),
  })
}

watch(() => state.role, (newRole) => {
  if (newRole === 'admin') {
    state.module_incidents = false
    state.module_volunteers = false
    state.module_donations = false
  }
})

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
            <UButton
              label="Guardar"
              color="primary"
              :loading="saving"
              @click="handleSave"
              :disabled="!auth.hasPermission('USERS_UPDATE_OWN')"
            />
          </div>
        </div>
      </div>
    </header>
    <div class="flex-1 overflow-y-auto px-6 sm:px-8 py-8 space-y-8">
      <UBreadcrumb :items="items"/>
      <UFormField label="Ativo?" name="locked">
        <div class="flex items-center gap-3">
          <USwitch
            :model-value="!state.locked"
            @update:model-value="(val: boolean) => state.locked = !val"
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
            <UInput v-model="state.name" class="w-full"/>
          </UFormField>
          <UFormField label="Email" name="email">
            <UInput v-model="state.email" class="w-full"/>
          </UFormField>
          <UFormField label="Telemóvel" name="mobile">
            <UInput v-model="state.mobile" class="w-full"/>
          </UFormField>
          <UFormField label="Função" name="role">
            <USelect
              v-model="state.role"
              :items="[
                { label: 'Administrador', value: 'admin' },
                { label: 'Utilizador', value: 'user' }
              ]"
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

      </section>
      <section class="space-y-3">
        <h2 class="font-bold">Alterar Palavra-Passe</h2>
        <div class="gap-4">
          <UFormField v-if="auth.currentUserID === Number(route.params.id)" label="Palavra-Passe Atual">
            <UInput
              v-model="state.current_password"
              type="password"
              class="w-full"
            />
          </UFormField>
        </div>
        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
          <UFormField label="Nova Palavra-Passe">
            <UInput v-model="state.password" type="password" class="w-full"/>
          </UFormField>
          <UFormField label="Confirmar Palavra-Passe">
            <UInput v-model="state.password_confirmation" type="password" class="w-full"/>
          </UFormField>
        </div>
      </section>
    </div>
  </div>
</template>

<style scoped>

</style>
