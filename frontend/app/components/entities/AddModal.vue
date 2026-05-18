<script setup lang="ts">
import * as z from 'zod'
import type {FormSubmitEvent} from '@nuxt/ui'
import {useApiStore} from '~/stores/api'
import {createBlobURL, formatBytes} from "~/utils";

const apiStore = useApiStore()
const open = ref(false)
const emit = defineEmits(['created'])
const entityTypes = ref([])

const toast = useToast()

const schema = z.object({
  name: z.string().min(1, 'Nome é obrigatório'),
  phone_contact: z.string().min(9, 'Número inválido').regex(/^\+?[0-9]+(?: [0-9]+)*$/, 'Insira apenas números ou formato +000 000000000').optional().nullable(),
  email_contact: z.string().email('Email inválido').optional().nullable(),
  address: z.string().optional().nullable(),
  poc_name: z.string().optional().nullable(),
  poc_phone: z.string().min(9, 'Número inválido').regex(/^\+?[0-9]+(?: [0-9]+)*$/, 'Insira apenas números ou formato +000 000000000').optional().nullable(),
  poc_email: z.string().email('Email inválido').optional().nullable(),
  description: z.string().optional().nullable()
})

type Schema = z.output<typeof schema>

const state = reactive<Partial<Schema & { entity_type_id: number }>>({
  name: '',
  phone_contact: '',
  email_contact: '',
  address: '',
  poc_name: '',
  poc_phone: '',
  poc_email: '',
  description: '',
  entity_type_id: null,
})

async function onSubmit(event: FormSubmitEvent<Schema>) {
  try {
    const entity = await apiStore.createEntity(event.data)

    if(fileState.image != undefined)
    {
      const uploadUrl = await apiStore.requestEntitySignedUrl(fileState.image.name);

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
      await apiStore.updateEntityLogo(entity.data.data.id, uploadUrl.data.key);
    }

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
      poc_name: '',
      poc_phone: '',
      poc_email: '',
      description: '',
      entity_type_id: null as number,
    })
  } catch (e: any) {
    console.debug(e)
    toast.add({
      title: 'Erro',
      description: 'Erro ao criar entidade',
      color: 'error'
    })
  }
}

const fetchEntityTypes = async () => {
  const res = await apiStore.getEntityTypes()
  entityTypes.value = res.data.data.map((t: any) => ({
    label: t.name,
    value: t.id
  }))
}

/***
 Upload do logotipo
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

onMounted(() => {
  fetchEntityTypes()
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
        :schema="fileSchema"
      >
        <UFormField name="image" label="Imagem" description="JPG, JPEG ou PNG. 2MB Max." class="mb-5">
          <UFileUpload v-slot="{ open, removeFile }" v-model="fileState.image" accept="image/PNG,image/JPG,image/JPEG">
            <div class="flex flex-wrap items-center gap-3">
              <UAvatar
                :src="fileState.image ? createBlobURL(fileState.image) : undefined"
                icon="i-lucide-image"
                class="w-24 h-24 ring-2 ring-default"
              />
              <div class="flex flex-col items-start">
                <UButton
                  :label="fileState.image ? 'Alterar imagem' : 'Carregar imagem'"
                  color="neutral"
                  variant="outline"
                  @click="open()"
                />
                <div v-if="fileState.image" class="flex items-center mt-1 ml-2 text-xs text-muted">
                  <span>{{ formatBytes(fileState.image.size) }}</span>
                </div>
              </div>
            </div>
            <p v-if="fileState.image" class="flex items-center text-xs text-muted mt-1.5 ml-1.5">
              {{ fileState.image.name }}
              <UButton
                icon="i-lucide-x"
                color="error"
                variant="ghost"
                size="xs"
                @click="removeFile()"
                class="ml-1"
              />
            </p>
          </UFileUpload>
        </UFormField>
      </UForm>
      <UForm
        :state="state"
        @submit="onSubmit"
      >
        <div class="h-px border-t border-stone-200 dark:border-stone-800 mb-5"/>
        <div class="grid grid-cols-1 lg:grid-cols-2 gap-x-8 gap-y-5">
          <div class="space-y-5">
            <UFormField label="Tipo de Entidade:" name="entity_type_id">
              <USelect
                v-model="state.entity_type_id"
                :items="entityTypes"
                placeholder="Seleciona o tipo"
                class="w-full"
              />
            </UFormField>
            <UFormField label="Nome:" name="name">
              <UInput v-model="state.name" class="w-full" required/>
            </UFormField>
            <UFormField label="Email:" name="email">
              <UInput v-model="state.email_contact" class="w-full"/>
            </UFormField>
            <UFormField label="Contacto:" name="phone_contact">
              <UInput v-model="state.phone_contact" class="w-full"/>
            </UFormField>
            <UFormField label="Morada:" name="address">
              <UInput v-model="state.address" class="w-full"/>
            </UFormField>
          </div>
          <div class="space-y-5">
            <UFormField label="Nome do Responsável:" name="poc_name">
              <UInput v-model="state.poc_name" class="w-full"/>
            </UFormField>
            <UFormField label="Email do Responsável:" name="poc_email">
              <UInput v-model="state.poc_email" class="w-full"/>
            </UFormField>
            <UFormField label="Contacto do Responsável:" name="poc_phone">
              <UInput v-model="state.poc_phone" class="w-full"/>
            </UFormField>
            <UFormField label="Observações:" name="description">
              <UTextarea v-model="state.description" class="w-full"/>
            </UFormField>
          </div>

          <div class="col-span-1 lg:col-span-2 flex justify-between gap-2">
            <UButton label="Cancelar" color="neutral" variant="subtle" class="flex-1 justify-center"
                     @click="open = false"/>
            <UButton label="Guardar" color="primary" type="submit" class="flex-1 justify-center"/>
          </div>
        </div>
      </UForm>
    </template>
  </UModal>
</template>
