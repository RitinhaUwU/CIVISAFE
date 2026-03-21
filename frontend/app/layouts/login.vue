<script setup lang="ts">
const colorMode = useColorMode()

const navbarUI = computed(() => ({
  base: colorMode.value === 'dark' ? 'bg-gray-900' : 'bg-blue-500 text-white'
}))
</script>

<template>
  <div class="min-h-screen flex flex-col">
    <UDashboardNavbar :ui="navbarUI">
      <template #leading>
        <Logo class="h-8 text-blue-500 dark:text-primary" />
      </template>
      <template #right>
        <UButton
          :icon="colorMode.value === 'dark' ? 'i-lucide-sun' : 'i-lucide-moon'"
          variant="ghost"
          @click="colorMode.preference = colorMode.value === 'dark' ? 'light' : 'dark'"
        />
      </template>
    </UDashboardNavbar>
    <main class="flex-1 flex items-center justify-center">
      <slot />
    </main>
    <UFooter class="w-full">
      <div class="py-4 text-center text-sm opacity-70">
        Copyright © 2026
      </div>
    </UFooter>
  </div>
</template>

<style scoped>

</style>
