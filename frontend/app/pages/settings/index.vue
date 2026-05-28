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
  role: ''
})

onMounted(async () => {
  if (!auth.currentUser) {
    await auth.getUser()
  }

  Object.assign(state, {
    name: auth.currentUser?.name,
    email: auth.currentUser?.email,
    mobile: auth.currentUser?.mobile,
    locked: auth.currentUser?.locked,
    role: auth.currentUser?.roles?.[0]
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
    await api.patchUser(user.value.id, {
      name: state.name,
      email: state.email,
      mobile: state.mobile
    })

    await auth.getUser()

    toast.add({
      title: 'Perfil atualizado',
      color: 'success'
    })
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
  <UDashboardPanel id="profile" class="min-h-0">
    <UPageCard variant="subtle">
      <div class="flex flex-col">
        <header class="border-b border-stone-200 dark:border-stone-800">
          <div class="px-6 sm:px-8 py-6">
            <p class="text-[0.65rem] font-bold uppercase tracking-[0.15em] text-stone-400 mb-1">Perfil</p>
            <div class="flex items-center justify-between w-full gap-4">
              <div class="flex items-center items-center gap-4">
                <h1 class="text-2xl sm:text-3xl font-bold tracking-tight">
                  {{ state.name }}
                </h1>
                <UBadge class="capitalize rounded-full" variant="subtle">
                  {{ state.role }}
                </UBadge>
              </div>
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
          <UFormField label="Nome">
            <UInput v-model="state.name" class="w-full" />
          </UFormField>
          <UFormField label="Email">
            <UInput v-model="state.email" class="w-full" />
          </UFormField>
          <UFormField label="Telemóvel">
            <UInput v-model="state.mobile" class="w-full" />
          </UFormField>
        </div>
      </div>
    </UPageCard>
  </UDashboardPanel>
</template>

<style scoped>

</style>
