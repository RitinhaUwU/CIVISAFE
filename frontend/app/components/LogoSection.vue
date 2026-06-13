<script setup lang="ts">
defineProps<{
  collapsed?: boolean
}>()
</script>

<template>
  <div class="flex items-center justify-center py-3">
    <Logo v-if="!collapsed" class="h-8 text-blue-500 dark:text-primary" />
    <LogoSmall v-else class="h8 text-blue-500 dark:text-primary" />
  </div>
</template>
