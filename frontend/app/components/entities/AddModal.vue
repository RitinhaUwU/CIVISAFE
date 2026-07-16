<script setup lang="ts">
import * as z from 'zod'
import type {FormSubmitEvent} from '@nuxt/ui'
import {useApiStore} from '~/stores/api'
import {createBlobURL, formatBytes} from "~/utils";
import {usePaginatedSelect} from "~/composables/usePaginatedSelect";
import {useAuthStore} from "~/stores/auth";

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
  phone_contact: z.string().min(9, 'Número inválido').regex(/^\+?[0-9]+(?: [0-9]+)*$/, 'Insira apenas números ou formato +000 000000000').optional().or(z.literal('')).nullable(),
  email_contact: z.string().email('Email inválido').optional().or(z.literal('')).nullable(),
  address: z.string().optional().nullable(),
  poc_name: z.string().optional().nullable(),
  poc_phone: z.string().min(9, 'Número inválido').regex(/^\+?[0-9]+(?: [0-9]+)*$/, 'Insira apenas números ou formato +000 000000000').optional().or(z.literal('')).nullable(),
  poc_email: z.string().email('Email inválido').optional().or(z.literal('')).nullable(),
  description: z.string().optional().nullable(),
  entity_type_id: z.object({
    id: z.number(),
    name: z.string()
  }, {error: 'Selecione um Tipo de Entidade'})
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
  if (!useAuthStore().hasPermission('ENTITIES_CREATE')) return;

  if (!await checkServerAccess()) {
    useToast().add({
      title: 'Sem ligação à internet!',
      description: 'Não é possível guardar alterações sem estar ligado à internet. Tente novamente mais tarde',
      color: 'error'
    });
    return;
  }

  try {
    const payload = {
      ...event.data,
      entity_type_id: event.data.entity_type_id?.id,
    }

    const entity = await api.createEntity(payload)

    if(fileState.image != undefined)
    {
      const uploadUrl = await api.requestEntitySignedUrl(fileState.image.name);

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
      await api.updateEntityLogo(entity.data.data.id, uploadUrl.data.key);
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
      entity_type_id: null,
    })
  }
  catch (e: any) {
    toast.add({
      title: 'Erro',
      description: 'Erro ao criar entidade',
      color: 'error'
    })
  }
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
      <UForm
        :state="state"
        :schema="schema"
        @submit="onSubmit"
      >
        <div class="h-px border-t border-stone-200 dark:border-stone-800 mb-5"/>
        <div class="grid grid-cols-1 lg:grid-cols-2 gap-x-8 gap-y-5">
          <div class="space-y-5">
            <UFormField label="Tipo de Entidade:" name="entity_type_id" required>
              <USelectMenu
                data-testid="entity-type-select"
                ref="entityTypesMenu"
                v-model="state.entity_type_id"
                v-model:search-term="entityTypes.search.value"
                :items="entityTypes.items.value"
                :loading="entityTypes.loading.value"
                label-key="name"
                ignore-filter
                placeholder="Seleciona o tipo"
                class="w-full"
              />
            </UFormField>
            <UFormField label="Nome:" name="name" required>
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
            <UFormField label="Descrição:" name="description">
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
