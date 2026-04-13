<script setup lang="ts">
import { useApiStore } from "@/stores/api"

const api = useApiStore()
const toast = useToast()
const props = defineProps<{
  id: number
  name: string
  description: string
  open: boolean
}>()

const emit = defineEmits(['update:open', 'deleted'])

const onSubmit = async () => {
  if (!props.id) return

  try {
    await api.deleteIncidentPriority(props.id)

    toast.add({
      title: 'Eliminado com sucesso',
      description: `O Estado foi eliminado.`,
      color: 'success'
    })

    emit('deleted')
    emit('update:open', false)

  } catch (e: any) {
    toast.add({
      title: 'Erro',
      description: 'Não foi possível eliminar o registo de Estado.',
      color: 'error'
    })
  }
}
</script>

<template>
  <UModal
    v-model:open="props.open"
    :title="`Eliminar tipo de Estado: ${props.name} - ${props.description}`"
    :description="`Tem certeza que deseja eliminar este tipo de estado '${props.name} - ${props.description}'?`"
    :ui="{
      close: 'hidden'
    }"
  >

    <template #body>
      <div class="flex justify-end gap-2">
        <UButton
          label="Cancelar"
          color="neutral"
          variant="subtle"
          @click="emit('update:open', false)"
        />
        <UButton
          label="Eliminar"
          color="error"
          loading-auto
          @click="onSubmit"
        />
      </div>
    </template>
  </UModal>
</template>
