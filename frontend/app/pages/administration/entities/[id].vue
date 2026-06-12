<script setup lang="ts">
import {useRoute, useRouter} from 'vue-router'
import { useApiStore } from '@/stores/api'
import { useAuthStore } from '@/stores/auth'
import * as z from "zod";
import type {BreadcrumbItem} from "@nuxt/ui/components/Breadcrumb.vue";
import {usePaginatedSelect} from "@/composables/usePaginatedSelect";

const route = useRoute()
const api = useApiStore()

const saving = ref(false)

const entityTypesMenu = useTemplateRef('entityTypesMenu')

const entityTypes = usePaginatedSelect({
  fetcher: api.getEntityTypes,
  menuRef: entityTypesMenu,
  map: (i: any) => ({
    id: i.id,
    name: i.name
  })
})

const schema = z.object({
  name: z.string().min(1, 'Nome é obrigatório'),
  phone_contact: z.string().min(9, 'Número inválido').regex(/^\+?[0-9]+(?: [0-9]+)*$/, 'Insira apenas números ou formato +000 000000000').optional().nullable(),
  email_contact: z.string().email('Email inválido').optional().nullable(),
  address: z.string().optional().nullable(),
  logo: z.string().optional().nullable(),
  poc_name: z.string().optional().nullable(),
  poc_phone: z.string().min(9, 'Número inválido').regex(/^\+?[0-9]+(?: [0-9]+)*$/, 'Insira apenas números ou formato +000 000000000').optional().nullable(),
  poc_email: z.string().email('Email inválido').optional().nullable(),
  description: z.string().optional().nullable(),
  entity_type_id: z.number().nullable()
})

type Schema = z.output<typeof schema>

const state = reactive<Partial<Schema & { entity_type_id: number|null }>>({
  name: '',
  email_contact: '',
  phone_contact: '',
  address: '',
  poc_name: '',
  poc_email: '',
  poc_phone: '',
  description: '',
  entity_type_id: null,
})

const toast = useToast()

const fetchEntity = async () => {
  const routeID = route.params.id;
  if (typeof routeID !== 'string') {
    toast.add({
      title: 'Entidade inválida',
      description: 'O Caminho que o trouxe aqui aponta para uma entidade inválida',
      color: 'error'
    });
    await useRouter().push('/entities');
    return;
  }

  const data = (await api.getEntity(parseInt(routeID))).data.data

  Object.assign(state, {
    ...data,
    entity_type_id: data.entityType?.id,
  })

  if (data.entityType) {
    entityTypes.prependSelected([{
      id: data.entityType.id,
      name: data.entityType.name
    }])
  }
}

const handleSave = async () => {

  if (!useAuthStore().hasPermission('ENTITIES_UPDATE')) return

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
    await api.updateEntity(parseInt(<string>route.params.id), state)

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
    label: 'Entidades',
    icon: 'i-lucide-building-2',
    to: '/administration/entities'
  },
  {
    label: 'Dados da Entidade',
    icon: 'i-lucide-building',
  }
])

onMounted(() => {
  if(!useAuthStore().hasPermission('ENTITIES_LIST')) {
    useRouter().push('/inicio');
    return;
  }

  fetchEntity()
  entityTypes.fetchItems()
})
</script>

<template>
  <div class="min-h-screen flex flex-col">
    <header class="border-b border-stone-200 dark:border-stone-800">
      <div class="px-6 sm:px-8 py-6">
        <p class="text-[0.65rem] font-bold uppercase tracking-[0.15em] text-stone-400 mb-1">
          Entidade
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
              :disabled="!useAuthStore().hasPermission('ENTITY_UPDATE')"
            />
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
              <UFormField label="Tipo" class="sm:col-span-2">
                <USelectMenu
                  ref="entityTypesMenu"
                  v-model="state.entity_type_id"
                  v-model:search-term="entityTypes.search.value"
                  :items="entityTypes.items.value"
                  :loading="entityTypes.loading.value"
                  value-key="id"
                  label-key="name"
                  ignore-filter
                  class="w-full"
                  placeholder="Selecionar tipo"
                />
              </UFormField>
              <UFormField label="Nome da Entidade" class="sm:col-span-2">
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
              <UFormField label="Nome do Responsável" class="sm:col-span-2">
                <UInput v-model="state.poc_name" data-testid="entity-name-input" class="w-full" />
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
</template>

<style scoped>

</style>
