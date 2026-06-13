<script setup lang="ts">
const props = defineProps<{
  open: boolean
  conflicting: any | null
}>()

const emit = defineEmits<{
  (e: 'update:open', v: boolean): void
  (e: 'confirm'): void
  (e: 'cancel'): void
}>()

const cancel = () => {
  emit('cancel')
  emit('update:open', false)
}

const confirm = () => {
  emit('confirm')
  emit('update:open', false)
}
</script>

<template>
  <UModal
    :open="props.open"
    title="A função está ATIVO"
    description="Já existe uma função ativa com este cargo"
    @update:open="emit('update:open', $event)"
  >
    <template #body>
      <div class="space-y-2">
        <p class="text-sm text-stone-600 dark:text-stone-400">
          A função <span class="font-semibold">{{ conflicting?.function_pco }}</span> já está atribuída a
          <span class="font-semibold">{{ conflicting?.resp_pco }}</span> sem data de fim.
        </p>
        <p class="text-sm text-stone-600 dark:text-stone-400">
          Pretende encerrar essa função agora e criar o novo registo?
        </p>
      </div>
    </template>
    <template #footer>
      <div class="flex justify-end gap-2 w-full">
        <UButton label="Cancelar" color="neutral" variant="soft" @click="cancel" />
        <UButton label="Sim, encerrar e criar" color="primary" @click="confirm" />
      </div>
    </template>
  </UModal>
</template>

