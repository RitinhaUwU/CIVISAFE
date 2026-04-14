<script setup lang="ts">
import { ref, onMounted, onUnmounted } from 'vue'
import {useToast} from "@nuxt/ui/composables";

const isOnline = ref(true)
const installPrompt = ref(null)
const toast = useToast()
const colorMode = useColorMode()
const color = computed(() => colorMode.value === 'dark' ? '#1b1718' : 'white')
const title = 'CIVISAFE'

useHead({
  meta: [
    { charset: 'utf-8' },
    { name: 'viewport', content: 'width=device-width, initial-scale=1' },
    { key: 'theme-color', name: 'theme-color', content: color }
  ],
  link: [
    { rel: 'icon', href: '' }
  ],
  htmlAttrs: {
    lang: 'pt-pt'
  }
})

useSeoMeta({
  title,
  ogTitle: title,
  // ogImage: 'https://ui.nuxt.com/assets/templates/nuxt/dashboard-light.png',
  // twitterImage: 'https://ui.nuxt.com/assets/templates/nuxt/dashboard-light.png',
  // twitterCard: 'summary_large_image'
})

const updateOnline = () => {
  isOnline.value = navigator.onLine  // read actual state
  if (isOnline.value) {
    toast.add({
      title: 'Está online!',
      description: 'A sua ligação foi reestabelecida.',
      color: 'success'
    })
  } else {
    toast.add({
      title: 'Está offline!',
      description: 'Enquanto estiver offline, algumas funcionalidades estão indisponíveis.',
      color: 'warning'
    })
  }
}

onMounted(() => {
  isOnline.value = navigator.onLine
  window.addEventListener('online', updateOnline)
  window.addEventListener('offline', updateOnline)  // both point to same handler
  // window.addEventListener('visibilitychange', updateOnline)
  window.addEventListener('beforeinstallprompt', (e) => {
    e.preventDefault()
    installPrompt.value = e
  })
})

onUnmounted(() => {
  window.removeEventListener('online', updateOnline)
  window.removeEventListener('offline', updateOnline)
  window.removeEventListener('visibilitychange', updateOnline)
})
</script>

<template>
  <UApp>
    <NuxtLoadingIndicator />

    <NuxtLayout>
      <NuxtPage />
    </NuxtLayout>
  </UApp>
</template>
