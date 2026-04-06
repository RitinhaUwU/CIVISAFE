<script setup lang="ts">
import { useRoute } from 'vue-router'
import { useApiStore } from '@/stores/api'

const route = useRoute()
const router = useRouter()
const api = useApiStore()

const saving = ref(false)

const state = reactive({
  name: '',
  email_contact: '',
  phone_contact: '',
  address: '',
  poc_name: '',
  poc_email: '',
  poc_phone: '',
  description: ''
})

const toast = useToast()

const fetchEntity = async () => {
  const res = await api.getEntity(route.params.id)

  Object.assign(state, res.data.data)
}

const handleSave = async () => {
  saving.value = true
  try {
    await api.updateEntity(route.params.id, state)

    toast.add({
      title: 'Sucesso',
      description: 'Entidade atualizada',
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
          Entidade
        </p>
        <div class="flex items-center justify-between w-full gap-4">
          <h1 class="text-2xl sm:text-3xl font-bold tracking-tight text-stone-900 dark:text-stone-50">
            {{ state.name }}
          </h1>
          <div class="flex items-center gap-2">
            <UButton label="Cancelar" color="neutral" variant="subtle" @click="handleCancel" />
            <UButton label="Guardar" color="primary" :loading="saving" @click="handleSave" />
          </div>
        </div>
      </div>
    </header>
    <div class="flex-1 flex flex-col min-h-0">
      <div class="flex-1 overflow-y-auto px-6 sm:px-8 py-8 pb-8">
        <div class="grid grid-cols-1 lg:grid-cols-[1fr_260px] gap-8">
          <div class="space-y-6">
            <section class="space-y-2">
              <h2 class="font-bold">Dados Gerais</h2>
              <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                <UFormField label="Nome" class="sm:col-span-2">
                  <UInput v-model="state.name" class="w-full" />
                </UFormField>

                <UFormField label="Email de contacto">
                  <UInput v-model="state.email_contact" class="w-full" />
                </UFormField>

                <UFormField label="Telefone">
                  <UInput v-model="state.phone_contact" class="w-full" />
                </UFormField>

                <UFormField label="Morada" class="sm:col-span-2">
                  <UInput v-model="state.address" class="w-full" />
                </UFormField>
              </div>
            </section>
            <div class="h-px border-t border-stone-200 dark:border-stone-800" />
            <section class="space-y-2">
              <h2 class="font-bold">Responsável</h2>
              <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                <UFormField label="Nome completo" class="sm:col-span-2">
                  <UInput v-model="state.poc_name" class="w-full" />
                </UFormField>

                <UFormField label="Email">
                  <UInput v-model="state.poc_email" class="w-full" />
                </UFormField>

                <UFormField label="Telefone">
                  <UInput v-model="state.poc_phone" class="w-full" />
                </UFormField>
              </div>
            </section>
            <div class="h-px border-t border-stone-200 dark:border-stone-800" />
            <section class="space-y-2">
              <h2 class="font-bold">Descrição</h2>
              <UTextarea v-model="state.description" :rows="5" class="w-full" />
            </section>
          </div>
          <div class="hidden lg:flex flex-col items-center justify-start gap-6 pt-1">
            <div class="sticky top-8 flex flex-col items-center gap-5 w-full text-center">
              <div class="relative flex items-center justify-center w-32 h-32">
                <img src="" class="h-10 w-auto object-contain" alt="Logo" />
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>

<style scoped>

</style>
