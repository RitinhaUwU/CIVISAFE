import {defineStore} from "pinia";
import type {Notification} from "~/types";
import {useApiStore} from "~/stores/api";
import {useAuthStore} from "~/stores/auth";

export const useNotificationStore = defineStore('notification', () => {
  const { $echo } = useNuxtApp();
  const apiStore = useApiStore();
  const authStore = useAuthStore();
  const toast = useToast();
  const notifications = ref<Notification[]>([]);

  const connect = () => {

    console.debug("Called websocket connect function.")

    $echo.options.auth.headers.Authorization = `Bearer ${localStorage.getItem('token')}`;

    $echo.private(`App.Models.User.${authStore.currentUserID}`)
      // Isto tem de ser ".NotificationEvent" porque se não tiver o ponto ele pensa que é "App.Events.NotificationEvent"
      .listen('.NotificationEvent', (notification: Notification) => {
        notifications.value.unshift(notification)
        toast.add({
          title: notification.title,
          description: notification.body,
          color: notification.style ?? 'info'
        })
      })
  }

  const disconnect = () => {
    $echo.leave(`App.Models.User.${authStore.currentUserID}`);
  }

  const read = (uuid: string) => {

  }

  const readAll = () => {

  }

  return {
    notifications,
    connect,
    disconnect,
    read,
    readAll
  }
})
