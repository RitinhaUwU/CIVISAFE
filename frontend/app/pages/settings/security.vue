<script setup lang="ts">
import { useAuthStore } from '@/stores/auth'
import { useApiStore } from '@/stores/api'

const auth = useAuthStore()
const api = useApiStore()
const toast = useToast()

const saving = ref(false)

const state = reactive({
  name: '',
  email: '',
  mobile: '',
  locked: false,
  role: '',
  password: '',
  password_confirmation: ''
})

onMounted(async () => {
  if (!auth.currentUser) {
    await auth.getUser()
  }

  const user = auth.currentUser

  Object.assign(state, {
    name: user?.name,
    email: user?.email,
    mobile: user?.mobile,
    locked: user?.locked,
    role: user?.roles?.[0] ?? '',
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
    const payload: any = {
      name: state.name,
      email: state.email,
      mobile: state.mobile
    }

    if (state.password) {
      payload.password = state.password
      payload.password_confirmation = state.password_confirmation
    }

    await api.patchUser(user.value.id, payload)

    await auth.getUser()

    toast.add({
      title: 'Perfil atualizado',
      color: 'success'
    })

    state.password = ''
    state.password_confirmation = ''
  } catch (e) {
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
        <div class="px-6 sm:px-8 py-8 space-y-8">
          <UFormField label="Nova Palavra-Passe">
            <UInput v-model="state.password" type="password" class="w-full" />
          </UFormField>
          <UFormField label="Confirmar Palavra-Passe">
            <UInput v-model="state.password_confirmation" type="password" class="w-full" />
          </UFormField>
        </div>
      </div>
    </UPageCard>
  </UDashboardPanel>
</template>

<style scoped>

</style>
