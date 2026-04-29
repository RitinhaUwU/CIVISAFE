<script setup lang="ts">
import { useRoute } from 'vue-router'
import { useApiStore } from '@/stores/api'
import * as z from "zod";

const route = useRoute()
const router = useRouter()
const api = useApiStore()

const saving = ref(false)

const schema = z.object({
  name: z.string().min(1, 'Nome é obrigatório'),
  description: z.string().optional().nullable()
})

type Schema = z.output<typeof schema>

const state = reactive<Partial<Schema>>({
  name: '',
  description: ''
})

const toast = useToast()

const fetchEntityType = async () => {
  const res = await api.getEntityType(route.params.id)

  Object.assign(state, res.data.data)
}

const handleSave = async () => {
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
    await api.updateEntityType(route.params.id, state)

    toast.add({
      title: 'Sucesso',
      description: 'Tipo de Entidade atualizada',
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

const items = ref<BreadcrumbItem[]>([
  {
    label: 'Tipos de Entidade',
    icon: 'i-lucide-building-2',
    to: '/administration/entityTypes'
  },
  {
    label: 'Dados do Tipo de Entidade',
    icon: 'i-lucide-building',
  }
])

onMounted(fetchEntityType)
</script>

<template>
  <div class="min-h-screen flex flex-col">
    <header class="border-b border-stone-200 dark:border-stone-800">
      <div class="px-6 sm:px-8 py-6">
        <p class="text-[0.65rem] font-bold uppercase tracking-[0.15em] text-stone-400 mb-1">
          Tipo de Entidade
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
      <div class="grid grid-cols-1 gap-8">
        <div class="space-y-6">
          <section class="space-y-2">
            <h2 class="font-bold">Dados Gerais</h2>
            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
              <UFormField label="Nome" class="sm:col-span-2">
                <UInput v-model="state.name" class="w-full" />
              </UFormField>
              <UFormField label="Descrição" class="sm:col-span-2">
                <UInput v-model="state.description" class="w-full" />
              </UFormField>
            </div>
          </section>
        </div>
      </div>
    </div>
  </div>
</template>

<style scoped>

</style>
