<script setup lang="ts">
import { formatTimeAgoIntl  } from '@vueuse/core'
import type { Notification } from '~/types'

const { isNotificationsSlideoverOpen } = useDashboard()
const api = useApiStore()
const toast = useToast()

const timeAgo = (date: Date) => formatTimeAgoIntl(new Date(date), { locale: 'pt-PT' })
const notifications = ref<Notification[]>([])
const loading = ref(false)

type Notification = {
  id: number;
  type: string;
  notifiable_type: string;
  notifiable_id: number;
  data: {
    title: string;
    body: string;
    style: string;
  };
  read_at: boolean,
  created_at: Date;
  updated_at: Date;
}

const fetch = async() => {
  loading.value = true
  try {
    const res = await api.getNotifications()

    notifications.value = res.data
  } catch (e) {
    toast.add({
      title: 'Erro',
      description: 'Erro ao carregar as notificações',
      color: 'error'
    })
  } finally {
    loading.value = false
  }
}

const markAllNotificationsRead = async () => {
  try {
    await api.readAllNotifications()

    notifications.value = notifications.value.map(n => ({ ...n, read_at: true }))
  } catch (e) {
    toast.add({
      title: 'Erro',
      description: 'Erro ao marcar todas as notificações como lidas',
      color: 'error'
    })
  }
}

const markNotificationRead = async (id: number) => {
  try {
    await api.readNotification(id)

    notifications.value = notifications.value.map(n => n.id === id ? { ...n, read_at: true } : n)
  } catch (e) {
    toast.add({
      title: 'Erro',
      description: 'Erro ao marcar a notificação como lida',
      color: 'error'
    })
  }
}

onMounted(() => {
  fetch()
})
</script>

<template>
  <USlideover v-model:open="isNotificationsSlideoverOpen" side="left">
    <template #header>
      <div class="flex items-center justify-between w-full">
        <h2 class="text-base font-semibold">Notificações</h2>
        <button class="text-xs text-muted hover:text-primary transition" @click="markAllNotificationsRead">
          Marcar todos como lidas
        </button>
      </div>
    </template>
    <template #body>
      <NuxtLink v-for="notification in notifications" :key="notification.id"
        class="px-3 py-2.5 rounded-md hover:bg-elevated/50 flex items-center gap-3 relative -mx-3 first:-mt-3 last:-mb-3"
        @click.prevent="markNotificationRead(notification.id)"
      >
        <span v-if="!notification.read_at" class="absolute top-2 right-2 w-2 h-2 rounded-full bg-red-500" />
        <UBadge
          :color="notification.data.style"
          variant="solid"
          class="w-1 self-stretch rounded-full p-0"
        />
        <div class="text-sm flex-1 flex flex-col justify-between">
          <span class="text-highlighted font-medium">
            {{ notification.data.title }}
          </span>
          <p class="text-dimmed line-clamp-3 mt-1">
            {{ notification.data.body }}
          </p>
          <time class="text-muted text-xs self-end mt-2">
            {{ timeAgo(new Date(notification.created_at)) }}
          </time>
        </div>
      </NuxtLink>
    </template>
  </USlideover>
</template>
