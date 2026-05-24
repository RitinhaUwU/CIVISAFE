import {defineStore} from "pinia";
import type {Notification} from "~/types";
import {useApiStore} from "~/stores/api";
import {useAuthStore} from "~/stores/auth";
import isOnline from "is-online";

export const useNotificationStore = defineStore('notification', () => {
  const {$echo} = useNuxtApp();
  const apiStore = useApiStore();
  const authStore = useAuthStore();
  const toast = useToast();
  const notifications = ref<Notification[]>([]);

  const connect = async () => {

    console.debug("Called websocket connect function.")

    // @ts-ignore
    $echo.options.auth.headers.Authorization = `Bearer ${localStorage.getItem('token')}`;

    if (await isOnline()) {
      apiStore.getNotifications()
        .then(r => {
          notifications.value = r.data.data;
        })
        .catch(e => {
          console.debug(e);
          toast.add({
            title: "Erro",
            description: "Ocorreu um erro ao carregar notificações",
            color: "error"
          });
        })
    }

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
    if (notifications.value.find(notif => notif.uuid === uuid)?.read === true)
      return;

    apiStore.readNotification(uuid)
      .then(r => {
        notifications.value = notifications.value.map(notification => notification.uuid === uuid ? {
          ...notification,
          read: true
        } : notification)
      })
      .catch(e => {
        console.debug(e);
        toast.add({
          title: "Erro",
          description: "Ocorreu um erro ao marcar a notificação como lida",
          color: "error"
        });
      })
  }

  const readAll = () => {
    if (!notifications.value.some(notification => notification.read === true))
      return;

    apiStore.readAllNotifications()
      .then(r => {
        notifications.value = notifications.value.map(n => ({...n, read: true}))
      })
      .catch(e => {
        console.debug(e);
        toast.add({
          title: "Erro",
          description: "Ocorreu um erro ao marcar as notificações como lidas",
          color: "error"
        });
      });
  }

  return {
    notifications,
    connect,
    disconnect,
    read,
    readAll
  }
})
