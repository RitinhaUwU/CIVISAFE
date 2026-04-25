<script setup lang="ts">
import { useApiStore } from "@/stores/api"

const api = useApiStore()
const toast = useToast()
const props = defineProps<{
  id: number
  type: string
  open: boolean
}>()

const emit = defineEmits(['update:open', 'deleted'])

const openModel = computed({
  get: () => props.open,
  set: (value: boolean) => emit('update:open', value)
})

const onSubmit = async () => {
  if (!props.id) return

  try {
    await api.deleteIncidentType(props.id)

    toast.add({
      title: 'Eliminado com sucesso',
      description: `O Tipo foi eliminado.`,
      color: 'success'
    })

    emit('deleted')
    emit('update:open', false)

  } catch (e: any) {
    toast.add({
      title: 'Erro',
      description: 'Não foi possível eliminar o registo do Tipo.',
      color: 'error'
    })
  }
}
</script>

<template>
  <UModal
    v-model:open="openModel"
    :title="`Eliminar tipo de Estado: ${props.type}`"
    :description="`Tem certeza que deseja eliminar este tipo de estado '${props.type}'?`"
    :ui="{
      close: 'hidden'
    }"
  >
    <template #body>
      <div class="flex justify-end gap-2">
        <UButton label="Cancelar" color="neutral" variant="subtle" @click="emit('update:open', false)" />
        <UButton label="Eliminar" color="error" loading-auto @click="onSubmit" />
      </div>
    </template>
  </UModal>
</template>
