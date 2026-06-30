<script setup lang="ts">
import {useRoute, useRouter} from 'vue-router'
import { useApiStore } from '@/stores/api'
import { useAuthStore } from '@/stores/auth'
import * as z from "zod";
import type {BreadcrumbItem} from "@nuxt/ui/components/Breadcrumb.vue";
import {createBlobURL} from "~/utils";
import {usePaginatedSelect} from "~/composables/usePaginatedSelect";

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

const selectOptionSchema = z.object({
  id: z.number(),
  name: z.string()
})

const schema = z.object({
  name: z.string().min(1, 'Nome é obrigatório'),
  phone_contact: z.string().min(9, 'Número inválido').regex(/^\+?[0-9]+(?: [0-9]+)*$/, 'Insira apenas números ou formato +000 000000000').optional().or(z.literal('')).nullable(),
  email_contact: z.string().email('Email inválido').optional().or(z.literal('')).nullable(),
  address: z.string().optional().nullable(),
  poc_name: z.string().optional().nullable(),
  poc_phone: z.string().min(9, 'Número inválido').regex(/^\+?[0-9]+(?: [0-9]+)*$/, 'Insira apenas números ou formato +000 000000000').optional().or(z.literal('')).nullable(),
  poc_email: z.string().email('Email inválido').optional().or(z.literal('')).nullable(),
  description: z.string().optional().nullable(),
  entity_type_id: selectOptionSchema.nullable()
})

type Schema = z.output<typeof schema>

const state = reactive<Partial<Schema & {logo: string}>>({
  name: '',
  email_contact: '',
  phone_contact: '',
  address: '',
  poc_name: '',
  poc_email: '',
  poc_phone: '',
  description: '',
  entity_type_id: null as number | null,
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
    entity_type_id: data.entityType ? { id: data.entityType.id, name: data.entityType.name } : null,
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

  if(fileState.image !== undefined){
    //Existe uma imagem para carregar/atualizar
    const uploadURL = await api.requestEntitySignedUrl(fileState.image.name);

    const bucketResponse = await fetch(uploadURL.data.url.url, {
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

    await api.updateEntityLogo(parseInt(<string>route.params.id), uploadURL.data.key)
  }
  else {
    //Remover o logotipo
    //if()
  }

  saving.value = true
  try {
    const payload = {
      ...result.data,
      entity_type_id: result.data.entity_type_id?.id ?? null
    }

    await api.updateEntity(parseInt(<string>route.params.id), payload)

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
              :disabled="!useAuthStore().hasPermission('ENTITIES_UPDATE')"
            />
          </div>
        </div>
      </div>
    </header>
    <div class="flex-1 overflow-y-auto px-6 sm:px-8 py-8 space-y-8">
      <UBreadcrumb :items="items" />
      <div class="grid grid-cols-1 lg:grid-cols-[1fr_380px] gap-8 items-start">
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
        <section class="space-y-6">
          <h2 class="font-bold">Logotipo</h2>
          <UFileUpload v-model="fileState.image" v-slot="{ open, removeFile }" accept="image/png, image/jpeg, image/jpg">
            <div class="relative w-full aspect-square rounded-xl border-2 border-dashed border-stone-300 dark:border-stone-700 hover:border-primary-400 dark:hover:border-primary-500 transition-colors overflow-hidden cursor-pointer bg-stone-50 dark:bg-stone-900" @click="!entityLogoURL && open()">
              <template v-if="entityLogoURL">
                <img
                  :src="entityLogoURL"
                  alt="Logotipo"
                  class="w-full h-full object-contain p-4"
                />
                <div class="absolute inset-0 flex flex-col items-center justify-center gap-2 opacity-0 hover:opacity-100 transition-opacity bg-black/40 rounded-xl">
                  <UButton icon="i-lucide-pencil" label="Alterar" color="neutral" variant="solid" size="sm" @click.stop="open()" />
                  <UButton icon="i-lucide-rotate-ccw" label="Restaurar" color="primary" variant="solid" size="sm" @click.stop="removeFile()" />
                </div>
              </template>
              <template v-else>
                <div class="absolute inset-0 flex flex-col items-center justify-center gap-2 p-4 text-center">
                  <div class="p-3 rounded-full bg-stone-100 dark:bg-stone-800">
                    <UIcon name="i-lucide-image-plus" class="size-6 text-muted" />
                  </div>
                  <p class="text-sm font-medium text-default">Carregar logotipo</p>
                  <p class="text-xs text-muted">JPG, JPEG, PNG · máx. 2 MB</p>
                </div>
              </template>
            </div>
            <div v-if="fileState.image" class="flex items-center gap-1.5 mt-2 px-1 text-xs text-muted">
              <UIcon name="i-lucide-file-image" class="size-3 shrink-0" />
              <span class="truncate">{{ fileState.image.name }}</span>
              <span class="ml-auto shrink-0">{{ formatBytes(fileState.image.size) }}</span>
            </div>
          </UFileUpload>
        </section>
      </div>
    </div>
  </div>
</template>

<style scoped>

</style>
