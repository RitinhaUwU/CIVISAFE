import {defineStore} from "pinia";
import type {Notification} from "~/types";
import {useApiStore} from "~/stores/api";

export const useNotificationStore = defineStore('notification', () => {
  const apiStore = useApiStore();
  const notifications = ref<Notification[]>([]);

  const add = (notification: Notification) => {
    notifications.value.push(notification)
  }

  const read = (uuid: string) => {

  }

  const readAll = () => {

  }

  return {
    notifications,
    add,
    read,
    readAll
  }
})
