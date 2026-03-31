<script setup lang="ts">

</script>

<template>
    <UPageCard
      title="Categorias"
      description="These informations will be displayed publicly."
      variant="naked"
      orientation="horizontal"
      class="mb-4"
    >
      <UButton
        form="settings"
        label="Save changes"
        color="neutral"
        type="submit"
        class="w-fit lg:ms-auto"
      />

      <UTable />
    </UPageCard>
</template>
