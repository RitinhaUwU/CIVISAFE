<script setup lang="ts">
import {createBlobURL, formatBytes} from '@/utils'
import z from 'zod'
import {useApiStore} from '@/stores/api'

const api = useApiStore()
const toast = useToast()

const props = defineProps<{ facilityId: number }>()
const emit = defineEmits(['uploaded'])

const open = ref(false)
const loading = ref(false)
const files = ref<File[]>([])

/***
 Upload da ficheiros
 ***/
const ACCEPTED_TYPES = ['application/pdf', 'application/msword',
  'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
  'application/vnd.ms-excel',
  'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',]

const removeFile = (index: number) => {
  files.value.splice(index, 1)
}

const onSubmit = async () => {
  if (!props.facilityId) return

  if (!files.value.length) {
    toast.add({
      title: 'Erro',
      description: 'Seleciona pelo menos um ficheiro.',
      color: 'error'
    })

    return
  }

  loading.value = true
  try {
    await api.uploadFacilityDocuments(props.facilityId, files.value)

    toast.add({
      title: 'Sucesso',
      description: 'Ficheiros carregados com sucesso.',
      color: 'success'
    })

    files.value = []

    open.value = false
    emit('uploaded')
  } catch {
    toast.add({
      title: 'Erro',
      description: 'Erro ao carregar os ficheiros.',
      color: 'error'
    })
  } finally {
    loading.value = false
  }
}
</script>

<template>
  <UModal v-model:open="open" title="Carregar Documentos" description="Adicione documentos à instalação">
    <UButton icon="i-lucide-upload" label="Carregar Ficheiros" color="primary" />
    <template #body>
      <UFileUpload
        v-model="files"
        icon="i-lucide-file-up"
        label="Arrasta os teus ficheiros aqui"
        description="PDF, DOC, XLS, PNG, JPG (máx. 20 MB cada)"
        layout="list"
        position="inside"
        multiple
        :interactive="false"
        :ui="{ base: 'min-h-48' }"
        accept=".pdf,.doc,.docx,.xls,.xlsx,.jpg,.jpeg,.png"
        class="w-full"
      >
        <template #actions="{ open: openPicker }">
          <UButton
            label="Selecionar ficheiros"
            icon="i-lucide-upload"
            color="neutral"
            variant="outline"
            @click="openPicker()"
          />
        </template>
        <template #files-top="{ open: openPicker, files: selectedFiles }">
          <div v-if="selectedFiles?.length" class="mb-2 flex items-center justify-between">
            <p class="font-bold">Ficheiros ({{ selectedFiles?.length }})</p>
            <UButton
              icon="i-lucide-plus"
              label="Adicionar mais"
              color="neutral"
              variant="outline"
              class="-my-2"
              @click="openPicker()"
            />
          </div>
        </template>
      </UFileUpload>
      <div class="flex justify-between gap-3 pt-4">
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
          class="flex-1 justify-center"
          :loading="loading"
          :disabled="!files.length"
          @click="onSubmit"
        />
      </div>
    </template>
  </UModal>
</template>

<style scoped>

</style>
