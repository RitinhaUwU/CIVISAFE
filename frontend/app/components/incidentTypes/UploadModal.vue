<script setup lang="ts">
import * as z from 'zod'
import type { FormSubmitEvent } from '@nuxt/ui'
import { useApiStore } from '~/stores/api'

const apiStore = useApiStore()
const open = ref(false)
const emit = defineEmits(['created'])

const toast = useToast()

const schema = z.object({
  file: z
    .instanceof(File, {message: "Selecione um Ficheiro xlsx (Excel)"})
    .refine((file) => file.type == "application/vnd.openxmlformats-officedocument.spreadsheetml.sheet", {
      message: "O ficheiro deve ser um ficheiro xlsx (Excel 2007+)"
    })
})

type Schema = z.output<typeof schema>
const state = reactive<Partial<Schema>>({ file: undefined })

async function onSubmit(event: FormSubmitEvent<Schema>) {
  //TODO: Mostrar erro no modal, fechar o modal quando termina de carregar com sucesso e limpar a input quando o modal fecha
  try {
    const formData = new FormData();
    formData.append('file', event.data.file)
    await apiStore.uploadIncidentTypesFile(formData)

    toast.add({
      title: 'Ficheiro Carregado!',
      description: 'O Ficheiro será processado e receberá uma notificação quando a operação tiver terminado.',
      color: 'success'
    })
    closeModal()
    emit('created')
  } catch (e: any) {
    console.error(e)
    //const errors = e.response?.data?.errors
  }
}

function closeModal(){
  open.value = false
  state.file = undefined
}
</script>

<template>
  <UButton
    icon="i-lucide-upload"
    label="Carregar Estados"
    color="primary"
    @click="open = true"
  />
  <UModal
    v-model:open="open"
    title="Carregar Ficheiro..."
    description="Carregue o ficheiro Excel preenchido com os novos Tipos de Ocorrência"
  >
    <template #body>
      <UForm :state="state" :schema="schema" class="space-y-5" @submit="onSubmit">
        <h1>Esta lista irá substituir todos os Tipos de Ocorrência atuais</h1>
        <UFormField name="file" label="Ficheiro" description="Ficheiro XLSX com nova listagem de Tipos de Ocorrência">
          <UFileUpload
            :key="open"
            v-model="state.file"
            accept="application/vnd.openxmlformats-officedocument.spreadsheetml.sheet"
            class="min-h-48"
          />
        </UFormField>
        <div class="flex justify-between gap-3 pt-2">
          <UButton
            label="Cancelar"
            color="neutral"
            variant="subtle"
            class="flex-1 justify-center"
            type="button"
            @click="closeModal"
          />
          <UButton
            label="Carregar"
            color="primary"
            type="submit"
            class="flex-1 justify-center"
          />
        </div>
      </UForm>
    </template>
  </UModal>
</template>
