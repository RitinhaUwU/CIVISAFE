import {defineStore} from 'pinia'
import {ref} from 'vue'
import {io, type Socket} from 'socket.io-client'
import {useRuntimeConfig} from '#imports'
import {useNotificationStore} from "@/stores/notification";
import type {Notification} from '~/types'


export const useSocketStore = defineStore('socket', () => {
  const notificationStore = useNotificationStore();
  const socket = ref<Socket | null>(null)
  const isConnected = ref(false)
  const runtimeConfig = useRuntimeConfig()

  const connect = () => {
    if (socket.value) return

    const token = localStorage.getItem('token')
    if (!token) return

    const client = io(runtimeConfig.public.websocketURL, {
      auth: {
        token,
      }
    })

    socket.value = client

    client.on('connect', () => {
      isConnected.value = true
    })

    client.on('disconnect', () => {
      isConnected.value = false
    })

    client.on('notification', (payload: Notification) => {
      console.debug(payload)
      notificationStore.add(payload)
    })

    client.on('connect_error', (error: Error) => {
      console.error('Socket connect error', error)
    })
  }

  const disconnect = () => {
    if (!socket.value) return
    socket.value.disconnect()
    socket.value = null
    isConnected.value = false
  }

  return {
    socket,
    isConnected,
    connect,
    disconnect,
  }
})
