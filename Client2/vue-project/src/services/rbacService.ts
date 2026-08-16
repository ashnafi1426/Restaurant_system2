import api from '../api/auth'
import type {
  Role,
  Permission,
  RbacUserSummary,
  TemporaryRoleAssignment,
  RbacAuditLogItem
} from '../types/rbacTypes'

export const rbacService = {
  // Roles API
  async getRoles(): Promise<Role[]> {
    try {
      const response = await api.get('/roles')
      return response.data.data
    } catch (e) {
      return this.getActiveRoles()
    }
  },

  async getActiveRoles(): Promise<Role[]> {
    try {
      const response = await api.get('/roles/active')
      return response.data.data
    } catch (e) {
      const response = await api.get('/public/roles')
      return response.data.data
    }
  },

  async getRole(id: number): Promise<Role> {
    const response = await api.get(`/roles/${id}`)
    return response.data.data
  },

  async createRole(data: { name: string; description?: string; is_active?: boolean; permissions?: number[] }): Promise<Role> {
    const response = await api.post('/roles', data)
    return response.data.data
  },

  async updateRole(id: number, data: { name?: string; description?: string; is_active?: boolean }): Promise<Role> {
    const response = await api.put(`/roles/${id}`, data)
    return response.data.data
  },

  async deleteRole(id: number): Promise<void> {
    await api.delete(`/roles/${id}`)
  },

  async getRolePermissions(roleId: number): Promise<{ permission_ids: number[]; data: Permission[] }> {
    const response = await api.get(`/roles/${roleId}/permissions`)
    return response.data
  },

  async syncRolePermissions(roleId: number, permissionIds: number[]): Promise<Role> {
    const response = await api.post(`/roles/${roleId}/permissions`, { permission_ids: permissionIds })
    return response.data.data
  },

  // Permissions API
  async getPermissions(): Promise<{ data: Permission[]; grouped: Record<string, Permission[]> }> {
    const response = await api.get('/permissions')
    return response.data
  },

  async createPermission(data: { name: string; module: string; action: string; description?: string }): Promise<Permission> {
    const response = await api.post('/permissions', data)
    return response.data.data
  },

  async updatePermission(id: number, data: Partial<Permission>): Promise<Permission> {
    const response = await api.put(`/permissions/${id}`, data)
    return response.data.data
  },

  async deletePermission(id: number): Promise<void> {
    await api.delete(`/permissions/${id}`)
  },

  // User Roles API
  async getUserRoleSummaries(): Promise<RbacUserSummary[]> {
    const response = await api.get('/user-roles')
    return response.data.data
  },

  async getUserRoles(userId: string): Promise<any> {
    const response = await api.get(`/users/${userId}/roles`)
    return response.data
  },

  async assignUserRoles(userId: string, roleIds: number[], primaryRoleId?: number): Promise<any> {
    const response = await api.post(`/users/${userId}/roles`, {
      role_ids: roleIds,
      primary_role_id: primaryRoleId
    })
    return response.data
  },

  async removeUserRole(userId: string, roleId: number): Promise<void> {
    await api.delete(`/users/${userId}/roles/${roleId}`)
  },

  // Temporary Roles API
  async getTemporaryRoles(): Promise<TemporaryRoleAssignment[]> {
    const response = await api.get('/temporary-roles')
    return response.data.data
  },

  async createTemporaryRole(data: {
    user_id: string
    role_id: number
    starts_at: string
    expires_at: string
    reason: string
  }): Promise<TemporaryRoleAssignment> {
    const response = await api.post(`/users/${data.user_id}/temporary-role`, data)
    return response.data.data
  },

  async revokeTemporaryRole(id: number): Promise<void> {
    await api.delete(`/temporary-roles/${id}`)
  },

  // User Direct Permissions API
  async getUserDirectPermissions(userId: string): Promise<any> {
    const response = await api.get(`/users/${userId}/direct-permissions`)
    return response.data
  },

  async saveUserDirectPermissions(
    userId: string,
    permissionIds: number[],
    options?: { starts_at?: string | null; expires_at?: string | null }
  ): Promise<any> {
    const response = await api.post(`/users/${userId}/direct-permissions`, {
      permission_ids: permissionIds,
      starts_at: options?.starts_at || null,
      expires_at: options?.expires_at || null,
    })
    return response.data
  },

  async removeUserDirectPermission(userId: string, permissionId: number): Promise<any> {
    const response = await api.delete(`/users/${userId}/direct-permissions/${permissionId}`)
    return response.data
  },

  // Audit Logs API
  async getAuditLogs(page = 1): Promise<{ data: RbacAuditLogItem[]; pagination: any }> {
    const response = await api.get(`/audit-logs?page=${page}`)
    return response.data
  }
}
