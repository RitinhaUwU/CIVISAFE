<script setup lang="ts">
import {useApiStore} from '@/stores/api'
import {useAuthStore} from "~/stores/auth";

const api = useApiStore()
const toast = useToast()

const props = defineProps<{ facilityId: number }>()
const emit = defineEmits(['uploaded'])

const open = ref(false)
const loading = ref(false)
const file = ref<File>()

/***
 Upload da ficheiros
 ***/
const onSubmit = async () => {
  if (!useAuthStore().hasPermission('FACILITIES_FILES_UPLOAD')) return

  if (!props.facilityId) return

  if (!await checkServerAccess()) {
    toast.add({
      title: 'Sem ligação à internet!',
      description: 'Não é possível guardar alterações sem estar ligado à internet.',
      color: 'error'
    })
    return
  }

  if (!file.value) {
    toast.add({
      title: 'Erro',
      description: 'Seleciona um ficheiro.',
      color: 'error'
    })
    return
  }


  loading.value = true
  try {
    const uploadURL = await api.requestFacilityDocumentSignedUrl(file.value.name, file.value.type)

    const bucketResponse = await fetch(uploadURL.data.url, {
      method: 'PUT',
      headers: {
        ...uploadURL.data.headers,
        'Content-Type': file.value.type
      },
      body: file.value
    })

    if (!bucketResponse.ok) {
      toast.add({
        title: 'Erro ao carregar ficheiro',
        description: 'Ocorreu um erro ao carregar ficheiro. A restante informação foi gravada.',
        color: 'error'
      })
      return;
    }

    await api.uploadFacilityDocument(props.facilityId, uploadURL.data.key, file.value.name)

    toast.add({
      title: 'Sucesso',
      description: 'Ficheiro carregado com sucesso.',
      color: 'success'
    })

    file.value = undefined
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
        v-model="file"
        icon="i-lucide-file-up"
        label="Arrasta o ficheiro aqui"
        description="PDF, DOC, XLS, PNG, JPG (máx. 20 MB)"
        layout="list"
        position="inside"
        :interactive="false"
        :ui="{ base: 'min-h-48' }"
        accept=".pdf,.doc,.docx,.xls,.xlsx,.jpg,.jpeg,.png"
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
          :disabled="!file"
          @click="onSubmit"
        />
      </div>
    </template>
  </UModal>
</template>

<style scoped>

</style>
