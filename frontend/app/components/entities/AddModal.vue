<script setup lang="ts">
import * as z from 'zod'
import type { FormSubmitEvent } from '@nuxt/ui'
import { useApiStore } from '../../stores/api'

const apiStore = useApiStore()
const open = ref(false)

const toast = useToast()

const schema = z.object({
  name: z.string().min(1, 'Nome é obrigatório'),
  phone_contact: z.string().optional().nullable(),
  email_contact: z.string().email('Email inválido').optional().nullable(),
  address: z.string().optional().nullable(),
  logo: z.string().optional().nullable(),
  poc_name: z.string().optional().nullable(),
  poc_phone: z.string().optional().nullable(),
  poc_email: z.string().email('Email inválido').optional().nullable(),
  description: z.string().optional().nullable()
})

type Schema = z.output<typeof schema>

const state = reactive<Partial<Schema>>({
  name: '',
  phone_contact: '',
  email_contact: '',
  address: '',
  logo: '',
  poc_name: '',
  poc_phone: '',
  poc_email: '',
  description: ''
})

async function onSubmit(event: FormSubmitEvent<Schema>) {
  try {
    await apiStore.createEntity(event.data)

    toast.add({
      title: 'Sucesso',
      description: 'Entidade criada com sucesso',
      color: 'success'
    })

    open.value = false

    Object.assign(state, {
      name: '',
      phone_contact: '',
      email_contact: '',
      address: '',
      logo: '',
      poc_name: '',
      poc_phone: '',
      poc_email: '',
      description: ''
    })

  } catch (e: any) {
    console.log(e.response?.data)

    toast.add({
      title: 'Erro',
      description: 'Erro ao criar entidade',
      color: 'error'
    })
  }
}
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
        class="grid grid-cols-1 lg:grid-cols-2 gap-x-8 gap-y-5"
        @submit="onSubmit"
      >
        <div class="space-y-5">
          <UFormField label="Nome:" name="name">
            <UInput v-model="state.name" class="w-full" required />
          </UFormField>

          <UFormField label="Email:" name="email">
            <UInput v-model="state.email_contact" class="w-full" required />
          </UFormField>

          <UFormField label="Contacto:" name="phone_contact">
            <UInput v-model="state.phone_contact" class="w-full" required />
          </UFormField>

          <UFormField label="Morada:" name="address">
            <UInput v-model="state.address" class="w-full" required />
          </UFormField>
        </div>

        <div class="space-y-5">
          <UFormField label="Nome do Responsável:" name="poc_name">
            <UInput v-model="state.poc_name" class="w-full" required />
          </UFormField>

          <UFormField label="Email do Responsável:" name="poc_email">
            <UInput v-model="state.poc_email" class="w-full" required />
          </UFormField>

          <UFormField label="Contacto do Responsável:" name="poc_phone">
            <UInput v-model="state.poc_phone" class="w-full" required />
          </UFormField>

          <UFormField label="Observações:" name="description">
            <UTextarea v-model="state.description" class="w-full" />
          </UFormField>
        </div>

        <div class="col-span-1 lg:col-span-2 flex justify-between gap-2">
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
