<script setup lang="ts">
import { useRoute } from 'vue-router'
import { useApiStore } from '@/stores/api'

const route = useRoute()
const router = useRouter()
const api = useApiStore()

const saving = ref(false)

const state = reactive({
  code: '',
  species: '',
  type: '',
  description: '',
  is_active: '',
  created_at: '',
  updated_at: ''
})

const toast = useToast()

const fetchEntity = async () => {
  const res = await api.getIncidentType(route.params.code)

  Object.assign(state, res.data.data)
}

const handleSave = async () => {
  saving.value = true
  try {
    await api.updateIncidentType(route.params.code, state)

    toast.add({
      title: 'Sucesso',
      description: 'Tipo de incidente atualizada',
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

onMounted(fetchEntity)
</script>

<template>
  <div class="min-h-screen flex flex-col">
    <header class="border-b border-stone-200 dark:border-stone-800">
      <div class="px-6 sm:px-8 py-6">
        <p class="text-[0.65rem] font-bold uppercase tracking-[0.15em] text-stone-400 mb-1">
          Tipo de Entidade
        </p>
        <div class="flex items-center justify-between w-full gap-4">
          <h1 class="text-2xl sm:text-3xl font-bold tracking-tight text-stone-900 dark:text-stone-50">
            {{ state.type }}
          </h1>
          <div class="flex items-center gap-2">
            <UButton label="Voltar" color="neutral" variant="subtle" @click="handleCancel" />
            <UButton label="Guardar" color="primary" :loading="saving" @click="handleSave" />
          </div>
        </div>
      </div>
    </header>
    <div class="flex-1 flex flex-col min-h-0">
      <div class="flex-1 overflow-y-auto px-6 sm:px-8 py-8 pb-8">
        <div class="grid grid-cols-1 gap-8">
          <div class="space-y-6">
            <section class="space-y-2">
              <h2 class="font-bold">Dados Gerais</h2>
              <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                <UFormField label="Código" class="sm:col-span-2">
                  <UInput v-model="state.code" class="w-full" />
                </UFormField>
                <UFormField label="Espécie" class="sm:col-span-2">
                  <UInput v-model="state.species" class="w-full" />
                </UFormField>
                <UFormField label="Tipo" class="sm:col-span-2">
                  <UInput v-model="state.type" class="w-full" />
                </UFormField>
                <USwitch
                  v-model="state.is_active"
                  label="Está ativo?"
                  unchecked-icon="i-lucide-x"
                  checked-icon="i-lucide-check"
                />
              </div>
            </section>
            <div class="h-px border-t border-stone-200 dark:border-stone-800" />
            <section class="space-y-2">
              <h2 class="font-bold">Descrição</h2>
              <UTextarea v-model="state.description" :rows="5" class="w-full" />
            </section>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>

<style scoped>

</style>
