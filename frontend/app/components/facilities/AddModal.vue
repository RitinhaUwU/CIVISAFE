<script setup lang="ts">
import * as z from 'zod'
import type { FormSubmitEvent } from '@nuxt/ui'
import { useApiStore } from '~/stores/api'
import {createBlobURL, formatBytes} from "~/utils";

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

/***
 Upload da imagem
 ***/
const MAX_FILE_SIZE = 2 * 1024 * 1024 // 2MB
const MIN_DIMENSIONS = { width: 200, height: 200 }
const MAX_DIMENSIONS = { width: 4096, height: 4096 }
const ACCEPTED_IMAGE_TYPES = ['image/jpeg', 'image/jpg', 'image/png']

const fileSchema = z.object({
  image: z
    .instanceof(File, {
      message: 'Please select an image file.'
    })
    .refine((file) => file.size <= MAX_FILE_SIZE, {
      message: `The image is too large. Please choose an image smaller than ${formatBytes(MAX_FILE_SIZE)}.`
    })
    .refine((file) => ACCEPTED_IMAGE_TYPES.includes(file.type), {
      message: 'Please upload a valid image file (JPEG, JPG ou PNG).'
    })
    .refine(
      (file) =>
        new Promise((resolve) => {
          const reader = new FileReader()
          reader.onload = (e) => {
            const img = new Image()
            img.onload = () => {
              const meetsDimensions =
                img.width >= MIN_DIMENSIONS.width &&
                img.height >= MIN_DIMENSIONS.height &&
                img.width <= MAX_DIMENSIONS.width &&
                img.height <= MAX_DIMENSIONS.height
              resolve(meetsDimensions)
            }
            img.src = e.target?.result as string
          }
          reader.readAsDataURL(file)
        }),
      {
        message: `The image dimensions are invalid. Please upload an image between ${MIN_DIMENSIONS.width}x${MIN_DIMENSIONS.height} and ${MAX_DIMENSIONS.width}x${MAX_DIMENSIONS.height} pixels.`
      }
    )
})

type FileSchema = z.output<typeof fileSchema>

const fileState = reactive<Partial<FileSchema>>({
  image: undefined
})

async function onSubmit(event: FormSubmitEvent<Schema>) {
  try {
    const facility = await api.createFacility(event.data)

    if(fileState.image != undefined)
    {
      const uploadUrl = await api.requestFacilitySignedUrl(fileState.image.name);

      const bucketResponse = await fetch(uploadUrl.data.url.url, {
        method: 'PUT',
        headers: { 'Content-Type': fileState.image.type },
        body: fileState.image
      })

      if(!bucketResponse.ok)
      {
        toast.add({
          title: 'Erro ao carregar imagem',
          description: 'Ocorreu um erro ao carregar imagem. A restante informação foi gravada.',
          color: 'error'
        })
        return;
      }
      //Atualizar a entidade com a key da imagem
      await api.updateFacilityImage(facility.data.data.id, uploadUrl.data.key);
    }

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
    <UButton
      icon="i-lucide-plus"
      label="Nova Instalação"
      color="primary"
    />
    <template #body>
      <UForm :schema="fileSchema">
        <UFormField name="image" label="Imagem" description="JPG, JPEG ou PNG · máx. 2 MB" class="mb-5">
          <UFileUpload v-slot="{ open, removeFile }" v-model="fileState.image" accept="image/PNG,image/JPG,image/JPEG">
            <div class="relative mt-1 w-full rounded-xl border-2 border-dashed border-stone-300 dark:border-stone-700 hover:border-primary-400 dark:hover:border-primary-500 transition-colors cursor-pointer overflow-hidden" @click="!fileState.image && open()">
              <template v-if="fileState.image">
                <img
                  :src="createBlobURL(fileState.image)"
                  alt="Preview"
                  class="w-full max-h-52 object-contain bg-stone-50 dark:bg-stone-900"
                />
                <div class="absolute inset-0 flex items-center justify-center gap-2 opacity-0 hover:opacity-100 transition-opacity bg-black/40 rounded-xl">
                  <UButton icon="i-lucide-pencil" label="Alterar" color="neutral" variant="solid" size="sm" @click.stop="open()" />
                  <UButton icon="i-lucide-trash-2" label="Remover" color="error" variant="solid" size="sm" @click.stop="removeFile()" />
                </div>
                <div class="absolute bottom-0 left-0 right-0 flex items-center gap-1.5 px-3 py-1.5 bg-black/50 backdrop-blur-sm text-white text-xs">
                  <UIcon name="i-lucide-image" class="size-3 shrink-0" />
                  <span class="truncate">{{ fileState.image.name }}</span>
                  <span class="ml-auto shrink-0 text-white/60">{{ formatBytes(fileState.image.size) }}</span>
                </div>
              </template>
              <template v-else>
                <div class="flex flex-col items-center justify-center gap-2 py-10">
                  <div class="p-3 rounded-full bg-stone-100 dark:bg-stone-800">
                    <UIcon name="i-lucide-image-plus" class="size-6 text-muted" />
                  </div>
                  <div class="text-center">
                    <p class="text-sm font-medium text-default">
                      Arrasta ou <span class="text-primary-500">clica para selecionar</span>
                    </p>
                    <p class="text-xs text-muted mt-0.5">JPG, JPEG, PNG · máx. 2 MB</p>
                  </div>
                </div>
              </template>
            </div>
          </UFileUpload>
        </UFormField>
      </UForm>
      <UForm :state="state" :schema="schema" class="space-y-5" @submit="onSubmit">
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
