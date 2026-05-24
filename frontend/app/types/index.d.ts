export interface User {
  id: number
  name: string
  email: string
  mobile: string
  locked: boolean
  created_at: Date
  updated_at: Date
  deleted_at: Date
  roles: string[]
  permissions: string[]
}

export interface Notification {
  uuid: string
  title: string
  body: string
  style: Toast['variants']
  date: string
  read: boolean
}


