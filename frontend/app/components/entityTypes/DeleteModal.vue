<script setup lang="ts">
import { useApiStore } from "@/stores/api"

const api = useApiStore()
const toast = useToast()
const props = defineProps<{
  id: number
  name: string
  open: boolean
}>()

const emit = defineEmits(['update:open', 'deleted'])

const openModel = computed({
  get: () => props.open,
  set: (value: boolean) => emit('update:open', value)
})

const onSubmit = async () => {
  if (!props.id) return

  if (!await checkServerAccess()) {
    useToast().add({
      title: 'Sem ligação à internet!',
      description: 'Não é possível guardar alterações sem estar ligado à internet. Tente novamente mais tarde',
      color: 'error'
    });
    return;
  }

  try {
    await api.deleteEntityType(props.id)

    toast.add({
      title: 'Eliminado com sucesso',
      description: `O tipo de entidade foi eliminada.`,
      color: 'success'
    })

    emit('deleted')
    emit('update:open', false)

  } catch (e: any) {
    toast.add({
      title: 'Erro',
      description: 'Não foi possível eliminar o registo do tipo de entidade.',
      color: 'error'
    })
  }
}
</script>

<template>
  <UModal
    v-model:open="openModel"
    :title="`Eliminar Tipo de Entidade: ${props.name}`"
    :description="`Tem certeza que deseja eliminar o Tipo de Entidade '${props.name}'?`"
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
