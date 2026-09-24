export interface Permission {
  id: number
  name: string
  slug: string
  module: string
  action: string
  description?: string
  is_active: boolean
  created_at?: string
  updated_at?: string
}
export interface Role {
  id: number
  name: string
  display_name?: string
  slug: string
  description?: string
  is_system: boolean
  is_active: boolean
  permissions_count?: number
  users_count?: number
  permissions?: Permission[]
  created_at?: string
  updated_at?: string
}

export interface UserRolePivot {
  is_primary: boolean
}

export interface UserRole {
  id: number
  name: string
  slug: string
  is_primary?: boolean
  pivot?: UserRolePivot
}

export interface TemporaryRoleAssignment {
  id: number
  user_id: string
  role_id: number
  role_name?: string
  role_slug?: string
  starts_at: string
  expires_at: string
  assigned_by?: string
  reason?: string
  is_active?: boolean
  user?: {
    id: string
    full_name: string
    email: string
  }
  role?: Role
  assigner?: {
    id: string
    full_name: string
  }
  created_at?: string
}

export interface RbacUserSummary {
  id: string
  full_name: string
  email: string
  phone?: string
  legacy_role?: string
  primary_role_name?: string
  primary_role_slug?: string
  is_active: boolean
  roles: UserRole[]
  active_roles: string[]
  temporary_assignments: TemporaryRoleAssignment[]
  direct_permissions_count?: number
  direct_permission_ids?: number[]
  effective_permissions_count: number
}

export interface RbacAuditLogItem {
  id: number
  user_id?: string
  action: string
  target_type?: string
  target_id?: string
  old_values?: Record<string, any> | null
  new_values?: Record<string, any> | null
  ip_address?: string
  user_agent?: string
  created_at: string
  user?: {
    id: string
    full_name: string
    email: string
  }
}
