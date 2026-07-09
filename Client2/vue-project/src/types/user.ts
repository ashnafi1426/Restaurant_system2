export interface User {
  id: string
  first_name: string
  last_name: string
  full_name: string
  email: string
  phone: string
  role: string
  is_active: boolean
  last_login: string | null
  created_at: string
  updated_at: string
}
