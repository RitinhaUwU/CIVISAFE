<script setup lang="ts">
import * as z from 'zod'
import type { FormSubmitEvent } from '@nuxt/ui'
import { useApiStore } from '../../stores/api'
import {usePaginatedSelect} from "~/composables/usePaginatedSelect";

const api = useApiStore()
const open = ref(false)
const emit = defineEmits(['created'])

const toast = useToast()

const entityTypesMenu = useTemplateRef('entityTypesMenu')

const entityTypes = usePaginatedSelect({
  fetcher: api.getEntityTypes,
  menuRef: entityTypesMenu,
  map: (i: any) => ({
    id: i.id,
    name: i.name
  })
})

const imageFile = ref(null)

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

const state = reactive<Partial<Schema & { entity_type_id: number }>>({
  name: '',
  phone_contact: '',
  email_contact: '',
  address: '',
  logo: '',
  poc_name: '',
  poc_phone: '',
  poc_email: '',
  description: '',
  entity_type_id: null,
})

async function onSubmit(event: FormSubmitEvent<Schema>) {
  try {
    await api.createEntity(event.data)

    emit('created')
    open.value = false

    toast.add({
      title: 'Sucesso',
      description: 'Entidade criada com sucesso',
      color: 'success'
    })

    Object.assign(state, {
      name: '',
      phone_contact: '',
      email_contact: '',
      address: '',
      logo: '',
      poc_name: '',
      poc_phone: '',
      poc_email: '',
      description: '',
      entity_type_id: null as number,
    })
  } catch (e: any) {
    toast.add({
      title: 'Erro',
      description: 'Erro ao criar entidade',
      color: 'error'
    })
  }
}

onMounted(async () => {
  await entityTypes.fetchItems()
})
</script>

<template>
  <UModal v-model:open="open" title="Nova Entidade" description="Adicione uma Nova Entidade">
    <UButton
      icon="i-lucide-plus"
      label="Nova Entidade"
      color="primary"
    />
    <template #body>
      <UForm
        :state="state"
        :schema="schema"
        @submit="onSubmit"
      >
        <UFormField name="image" class="mb-5">
          <UFileUpload
            icon="i-lucide-image"
            v-model="imageFile"
            accept="image/*"
            color="neutral"
            highlight
            label="Carregue uma imagem"
            description="SVG, PNG, JPG or GIF (max. 2MB)"
            class="w-full min-h-48 cursor-pointer bg-stone-50/40 dark:bg-stone-900/40 hover:bg-stone-100/70 dark:hover:bg-stone-800/60 transition-all duration-200 ease-out"
          />
        </UFormField>
        <div class="h-px border-t border-stone-200 dark:border-stone-800 mb-5" />
        <div class="grid grid-cols-1 lg:grid-cols-2 gap-x-8 gap-y-5">
          <div class="space-y-5">
            <UFormField label="Tipo de Entidade:" name="entity_type_id">
              <USelectMenu
                data-testid="entity-type-select"
                ref="entityTypesMenu"
                v-model="state.entity_type_id"
                v-model:search-term="entityTypes.search.value"
                :items="entityTypes.items.value"
                :loading="entityTypes.loading.value"
                value-key="id"
                label-key="name"
                ignore-filter
                placeholder="Seleciona o tipo"
                class="w-full"
              />
            </UFormField>
            <UFormField label="Nome:" name="name">
              <UInput v-model="state.name" class="w-full" />
            </UFormField>
            <UFormField label="Email:" name="email_contact">
              <UInput v-model="state.email_contact" class="w-full" />
            </UFormField>
            <UFormField label="Contacto:" name="phone_contact">
              <UInput v-model="state.phone_contact" class="w-full" />
            </UFormField>
            <UFormField label="Morada:" name="address">
              <UInput v-model="state.address" class="w-full" />
            </UFormField>
          </div>
          <div class="space-y-5">
            <UFormField label="Nome do Responsável:" name="poc_name">
              <UInput v-model="state.poc_name" class="w-full" />
            </UFormField>
            <UFormField label="Email do Responsável:" name="poc_email">
              <UInput v-model="state.poc_email" class="w-full" />
            </UFormField>
            <UFormField label="Contacto do Responsável:" name="poc_phone">
              <UInput v-model="state.poc_phone" class="w-full" />
            </UFormField>
            <UFormField label="Observações:" name="description">
              <UTextarea v-model="state.description" class="w-full" />
            </UFormField>
          </div>
          <div class="col-span-1 lg:col-span-2 flex justify-between gap-2">
            <UButton label="Cancelar" color="neutral" variant="subtle" class="flex-1 justify-center" @click="open = false"/>
            <UButton label="Guardar" color="primary" type="submit" class="flex-1 justify-center"/>
          </div>
        </div>
      </UForm>
    </template>
  </UModal>
</template>
