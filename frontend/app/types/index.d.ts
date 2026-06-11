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

export interface QueryParams{
  page?: number
  per_page?: number
  filter?: {
    search?: string // Pesquisa
    type?: string // Usado nas entidades para filtrar por tipo de entidade
    status?: boolean
    state?: string // Usado nas ocorrências para filtrar por estados de ocorrência
    terminates?: boolean // Usado nos tipos de ocorrência para determinar se termina a ocorrência
    priority?: string // Usado nas ocorrências para filtrar por prioridade
    classification?: string
    has_accommodation?: boolean
    has_meal?: boolean
  }
}

export interface Facilities {
  id: number;
  name: string;
  email: string;
  address: string;
  contact: string;
  description: boolean;
  created_at: Date;
  updated_at: Date;
}

export interface Incident {
  id: number;
  identifier: string;
  incident_type_id: number;
  incident_state_id: number;
  user_id: number;
  incident_priority_id: number;
  start_datetime: Date;
  end_datetime: Date;
  coordinates: string;
  common_place: string;
  address: string;
  parish: string;
  municipality: string;
  district: string;
  command_post: string;
  is_major: boolean;
  alert_source_relationship: string;
  alert_source_name: string;
  alert_source_contact: string;
  obs: string;
  incident_id: number;
  created_at: Date;
  updated_at: Date;
  deleted_at: Date;
}

export interface Volunteer {
  id: number;
  name: string;
  start_datetime: Date;
  end_datetime: Date;
  contact: string;
  email: string;
  num_elements: number
  mission: string;
  team_identification: string
  classification: string
  has_accommodation: boolean;
  location: string;
  has_meal: boolean;
  meal_notes: string;
  meal_location: string;
  incident_id: number;
  created_at: Date;
  updated_at: Date;
  deleted_at: Date;
}

export interface DonationGoodType {
  id: number;
  name: string;
  is_type_countable: boolean;
  unit: string;
  danger_level: number;
  created_at: Date;
  updated_at: Date;
}
