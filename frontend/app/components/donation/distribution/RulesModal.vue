<script setup lang="ts">
const open = ref(false)

</script>

<template>
  <UModal
    v-model:open="open"
    title="Regulamento "
    :ui="{ content: 'max-h-[90vh] overflow-y-auto w-full max-w-5xl' }"
  >
    <UButton
      icon="i-lucide-scroll"
      label="Regulamento"
      size="xl"
      class="h-fit p-4"
    />
    <template #body>
      Aqui podíamos escrever algumas regras ou <i>guidelines</i> sobre a forma como a PROCIV pretende que os utilizadores
      do sistema de doações interajam com ele. Por exemplo, máximos de bens por pessoa ou processos para resolução de
      problemas de inventário.
    </template>
  </UModal>
</template>

<style scoped>

</style>
