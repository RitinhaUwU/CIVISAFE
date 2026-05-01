import type { AvatarProps } from '@nuxt/ui'

export interface User {
  id: number
  name: string
  email: string
  avatar?: AvatarProps
  location: string
}

export interface Notification {
  uuid: string
  title: string
  body: string
  style: Toast['variants']
  date: string
  read: boolean
}


