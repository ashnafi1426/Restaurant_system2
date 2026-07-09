export interface User {
  id: string
  first_name: string
  last_name: string
  email: string
  role: string
}
export interface LoginResponse {
  success: boolean
  token: string
  user: User
}
