<template>
  <div class="waiter-table">
    <div class="table-responsive">
      <table class="data-table">
        <thead>
          <tr>
            <th>Name</th>
            <th>Email</th>
            <th>Phone</th>
            <th>Type</th>
            <th>Status</th>
            <th>Availability</th>
            <th>Orders</th>
            <th>Actions</th>
          </tr>
        </thead>
        <tbody>
          <tr v-for="waiter in waiters" :key="waiter.id" :class="getRowClass(waiter)">
            <td>
              <div class="waiter-name">
                <img v-if="waiter.profile_photo" :src="waiter.profile_photo" :alt="waiter.user.name" class="avatar" />
                <div v-else class="avatar placeholder">
                  {{ waiter.user.name.charAt(0) }}
                </div>
                {{ waiter.user.name }}
              </div>
            </td>
            <td>{{ waiter.user.email }}</td>
            <td>{{ waiter.phone }}</td>
            <td>
              <span class="badge" :class="'badge-' + waiter.employment_type">
                {{ employmentTypeLabel(waiter.employment_type) }}
              </span>
            </td>
            <td>
              <span class="badge" :class="'badge-' + waiter.status">
                {{ statusLabel(waiter.status) }}
              </span>
            </td>
            <td>
              <span class="badge" :class="'badge-' + waiter.availability">
                {{ availabilityLabel(waiter.availability) }}
              </span>
            </td>
            <td>
              <div class="orders-bar">
                <div class="progress-bar">
                  <div
                    class="progress-fill"
                    :style="{ width: (waiter.current_orders / waiter.maximum_orders) * 100 + '%' }"
                    :class="{ 'full': waiter.is_busy }"
                  />
                </div>
                <span class="orders-text">{{ waiter.current_orders }}/{{ waiter.maximum_orders }}</span>
              </div>
            </td>
            <td>
              <div class="action-buttons">
                <button class="btn-icon" @click="$emit('view', waiter)" title="View Details">
                  <i class="icon-eye" />
                </button>
                <button class="btn-icon" @click="$emit('edit', waiter)" title="Edit">
                  <i class="icon-edit" />
                </button>
                <div class="dropdown">
                  <button class="btn-icon" title="More Options">
                    <i class="icon-more" />
                  </button>
                  <div class="dropdown-menu">
                    <button @click="$emit('change-availability', waiter)">Change Availability</button>
                    <button @click="$emit('toggle-status', waiter)">
                      {{ waiter.status === 'active' ? 'Deactivate' : 'Reactivate' }}
                    </button>
                    <button v-if="waiter.status === 'active'" @click="$emit('suspend', waiter)">Suspend</button>
                    <button @click="$emit('delete', waiter)" class="btn-danger">Delete</button>
                  </div>
                </div>
              </div>
            </td>
          </tr>
        </tbody>
      </table>
    </div>

    <div v-if="waiters.length === 0" class="empty-state">
      <p>No waiters found</p>
    </div>
  </div>
</template>

<script setup lang="ts">
import type { Waiter } from '@/services/manager/waiterManagementService'

withDefaults(
  defineProps<{
    waiters: Waiter[]
  }>(),
  {}
)

defineEmits<{
  view: [waiter: Waiter]
  edit: [waiter: Waiter]
  'change-availability': [waiter: Waiter]
  'toggle-status': [waiter: Waiter]
  suspend: [waiter: Waiter]
  delete: [waiter: Waiter]
}>()

function getRowClass(waiter: Waiter) {
  const classes = []
  if (waiter.status === 'suspended') classes.push('suspended')
  if (waiter.is_busy) classes.push('busy')
  return classes.join(' ')
}

function statusLabel(status: string) {
  const labels: Record<string, string> = {
    active: 'Active',
    inactive: 'Inactive',
    suspended: 'Suspended',
  }
  return labels[status] || status
}

function availabilityLabel(availability: string) {
  const labels: Record<string, string> = {
    available: 'Available',
    busy: 'Busy',
    break: 'On Break',
    offline: 'Offline',
  }
  return labels[availability] || availability
}

