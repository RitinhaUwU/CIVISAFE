<script setup lang="ts">
import { useApiStore } from "@/stores/api"

const api = useApiStore()

const props = defineProps<{
  id: number
  name: string
  open: boolean
}>()

const emit = defineEmits(['update:open', 'deleted'])

const onSubmit = async () => {
  if (!props.id) return

  await api.deleteIncidentState(props.id)

  emit('deleted')
  emit('update:open', false)
}
</script>

<template>
  <UModal
    v-model:open="props.open"
    :title="`Eliminar tipo de Estado: ${props.name}`"
    :description="`Tem certeza que deseja eliminar este tipo de estado '${props.name}'?`"
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
