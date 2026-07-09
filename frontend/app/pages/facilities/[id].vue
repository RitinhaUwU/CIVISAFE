<script setup lang="ts">
import { useRoute } from 'vue-router'
import { useApiStore } from '@/stores/api'
import * as z from 'zod'
import type {BreadcrumbItem} from '@nuxt/ui/components/Breadcrumb.vue'
import {createBlobURL} from '@/utils'
import AddFileModal from "~/components/facilities/AddFileModal.vue";

const route = useRoute()
const api = useApiStore()

const saving = ref(false)

const tabs = [
  {
    label: 'Geral',
    slot: 'geral',
    icon: 'i-lucide-building-2'
  },
  {
    label: 'Ficheiros',
    slot: 'files',
    icon: 'i-lucide-files',
  }
]

const schema = z.object({
  name: z.string().min(1, 'Nome é obrigatório'),
  contact: z.string().min(9, 'Número inválido').regex(/^\+?[0-9]+(?: [0-9]+)*$/, 'Insira apenas números ou formato +000 000000000').optional().or(z.literal('')).nullable(),
  email: z.string().email('Email inválido').optional().or(z.literal('')).nullable(),
  address: z.string().optional().nullable(),
  description: z.string().optional().nullable()
})

type Schema = z.output<typeof schema>

const state = reactive<Partial<Schema & {image: string, id: number, documents: any[]}>>({
  name: '',
  email: '',
  contact: '',
  address: '',
  description: '',
  documents: []
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

const imageRemoved = ref(false)

const handleRemoveImage = (removeFile: () => void) => {
  removeFile()
  if (state.image) {
    imageRemoved.value = true
    state.image = undefined
  }
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

  if(fileState.image){
    //Existe uma imagem para carregar/atualizar
    const uploadURL = await api.requestFacilitySignedUrl(fileState.image.name);

    const bucketResponse = await fetch(uploadURL.data.url.url, {
      method: 'PUT',
      headers: { 'Content-Type': fileState.image.type },
      body: fileState.image
    })

    if(!bucketResponse.ok) {
      toast.add({
        title: 'Erro ao carregar imagem',
        description: 'Ocorreu um erro ao carregar imagem.',
        color: 'error'
      })
      return;
    }

    await api.updateFacilityImage(parseInt(<string>route.params.id), uploadURL.data.key)
  }
  else if (imageRemoved.value) {
    try {
      await api.deleteFacilityImage(state.id)
      imageRemoved.value = false
    } catch (e) {
      toast.add({
        title: 'Erro',
        description: 'Erro ao remover logotipo',
        color: 'error'
      })
      return;
    }
  }

  saving.value = true
  try {
    await api.updateFacility(parseInt(<string>route.params.id), state)

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

const facilityLogoURL = computed(() => {
  if(fileState.image !== undefined && fileState.image !== null) {
    return createBlobURL(fileState.image)
  }

  if(state.image !== undefined && state.image !== null) {
    return state.image
  }

  return undefined
});

// Documentos
const deleteDocument = async (mediaId: number) => {
  await api.deleteFacilityDocument(state.id, mediaId)
  await fetchFacility()

  toast.add({
    title: 'Sucesso',
    description: 'Ficheiro eliminado.',
    color: 'success'
  })
}

const downloadDocument = async (mediaId: number, filename: string) => {
  await api.downloadFacilityDocument(state.id, mediaId, filename)
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
  if(!useAuthStore().hasPermission('FACILITIES_LIST')){
    useRouter().push('/inicio');
    return;
  }

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
        </div>
      </div>
    </header>
    <div class="flex-1 overflow-y-auto px-6 sm:px-8 py-8 space-y-8">
      <UBreadcrumb :items="items" />
      <UTabs :items="tabs" class="w-full">
        <template #geral>
          <div class="grid grid-cols-1 lg:grid-cols-[1fr_380px] gap-8">
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
            </div>
            <section class="space-y-6">
              <h2 class="font-bold">Logotipo</h2>
              <UFileUpload v-model="fileState.image" v-slot="{ open, removeFile }" accept="image/png, image/jpeg, image/jpg">
                <div class="relative w-full aspect-square rounded-xl border-2 border-dashed border-stone-300 dark:border-stone-700 hover:border-primary-400 dark:hover:border-primary-500 transition-colors overflow-hidden cursor-pointer bg-stone-50 dark:bg-stone-900" @click="!facilityLogoURL && open()">
                  <template v-if="facilityLogoURL">
                    <img
                      :src="facilityLogoURL"
                      alt="Logotipo"
                      class="w-full h-full object-contain p-4"
                    />
                    <div class="absolute inset-0 flex flex-col items-center justify-center gap-2 opacity-0 hover:opacity-100 transition-opacity bg-black/40 rounded-xl">
                      <UButton icon="i-lucide-pencil" label="Alterar" color="neutral" variant="solid" size="sm" @click.stop="open()" />
                      <UButton icon="i-lucide-rotate-ccw" label="Restaurar" color="primary" variant="solid" size="sm" @click.stop="removeFile()" />
                      <UButton icon="i-lucide-trash-2" label="Remover" color="error" variant="solid" size="sm" @click.stop="handleRemoveImage(removeFile)" />
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
            <UButton
              icon="i-lucide-save"
              color="primary"
              size="xl"
              :loading="saving"
              @click="handleSave"
              class="fixed bottom-6 right-6 z-1000 rounded-full w-16 h-16 shadow-lg flex items-center justify-center"
            />
          </div>
        </template>
        <template #files>
          <div class="space-y-6 pt-4">
            <section class="space-y-2">
              <div class="flex justify-end mb-6">
                <FacilitiesAddFileModal :facility-id="state.id ?? 0" @uploaded="fetchFacility" />
              </div>
              <template v-if="state.documents?.length">
                <div v-for="doc in state.documents" :key="doc.id" class="group flex items-center justify-between rounded-xl border border-stone-200 dark:border-stone-800 bg-white dark:bg-stone-900 px-4 py-3 shadow-sm transition hover:shadow-md hover:border-stone-300 dark:hover:border-stone-700">
                  <div class="flex items-center gap-3 min-w-0">
                    <UIcon name="i-lucide-file" class="size-5 shrink-0 text-muted" />
                    <div class="min-w-0">
                      <p class="text-sm font-medium truncate">{{ doc.name }}</p>
                      <p class="text-xs text-muted">{{ formatBytes(doc.size) }}</p>
                    </div>
                  </div>
                  <div class="flex items-center gap-1 shrink-0 ml-4">
                    <UButton
                      data-testid="download-document"
                      icon="i-lucide-download"
                      color="neutral"
                      variant="ghost"
                      size="sm"
                      @click="downloadDocument(doc.id, doc.name)"
                    />
                    <UButton
                      data-testid="delete-document"
                      icon="i-lucide-trash-2"
                      color="error"
                      variant="ghost"
                      size="sm"
                      @click="deleteDocument(doc.id)"
                    />
                  </div>
                </div>
              </template>
              <div v-else class="text-center py-6 text-sm text-stone-400">
                Sem ficheiros carregados
              </div>
            </section>
          </div>
        </template>
      </UTabs>
    </div>
  </div>
</template>

<style scoped>

</style>
