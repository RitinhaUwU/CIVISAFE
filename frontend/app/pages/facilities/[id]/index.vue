<script setup lang="ts">
import { useRoute } from 'vue-router'
import { useApiStore } from '@/stores/api'
import * as z from "zod";
import type {BreadcrumbItem} from "@nuxt/ui/components/Breadcrumb.vue";

const route = useRoute()
const api = useApiStore()

const saving = ref(false)

const schema = z.object({
  name: z.string().min(1, 'Nome é obrigatório'),
  contact: z.string().min(9, 'Número inválido').regex(/^\+?[0-9]+(?: [0-9]+)*$/, 'Insira apenas números ou formato +000 000000000').optional().nullable(),
  email: z.string().email('Email inválido').optional().nullable(),
  address: z.string().optional().nullable(),
  image: z.string().optional().nullable(),
  description: z.string().optional().nullable()
})

type Schema = z.output<typeof schema>

const state = reactive<Partial<Schema>>({
  name: '',
  email: '',
  contact: '',
  address: '',
  image: '',
  description: ''
})

const toast = useToast()

const fetchFacility = async () => {
  const routeID = route.params.id;
  if (typeof routeID !== 'string') {
    toast.add({
      title: 'Instalação inválida',
      description: 'O Caminho que o trouxe aqui aponta para uma instalação inválida',
      color: 'error'
    });
    await useRouter().push('/facilities');
    return;
  }

  const data = (await api.getFacility(parseInt(routeID))).data.data

  Object.assign(state, data)
}

const handleSave = async () => {
  if (!useAuthStore().hasPermission('FACILITIES_UPDATE')) return

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
    await api.updateFacility(route.params.id, state)

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

const items = ref<BreadcrumbItem[]>([
  {
    label: 'Instalações',
    icon: 'i-lucide-building-2',
    to: '/facilities'
  },
  {
    label: 'Dados da Instalação',
    icon: 'i-lucide-building',
  }
])

onMounted(() => {
  fetchFacility()
})
</script>

<template>
  <div class="min-h-screen flex flex-col">
    <header class="border-b border-stone-200 dark:border-stone-800">
      <div class="px-6 sm:px-8 py-6">
        <p class="text-[0.65rem] font-bold uppercase tracking-[0.15em] text-stone-400 mb-1">
          Instalação
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
      <div class="grid grid-cols-1 lg:grid-cols-[1fr_260px] gap-8">
        <div class="space-y-6">
          <section class="space-y-2">
            <h2 class="font-bold">Dados Gerais</h2>
            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
              <UFormField label="Nome" class="sm:col-span-2">
                <UInput v-model="state.name" class="w-full" />
              </UFormField>
              <UFormField label="Email">
                <UInput v-model="state.email" class="w-full" />
              </UFormField>
              <UFormField label="Telefone">
                <UInput v-model="state.contact" class="w-full" />
              </UFormField>
              <UFormField label="Sede" class="sm:col-span-2">
                <UInput v-model="state.address" class="w-full" />
              </UFormField>
            </div>
          </section>
          <div class="h-px border-t border-stone-200 dark:border-stone-800" />
          <section class="space-y-2">
            <h2 class="font-bold">Descrição</h2>
            <UTextarea v-model="state.description" :rows="5" class="w-full" />
          </section>
          <div class="h-px border-t border-stone-200 dark:border-stone-800" />
          <section class="space-y-2">
            <h2 class="font-bold">Ficheiros:</h2>
            <UButton class="rounded-full" icon="i-lucide-plus" color="primary" />
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
</template>

<style scoped>

</style>
