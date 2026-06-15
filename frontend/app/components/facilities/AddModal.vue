<script setup lang="ts">
import * as z from 'zod'
import type { FormSubmitEvent } from '@nuxt/ui'
import { useApiStore } from '~/stores/api'

const api = useApiStore()
const open = ref(false)
const emit = defineEmits(['created'])

const toast = useToast()

const imageFile = ref(null)

const schema = z.object({
  name: z.string().min(1, 'Nome é obrigatório'),
  contact: z.string().min(9, 'Número inválido').regex(/^\+?[0-9]+(?: [0-9]+)*$/, 'Insira apenas números ou formato +000 000000000').optional().nullable(),
  email: z.string().email('Email inválido').optional().nullable(),
  address: z.string().optional().nullable(),
  image: z.any().optional(),
  description: z.string().optional().nullable(),
})

type Schema = z.output<typeof schema>

const state = reactive<Partial<Schema>>({
  name: '',
  email: '',
  address: '',
  contact: '',
  description: '',
})

async function onSubmit(event: FormSubmitEvent<Schema>) {
  try {
    await api.createFacility(event.data)

    emit('created')
    open.value = false

    toast.add({
      title: 'Sucesso',
      description: 'Instalação criada com sucesso',
      color: 'success'
    })

    Object.assign(state, {
      name: '',
      email: '',
      address: '',
      contact: '',
      description: '',
    })
  }
  catch (e: any) {
    toast.add({
      title: 'Erro',
      description: 'Erro ao criar instalação',
      color: 'error'
    })
  }
}
</script>

<template>
  <UModal v-model:open="open" title="Nova Intalação" description="Adicione uma Nova Instalação">
    <UButton icon="i-lucide-plus" label="Nova Instalação" color="primary"/>
    <template #body>
      <UForm :state="state" :schema="schema" class="space-y-5" @submit="onSubmit">
        <UFormField name="image">
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
        <UFormField label="Nome" name="name" required>
          <UInput v-model="state.name" class="w-full" />
        </UFormField>
        <UFormField label="Email" name="email">
          <UInput v-model="state.email" class="w-full" />
        </UFormField>
        <UFormField label="Telefone" name="contact">
          <UInput v-model="state.contact" class="w-full" />
        </UFormField>
        <UFormField label="Sede" name="address">
          <UInput v-model="state.address" class="w-full" />
        </UFormField>
        <UFormField label="Observações" name="description">
          <UTextarea v-model="state.description" class="w-full" />
        </UFormField>
        <div class="flex justify-between gap-3 pt-2">
          <UButton
            label="Cancelar"
            color="neutral"
            variant="subtle"
            class="flex-1 justify-center"
            @click="open = false"
          />
          <UButton
            label="Guardar"
            color="primary"
            type="submit"
            class="flex-1 justify-center"
          />
        </div>
      </UForm>
    </template>
  </UModal>
</template>
