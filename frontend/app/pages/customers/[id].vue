<script setup lang="ts">
import { useRoute, useRouter } from 'vue-router'
import { useApiStore } from '@/stores/api'

const route = useRoute()
const router = useRouter()
const api = useApiStore()
const toast = useToast()

const saving = ref(false)

const state = reactive({
  name: '',
  email: '',
  mobile: '',
  locked: false,
  role: 'user'
})

const handleSave = async () => {
  saving.value = true
  try {
    await api.updateUser(route.params.id, state)

    toast.add({
      title: 'Sucesso',
      description: 'Utilizador atualizada',
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

const handleCancel = () => {
  router.back()
}

const fetchUser = async () => {
  const res = await api.getUser(route.params.id)
  const data = res.data.data

  Object.assign(state, {
    name: data.name,
    email: data.email,
    mobile: data.mobile,
    locked: data.locked,
    role: data.roles?.[0]
  })
}

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
          <h1 class="text-2xl sm:text-3xl font-bold tracking-tight">
            {{ state.name }}
          </h1>
          <div class="flex items-center gap-2">
            <UButton label="Voltar" color="neutral" variant="subtle" @click="handleCancel" />
            <UButton label="Guardar" color="primary" :loading="saving" @click="handleSave" />
          </div>
        </div>
      </div>
    </header>
    <div class="flex-1 overflow-y-auto px-6 sm:px-8 py-8 space-y-8">
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
    </div>
  </div>
</template>

<style scoped>

</style>
