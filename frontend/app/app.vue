<script setup lang="ts">
import {useToast} from "@nuxt/ui/composables";
import {computed, onMounted, onUnmounted, ref, watch} from "vue";
import {useHead} from "nuxt/app";
import {useColorMode} from "@vueuse/core";
import {checkServerAccess} from "@/utils";
import {useAuthStore} from "@/stores/auth";

const toast = useToast()
const colorMode = useColorMode()
const color = computed(() => colorMode.value === 'dark' ? '#1b1718' : 'white')
let last_connectivity_state = true;

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
  },
  title: 'CIVISAFE',
})


const INTERVAL_MS = 5000
let intervalId: number | null = null

async function checkInternetAccess() {
  const online = await checkServerAccess()

  if(online !== last_connectivity_state)
  {
    last_connectivity_state = online;

    if (online) {
      toast.add({
        'title': 'Ligação à internet restaurada!',
        'description': 'A sua ligação à internet foi restaurada!',
        'color': 'success',
      });

      await useAuthStore().getUser();
    }
    else
    {
      toast.add({
        'title': 'Ligação perdida!',
        'description': 'A sua à internet foi perdida!',
        'color': 'error',
      })
    }
  }
}

function startConnectivityLoop() {
  if (intervalId) return
  intervalId = setInterval(checkInternetAccess, INTERVAL_MS)
}

function stopConnectivityLoop() {
  if (!intervalId) return
  clearInterval(intervalId)
  intervalId = null
}

function handleOffline() {
  stopConnectivityLoop()
  last_connectivity_state = false
  toast.add({
    title: 'Sem acesso à rede!',
    description: 'A sua ligação à internet foi perdida.',
    color: 'error',
  })
}

function handleOnline() {
  //Não mostrar o toast aqui porque o navigator.online não garante acesso à internet
  last_connectivity_state = false
  startConnectivityLoop()
}

onMounted(() => {
  startConnectivityLoop()
  window.addEventListener('offline', handleOffline)
  window.addEventListener('online', handleOnline)
})

onUnmounted(() => {
  stopConnectivityLoop()
  window.removeEventListener('offline', handleOffline)
  window.removeEventListener('online', handleOnline)
})
</script>

<template>
  <VitePwaManifest />
  <UApp>
    <NuxtLoadingIndicator />

    <NuxtLayout>
      <NuxtPage />
    </NuxtLayout>
  </UApp>
</template>
