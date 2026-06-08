<script setup lang="ts">

const api = useApiStore()
const toast = useToast()
const props = defineProps<{
  incidentId: number
  pcoId: number
  itemName: string
  open: boolean
}>()

const emit = defineEmits(['update:open', 'deleted'])

const openModel = computed({
  get: () => props.open,
  set: (value: boolean) => emit('update:open', value)
})

const onSubmit = async () => {
  if (!props.incidentId || !props.pcoId) return

  try {
    await api.deleteIncidentPCO(props.incidentId, props.pcoId)

    toast.add({
      title: 'Removido com sucesso',
      description: 'A função foi eliminada do PCO.',
      color: 'success'
    })

    emit('deleted')
    emit('update:open', false)

  } catch (e: any) {
    toast.add({
      title: 'Erro',
      description: 'Não foi possível eliminar a função do PCO.',
      color: 'error'
    })
  }
}
</script>

<template>
  <UModal
    v-model:open="openModel"
    :title="`Eliminar função: ${props.itemName}`"
    :description="`Tem certeza que deseja eliminar esta função do PCO '${props.itemName}'?`"
    :ui="{ close: 'hidden' }"
  >
    <template #body>
      <div class="flex justify-end gap-2">
        <UButton label="Cancelar" color="neutral" variant="subtle" @click="emit('update:open', false)"/>
        <UButton label="Eliminar" color="error" loading-auto @click="onSubmit"/>
      </div>
    </template>
  </UModal>
</template>
