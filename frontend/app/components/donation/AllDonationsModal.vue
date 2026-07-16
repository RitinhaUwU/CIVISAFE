<script setup lang="ts">

const open = ref(false);
const tableSearchTerm = ref<String>();

</script>

<template>
  <UModal
    v-model:open="open"
    title="Todas as Doações"
    :ui="{ content: 'max-h-[90vh] overflow-y-auto w-full max-w-5xl' }"
  >
    <UButton
      label="Ver Todas as Doações"
      icon="i-lucide-list"
      size="sm"
    />
    <template #body>

      <UInput
        placeholder="Pesquisar..."
        v-model="tableSearchTerm"
        class="w-full mb-4"
      />

      <DonationTable
        v-model="tableSearchTerm"
        class="w-full"
      />
    </template>
  </UModal>
</template>

<style scoped>

</style>
