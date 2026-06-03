<script setup lang="ts">
import { formatTimeAgoIntl  } from '@vueuse/core'
const { isNotificationsSlideoverOpen } = useDashboard()

const timeAgo = (date: Date) => formatTimeAgoIntl(new Date(date), { locale: 'pt-PT' })
</script>

<template>
  <USlideover v-model:open="isNotificationsSlideoverOpen" side="left">
    <template #header>
      <div class="flex items-center justify-between w-full">
        <h2 class="text-base font-semibold">Notificações</h2>
        <button class="text-xs text-muted hover:text-primary transition" @click="useNotificationStore().readAll()">
          Marcar todos como lidas
        </button>
      </div>
    </template>
    <template #body>
      <NuxtLink v-for="notification in useNotificationStore().notifications"
                :key="notification.uuid"
                data-testid="notification-item"
                class="px-3 py-2.5 rounded-md hover:bg-elevated/50 flex items-center gap-3 relative -mx-3 first:-mt-3 last:-mb-3"
                @click.prevent="useNotificationStore().read(notification.uuid)"
      >
        <span v-if="!notification.read" class="absolute top-2 right-2 w-2 h-2 rounded-full bg-red-500" />
        <UBadge
          :color="notification.style"
          variant="solid"
          class="w-1 self-stretch rounded-full p-0"
        />
        <div class="text-sm flex-1 flex flex-col justify-between">
          <span class="text-highlighted font-medium">
            {{ notification.title }}
          </span>
          <p class="text-dimmed line-clamp-3 mt-1">
            {{ notification.body }}
          </p>
          <time class="text-muted text-xs self-end mt-2">
            {{ timeAgo(new Date(notification.date)) }}
          </time>
        </div>
      </NuxtLink>
    </template>
  </USlideover>
</template>
