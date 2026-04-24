<script setup lang="ts">
import { useApiStore } from "@/stores/api"

const api = useApiStore()
const toast = useToast()
const props = defineProps<{
  id: number
  team_identification: string
  open: boolean
}>()

const emit = defineEmits(['update:open', 'deleted'])

const onSubmit = async () => {
  if (!props.id) return

  try {
    await api.deleteVolunteer(props.id)

    toast.add({
      title: 'Voluntário eliminado',
      description: `${props.team_identification} foi removido com sucesso.`,
      color: 'success'
    })

    emit('deleted')
    emit('update:open', false)

  } catch (e) {
    toast.add({
      title: 'Erro',
      description: 'Não foi possível eliminar o voluntário.',
      color: 'error'
    })
  } finally {
    loading.value = false
  }
}
</script>

<template>
  <UModal
    :open="props.open"
    :title="`Eliminar Voluntário: ${props.team_identification}`"
    :description="`Tens a certeza que queres eliminar o voluntário '${props.team_identification}'?`"
    :ui="{ close: 'hidden' }"
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
