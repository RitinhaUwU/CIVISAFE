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

export interface Entity {
  id: number;
  name: string;
  description: string;
  phone_contact: string;
  email_contact: string;
  address: string;
  logo: string;
  poc_name: string;
  poc_phone: string;
  poc_email: string;
  created_at: Date;
  updated_at: Date;
}

export interface EntityType {
  id: number;
  name: string;
  description: string;
  created_at: Date;
  updated_at: Date;
}

export interface IncidentPriority {
  id: number;
  name: string;
  description: string;
  hex_color: string;
  is_active: boolean;
}

export interface IncidentState {
  id: number;
  name: string;
  description: string;
  hex_color: string;
  terminates_incident: boolean;
  is_active: boolean;
}

export interface IncidentType {
  id: number;
  code: number;
  species: string;
  type: string;
  description: string;
  created_at: Date;
  updated_at: Date;
}

export interface Notification {
  uuid: string
  title: string
  body: string
  style: Toast['variants']
  date: string
  read: boolean
}

interface QueryParams{
  page?: number
  per_page?: number
  search?: string
}

