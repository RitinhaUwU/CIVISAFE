import { createSharedComposable } from '@vueuse/core'
import {useRoute} from "nuxt/app";
import {useRouter} from "vue-router";
import {defineShortcuts} from "@nuxt/ui/composables";
import {watch} from "vue";

const _useDashboard = () => {
  const route = useRoute()
  const router = useRouter()
  const isNotificationsSlideoverOpen = ref(false)

  defineShortcuts({
    'g-h': () => router.push('/inicio'), // Início
    'g-i': () => router.push('/incidents'), // Ocorrência
    'g-u': () => router.push('/users'), // Utilizadores
    'g-s': () => router.push('/settings'),
    'n': () => isNotificationsSlideoverOpen.value = !isNotificationsSlideoverOpen.value
  })

  watch(() => route.fullPath, () => {
    isNotificationsSlideoverOpen.value = false
  })

  return {
    isNotificationsSlideoverOpen
  }
}

export const useDashboard = createSharedComposable(_useDashboard)
