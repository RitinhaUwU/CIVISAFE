<script setup lang="ts">
import { useAuthStore } from '@/stores/auth'
import { useApiStore } from '@/stores/api'
import z from "zod";

const auth = useAuthStore()
const api = useApiStore()
const toast = useToast()

const schema = z.object({
  current_password: z.string().min(1, 'Introduza a palavra-passe atual'),
  password: z.string().min(8, 'Mínimo 8 caracteres'),
  password_confirmation: z.string()
}).refine((data) => data.password === data.password_confirmation, {
    message: 'As palavras-passe não coincidem.',
    path: ['password_confirmation']
  }
)

const saving = ref(false)

const state = reactive({
  current_password: '',
  password: '',
  password_confirmation: ''
})

onMounted(async () => {
  if (!auth.currentUser) {
    await auth.getUser()
  }

  Object.assign(state, {
    current_password: '',
    password: '',
    password_confirmation: ''
  })
})

const user = computed(() => auth.currentUser)

const handleSave = async () => {
  if (!auth.hasPermission('USERS_UPDATE_OWN')) return

  if (!await checkServerAccess()) {
    toast.add({
      title: 'Sem ligação à internet!',
      description: 'Não é possível guardar alterações sem estar ligado à internet. Tente novamente mais tarde',
      color: 'error'
    });
    return;
  }

  saving.value = true

  try {
    const payload = {
      current_password: state.current_password,
      password: state.password,
      password_confirmation: state.password_confirmation
    }

    await api.patchUser(user.value.id, payload)

    await auth.getUser()

    toast.add({
      title: 'Perfil atualizado',
      color: 'success'
    })

    state.current_password = ''
    state.password = ''
    state.password_confirmation = ''
  } catch (e: any) {
    if (e.response?.data?.errors?.current_password) {
      toast.add({
        title: 'A palavra-passe atual está incorreta.',
        color: 'error'
      })
      return
    }

    toast.add({
      title: 'Erro ao atualizar perfil',
      color: 'error'
    })
  } finally {
    saving.value = false
  }
}
</script>

<template>
  <UDashboardPanel id="security" class="min-h-0">
    <UPageCard variant="subtle">
      <div class="flex flex-col">
        <header class="border-b border-stone-200 dark:border-stone-800">
          <div class="px-6 sm:px-8 py-6">
            <div class="flex items-center justify-between w-full gap-4">
              <h1 class="text-2xl sm:text-3xl font-bold tracking-tight">
                Alterar Palavra-Passe
              </h1>
            </div>
          </div>
        </header>
        <UForm :schema="schema" :state="state" @submit="handleSave">
          <div class="px-6 sm:px-8 py-8 space-y-8">
            <UFormField label="Palavra-Passe Atual">
              <UInput v-model="state.current_password" type="password" class="w-full" />
            </UFormField>
            <UFormField label="Nova Palavra-Passe">
              <UInput v-model="state.password" type="password" class="w-full" />
            </UFormField>
            <UFormField label="Confirmar Palavra-Passe">
              <UInput v-model="state.password_confirmation" type="password" class="w-full" />
            </UFormField>
          </div>
          <div class="px-6 sm:px-8 space-y-8 flex justify-end">
            <UButton
              type="submit"
              label="Guardar"
              color="primary"
              :loading="saving"
              :disabled="!auth.hasPermission('USERS_UPDATE_OWN')"
            />
          </div>
        </UForm>
      </div>
    </UPageCard>
  </UDashboardPanel>
</template>

<style scoped>

</style>