function employmentTypeLabel(type: string) {
  const labels: Record<string, string> = {
    full_time: 'Full Time',
    part_time: 'Part Time',
    contract: 'Contract',
  }
  return labels[type] || type
}
</script>

<style scoped>
.waiter-table {
  background: white;
  border-radius: 8px;
  overflow: hidden;
}

.table-responsive {
  overflow-x: auto;
}

.data-table {
  width: 100%;
  border-collapse: collapse;
  font-size: 14px;
}

.data-table thead {
  background: #f5f5f5;
  border-bottom: 2px solid #e0e0e0;
}

.data-table th {
  padding: 12px;
  text-align: left;
  font-weight: 600;
  color: #333;
}

.data-table td {
  padding: 12px;
  border-bottom: 1px solid #f0f0f0;
}

.data-table tbody tr:hover {
  background: #fafafa;
}

.data-table tbody tr.suspended {
  opacity: 0.6;
}

.data-table tbody tr.busy {
  background: #fff9f5;
}

.waiter-name {
  display: flex;
  align-items: center;
  gap: 8px;
}

.avatar {
  width: 32px;
  height: 32px;
  border-radius: 50%;
  object-fit: cover;
}

.avatar.placeholder {
  background: #e0e0e0;
  display: flex;
  align-items: center;
  justify-content: center;
  font-weight: 600;
  color: #666;
}

.badge {
  display: inline-block;
  padding: 4px 8px;
  border-radius: 4px;
  font-size: 12px;
  font-weight: 500;
}

.badge-active {
  background: #e8f5e9;
  color: #2e7d32;
}

.badge-inactive {
  background: #f5f5f5;
  color: #666;
}

.badge-suspended {
  background: #ffebee;
  color: #c62828;
}

.badge-available {
  background: #e3f2fd;
  color: #1565c0;
}

.badge-busy {
  background: #fff3e0;
  color: #e65100;
}

.badge-break {
  background: #f3e5f5;
  color: #6a1b9a;
}

.badge-offline {
  background: #eceff1;
  color: #455a64;
}

.badge-full_time {
  background: #e8f5e9;
  color: #2e7d32;
}

.badge-part_time {
  background: #e0f2f1;
  color: #00695c;
}

.badge-contract {
  background: #ede7f6;
  color: #512da8;
}

.orders-bar {
  display: flex;
  align-items: center;
  gap: 8px;
}

.progress-bar {
  flex: 1;
  height: 6px;
  background: #f0f0f0;
  border-radius: 3px;
  overflow: hidden;
  min-width: 100px;
}

.progress-fill {
  height: 100%;
  background: #4caf50;
  transition: background 0.2s;
}

.progress-fill.full {
  background: #ff9800;
}

.orders-text {
  font-size: 12px;
  font-weight: 500;
  color: #666;
  min-width: 40px;
}

.action-buttons {
  display: flex;
  gap: 8px;
  align-items: center;
}

.btn-icon {
  width: 32px;
  height: 32px;
  border: none;
  background: #f0f0f0;
  border-radius: 4px;
  cursor: pointer;
  display: flex;
  align-items: center;
  justify-content: center;
  transition: background 0.2s;
}

.btn-icon:hover {
  background: #e0e0e0;
}

.dropdown {
  position: relative;
}

.dropdown-menu {
  display: none;
  position: absolute;
  right: 0;
  top: 100%;
  background: white;
  border: 1px solid #e0e0e0;
  border-radius: 4px;
  min-width: 150px;
  box-shadow: 0 2px 8px rgba(0, 0, 0, 0.1);
  z-index: 100;
  margin-top: 4px;
}

.dropdown:hover .dropdown-menu {
  display: block;
}

.dropdown-menu button {
  display: block;
  width: 100%;
  padding: 8px 12px;
  border: none;
  background: none;
  text-align: left;
  cursor: pointer;
  font-size: 14px;
  transition: background 0.2s;
}

.dropdown-menu button:hover {
  background: #f5f5f5;
}

.dropdown-menu button.btn-danger {
  color: #c62828;
}

.dropdown-menu button.btn-danger:hover {
  background: #ffebee;
}

.empty-state {
  padding: 40px;
  text-align: center;
  color: #999;
}
</style>
