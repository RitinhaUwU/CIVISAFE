<script setup lang="ts">
import {createBlobURL, formatBytes} from '@/utils'
import z from 'zod'
import {useApiStore} from '@/stores/api'

const api = useApiStore()
const open = ref(false)
const emit = defineEmits(['created'])

const toast = useToast()

const file = ref(null)

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

</script>

<template>
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

<style scoped>

</style>
