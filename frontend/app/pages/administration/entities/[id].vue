<script setup lang="ts">
import { useRoute } from 'vue-router'
import { useApiStore } from '@/stores/api'
import * as z from "zod";
import type {BreadcrumbItem} from "@nuxt/ui/components/Breadcrumb.vue";
import {createBlobURL} from "~/utils";

const route = useRoute()
const apiStore = useApiStore()

const saving = ref(false)
const entityTypes = ref([])

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

const state = reactive<Partial<Schema & { entity_type_id: number, logo: string }>>({
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
  const res = await apiStore.getEntity(route.params.id)
  const data = res.data.data

  Object.assign(state, {
    ...data,
    entity_type_id: data.entityType?.id,
  })
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

  if(fileState.image !== undefined){
    //Existe uma imagem para carregar/atualizar
    const uploadURL = await apiStore.requestEntitySignedUrl(fileState.image.name);

    const bucketResponse = await fetch(uploadUrl.data.url.url, {
      method: 'PUT',
      headers: { 'Content-Type': fileState.image.type },
      body: fileState.image
    })

    if(!bucketResponse.ok)
    {
      toast.add({
        title: 'Erro ao carregar imagem',
        description: 'Ocorreu um erro ao carregar imagem.',
        color: 'error'
      })
      return;
    }

    await apiStore.updateEntityLogo(route.params.id, uploadURL.data.key)
  }
  else
  {
    //Remover o logotipo
    //if()
  }

  saving.value = true
  try {
    await apiStore.updateEntity(route.params.id, state)

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

const fetchEntityTypes = async () => {
  const res = await apiStore.getEntityTypes()
  entityTypes.value = res.data.data.map((t: any) => ({
    label: t.name,
    value: t.id
  }))
}

const items = ref<BreadcrumbItem[]>([
  {
    label: 'Entidades',
    icon: 'i-lucide-building-2',
    to: '/administration/entities'
  },
  {
    label: 'Dados das Entidades',
    icon: 'i-lucide-building',
  }
])

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

const entityLogoURL = computed(() => {
  if(fileState.image !== undefined && fileState.image !== null) {
    return createBlobURL(fileState.image)
  }

  if(state.logo !== undefined && state.logo !== null) {
    return state.logo
  }

  return undefined
});

onMounted(() => {
  fetchEntity()
  fetchEntityTypes()
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
            <UButton label="Guardar" color="primary" :loading="saving" @click="handleSave" />
          </div>
        </div>
      </div>
    </header>
    <div class="flex-1 overflow-y-auto px-6 sm:px-8 py-8 space-y-8">
      <UBreadcrumb :items="items" />
      <div class="grid grid-cols-1 lg:grid-cols-[1fr_260px] gap-8">
        <div class="space-y-6">
          <section>
            <h2 class="font-bold">Logotipo</h2>
            <div class="grid grid-cols-2">
              <div>
                <UFileUpload
                  v-model="fileState.image"
                  v-slot="{ open, removeFile }"
                  accept="image/png, image/jpeg, image/jpg"
                >
                  <div class="flex flex-wrap items-center gap-3">
                    <UAvatar
                      :src="entityLogoURL"
                      icon="i-lucide-image"
                      class="h-50 w-50"
                    />

                    <UButton
                      :label="state.logo ? 'Alterar Logotipo' : 'Carregar Logotipo'"
                      color="neutral"
                      variant="outline"
                      @click="open()"
                    />

                    <UButton
                      label="Restaurar"
                      v-if="fileState.image"
                      @click="removeFile()"
                    />

                  </div>
                </UFileUpload>
              </div>
            </div>
          </section>

          <section class="space-y-2">
            <h2 class="font-bold">Dados Gerais</h2>
            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
              <UFormField label="Tipo" class="sm:col-span-2">
                <USelect
                  v-model="state.entity_type_id"
                  :items="entityTypes"
                  class="w-full"
                />
              </UFormField>
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
      </div>
    </div>
  </div>
</template>

<style scoped>

</style>
