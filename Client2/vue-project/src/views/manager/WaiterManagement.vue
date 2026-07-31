<template>
  <DashboardLayout>
  <div class="waiter-management">
    <div class="page-header">
      <div class="header-content">
        <h1>Waiter Management</h1>
        <p>Manage hotel waiters, assignments, and performance</p>
      </div>
      <button class="btn btn-primary" @click="showRegisterModal = true">
        <i class="icon-plus" /> Register New Waiter
      </button>
    </div>

    <!-- Stats Cards -->
    <div class="stats-grid">
      <div class="stat-card">
        <div class="stat-icon active">
          <i class="icon-check-circle" />
        </div>
        <div class="stat-content">
          <h3>{{ totalWaiters }}</h3>
          <p>Total Waiters</p>
        </div>
      </div>

      <div class="stat-card">
        <div class="stat-icon success">
          <i class="icon-user-check" />
        </div>
        <div class="stat-content">
          <h3>{{ activeWaiters.length }}</h3>
          <p>Active Waiters</p>
        </div>
      </div>

      <div class="stat-card">
        <div class="stat-icon warning">
          <i class="icon-alert-circle" />
        </div>
        <div class="stat-content">
          <h3>{{ busyWaiters.length }}</h3>
          <p>Currently Busy</p>
        </div>
      </div>

      <div class="stat-card">
        <div class="stat-icon info">
          <i class="icon-available" />
        </div>
        <div class="stat-content">
          <h3>{{ availableWaiters.length }}</h3>
          <p>Available Now</p>
        </div>
      </div>
    </div>

    <!-- Filters -->
    <div class="filters-section">
      <div class="search-box">
        <input
          v-model="searchQuery"
          type="text"
          placeholder="Search by name, email, or phone..."
          @input="handleSearch"
        />
        <i class="icon-search" />
      </div>

      <div class="filter-buttons">
        <button
          v-for="status in ['All', 'Active', 'Inactive', 'Suspended']"
          :key="status"
          class="filter-btn"
          :class="{ active: selectedFilter === status }"
          @click="handleFilter(status)"
        >
          {{ status }}
        </button>
      </div>
    </div>

    <!-- Error Message -->
    <div v-if="error" class="alert alert-error">
      {{ error }}
      <button @click="clearError" class="btn-close">×</button>
    </div>

    <!-- Loading State -->
    <div v-if="isLoading" class="loading-state">
      <div class="spinner" />
      <p>Loading waiters...</p>
    </div>

    <!-- Waiter Table -->
    <div v-else class="table-section">
      <WaiterTable
        :waiters="waiters"
        @view="handleViewWaiter"
        @edit="handleEditWaiter"
        @change-availability="handleChangeAvailability"
        @toggle-status="handleToggleStatus"
        @suspend="handleSuspendWaiter"
        @delete="handleDeleteWaiter"
      />

      <!-- Pagination -->
      <div v-if="totalPages > 1" class="pagination">
        <button
          :disabled="currentPage === 1"
          @click="goToPage(currentPage - 1)"
          class="btn btn-secondary"
        >
          Previous
        </button>

        <div class="page-numbers">
          <button
            v-for="page in totalPages"
            :key="page"
            @click="goToPage(page)"
            class="page-btn"
            :class="{ active: page === currentPage }"
          >
            {{ page }}
          </button>
        </div>

        <button
          :disabled="currentPage === totalPages"
          @click="goToPage(currentPage + 1)"
          class="btn btn-secondary"
        >
          Next
        </button>
      </div>
    </div>

    <!-- Register Modal -->
    <div v-if="showRegisterModal" class="modal-overlay" @click="closeModal">
      <div class="modal-content" @click.stop>
        <div class="modal-header">
          <h2>Register New Waiter</h2>
          <button @click="closeModal" class="btn-close">×</button>
        </div>

        <form @submit.prevent="submitRegister" class="form">
          <div class="form-row">
            <div class="form-group">
              <label>First Name *</label>
              <input v-model="registerForm.first_name" type="text" required />
            </div>
            <div class="form-group">
              <label>Last Name *</label>
              <input v-model="registerForm.last_name" type="text" required />
            </div>
          </div>

          <div class="form-group">
            <label>Email *</label>
            <input v-model="registerForm.email" type="email" required />
          </div>

          <div class="form-row">
            <div class="form-group">
              <label>Phone *</label>
              <input v-model="registerForm.phone" type="tel" required />
            </div>
            <div class="form-group">
              <label>Employee Number</label>
              <input v-model="registerForm.employee_number" type="text" />
            </div>
          </div>

          <div class="form-row">
            <div class="form-group">
              <label>Employment Type *</label>
              <select v-model="registerForm.employment_type" required>
                <option value="full_time">Full Time</option>
                <option value="part_time">Part Time</option>
                <option value="contract">Contract</option>
              </select>
            </div>
            <div class="form-group">
              <label>Hire Date *</label>
              <input v-model="registerForm.hire_date" type="date" required />
            </div>
          </div>

          <div class="form-row">
            <div class="form-group">
              <label>Password *</label>
              <input v-model="registerForm.password" type="password" required />
            </div>
            <div class="form-group">
              <label>Confirm Password *</label>
              <input v-model="registerForm.password_confirmation" type="password" required />
            </div>
          </div>

          <div class="form-group">
            <label>Maximum Orders *</label>
            <input v-model.number="registerForm.maximum_orders" type="number" min="1" max="20" required />
          </div>

          <div class="modal-footer">
            <button type="button" @click="closeModal" class="btn btn-secondary">
              Cancel
            </button>
            <button type="submit" class="btn btn-primary" :disabled="isSubmitting">
              {{ isSubmitting ? 'Registering...' : 'Register Waiter' }}
            </button>
          </div>
        </form>
      </div>
    </div>
  </div>
  </DashboardLayout>
</template>

<script setup lang="ts">
import { ref, computed, onMounted } from 'vue'
import { useWaiterManagementStore } from '@/stores/manager/waiterManagementStore'
import WaiterTable from '@/components/manager/WaiterTable.vue'
import DashboardLayout from '../../Layouts/DashboardLayout.vue'

const store = useWaiterManagementStore()

const showRegisterModal = ref(false)
const isSubmitting = ref(false)
const selectedFilter = ref('All')
const searchQuery = ref('')

const registerForm = ref({
  first_name: '',
  last_name: '',
  email: '',
  phone: '',
  password: '',
  password_confirmation: '',
  employment_type: 'full_time',
  hire_date: new Date().toISOString().split('T')[0],
  maximum_orders: 10,
  employee_number: '',
})

// Computed
const waiters = computed(() => store.waiters)
const isLoading = computed(() => store.isLoading)
const error = computed(() => store.error)
const currentPage = computed(() => store.currentPage)
const totalWaiters = computed(() => store.totalWaiters)
const totalPages = computed(() => store.totalPages)
const activeWaiters = computed(() => store.activeWaiters)
const busyWaiters = computed(() => store.busyWaiters)
const availableWaiters = computed(() => store.availableWaiters)

// Methods
onMounted(() => {
  store.fetchWaiters()
})

async function handleSearch() {
  await store.fetchWaiters(1, searchQuery.value, null)
}

function handleFilter(status: string) {
  selectedFilter.value = status
  const filterStatus = status === 'All' ? null : status.toLowerCase()
  store.fetchWaiters(1, searchQuery.value, filterStatus)
}

function goToPage(page: number) {
  store.fetchWaiters(page, searchQuery.value, null)
}

async function submitRegister() {
  isSubmitting.value = true
  try {
    await store.registerWaiter(registerForm.value)
    closeModal()
    // Reset form
    registerForm.value = {
      first_name: '',
      last_name: '',
      email: '',
      phone: '',
      password: '',
      password_confirmation: '',
      employment_type: 'full_time',
      hire_date: new Date().toISOString().split('T')[0],
      maximum_orders: 10,
      employee_number: '',
    }
  } catch (err) {
    console.error('Error registering waiter:', err)
  } finally {
    isSubmitting.value = false
  }
}

function closeModal() {
  showRegisterModal.value = false
}

function handleViewWaiter(waiter: any) {
  // TODO: Open detail view
  console.log('View waiter:', waiter)
}

function handleEditWaiter(waiter: any) {
  // TODO: Open edit modal
  console.log('Edit waiter:', waiter)
}

async function handleChangeAvailability(waiter: any) {
  const newStatus = prompt(
    'Select availability:\navailable\nbusy\nbreak\noffline',
    waiter.availability
  )
  if (newStatus) {
    await store.changeAvailability(waiter.id, newStatus)
  }
}

async function handleToggleStatus(waiter: any) {
  const action = waiter.status === 'active' ? 'deactivate' : 'reactivate'
  if (confirm(`Are you sure you want to ${action} this waiter?`)) {
    if (waiter.status === 'active') {
      await store.deactivateWaiter(waiter.id)
    } else {
      await store.reactivateWaiter(waiter.id)
    }
  }
}

async function handleSuspendWaiter(waiter: any) {
  const reason = prompt('Enter suspension reason:')
  if (reason) {
    await store.suspendWaiter(waiter.id, reason)
  }
}

async function handleDeleteWaiter(waiter: any) {
  if (confirm(`Are you sure you want to delete ${waiter.user.name}?`)) {
    await store.deleteWaiter(waiter.id)
  }
}

function clearError() {
  store.clearError()
}
</script>

<style scoped>
.waiter-management {
  padding: 24px;
  background: #f5f5f5;
  min-height: 100vh;
}

.page-header {
  display: flex;
  justify-content: space-between;
  align-items: center;
  margin-bottom: 32px;
}

.header-content h1 {
  font-size: 28px;
  margin: 0 0 4px 0;
  color: #333;
}

.header-content p {
  margin: 0;
  color: #999;
  font-size: 14px;
}

.stats-grid {
  display: grid;
  grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
  gap: 16px;
  margin-bottom: 32px;
}

.stat-card {
  background: white;
  padding: 20px;
  border-radius: 8px;
  display: flex;
  align-items: center;
  gap: 16px;
  box-shadow: 0 1px 3px rgba(0, 0, 0, 0.1);
}

.stat-icon {
  width: 56px;
  height: 56px;
  border-radius: 50%;
  display: flex;
  align-items: center;
  justify-content: center;
  font-size: 24px;
}

.stat-icon.active {
  background: #e8f5e9;
  color: #4caf50;
}

.stat-icon.success {
  background: #e3f2fd;
  color: #2196f3;
}

.stat-icon.warning {
  background: #fff3e0;
  color: #ff9800;
}

.stat-icon.info {
  background: #f3e5f5;
  color: #9c27b0;
}

.stat-content h3 {
  margin: 0;
  font-size: 24px;
  font-weight: 600;
  color: #333;
}

.stat-content p {
  margin: 4px 0 0 0;
  color: #999;
  font-size: 12px;
}

.filters-section {
  background: white;
  padding: 20px;
  border-radius: 8px;
  margin-bottom: 24px;
  display: flex;
  gap: 16px;
  align-items: center;
}

.search-box {
  flex: 1;
  position: relative;
}

.search-box input {
  width: 100%;
  padding: 10px 16px 10px 32px;
  border: 1px solid #e0e0e0;
  border-radius: 4px;
  font-size: 14px;
}

.search-box i {
  position: absolute;
  left: 12px;
  top: 50%;
  transform: translateY(-50%);
  color: #999;
}

.filter-buttons {
  display: flex;
  gap: 8px;
}

.filter-btn {
  padding: 8px 16px;
  border: 1px solid #e0e0e0;
  background: white;
  border-radius: 4px;
  cursor: pointer;
  font-size: 14px;
  transition: all 0.2s;
}

.filter-btn:hover {
  border-color: #2196f3;
  color: #2196f3;
}

.filter-btn.active {
  background: #2196f3;
  border-color: #2196f3;
  color: white;
}

.alert {
  padding: 16px;
  border-radius: 4px;
  margin-bottom: 16px;
  display: flex;
  justify-content: space-between;
  align-items: center;
}

.alert-error {
  background: #ffebee;
  color: #c62828;
  border: 1px solid #ef5350;
}

.btn-close {
  background: none;
  border: none;
  font-size: 20px;
  cursor: pointer;
  color: inherit;
}

.loading-state {
  text-align: center;
  padding: 60px 20px;
  background: white;
  border-radius: 8px;
}

.spinner {
  width: 40px;
  height: 40px;
  border: 4px solid #f0f0f0;
  border-top-color: #2196f3;
  border-radius: 50%;
  animation: spin 1s linear infinite;
  margin: 0 auto 16px;
}

@keyframes spin {
  to {
    transform: rotate(360deg);
  }
}

.table-section {
  background: white;
  border-radius: 8px;
  overflow: hidden;
  box-shadow: 0 1px 3px rgba(0, 0, 0, 0.1);
}

.pagination {
  display: flex;
  justify-content: center;
  align-items: center;
  gap: 12px;
  padding: 20px;
  border-top: 1px solid #f0f0f0;
}

.page-numbers {
  display: flex;
  gap: 8px;
}

.page-btn {
  width: 36px;
  height: 36px;
  border: 1px solid #e0e0e0;
  background: white;
  border-radius: 4px;
  cursor: pointer;
  font-size: 14px;
  transition: all 0.2s;
}

.page-btn:hover {
  border-color: #2196f3;
  color: #2196f3;
}

.page-btn.active {
  background: #2196f3;
  border-color: #2196f3;
  color: white;
}

.btn {
  padding: 10px 16px;
  border: none;
  border-radius: 4px;
  cursor: pointer;
  font-size: 14px;
  font-weight: 500;
  transition: all 0.2s;
  display: inline-flex;
  align-items: center;
  gap: 8px;
}

.btn-primary {
  background: #2196f3;
  color: white;
}

.btn-primary:hover {
  background: #1976d2;
}

.btn-primary:disabled {
  background: #ccc;
  cursor: not-allowed;
}

.btn-secondary {
  background: #f5f5f5;
  color: #333;
  border: 1px solid #e0e0e0;
}

.btn-secondary:hover:not(:disabled) {
  background: #eeeeee;
}

.btn-secondary:disabled {
  opacity: 0.5;
  cursor: not-allowed;
}

/* Modal Styles */
.modal-overlay {
  position: fixed;
  top: 0;
  left: 0;
  right: 0;
  bottom: 0;
  background: rgba(0, 0, 0, 0.5);
  display: flex;
  align-items: center;
  justify-content: center;
  z-index: 1000;
}

.modal-content {
  background: white;
  border-radius: 8px;
  max-width: 500px;
  width: 90%;
  max-height: 90vh;
  overflow-y: auto;
  box-shadow: 0 4px 16px rgba(0, 0, 0, 0.2);
}

.modal-header {
  display: flex;
  justify-content: space-between;
  align-items: center;
  padding: 20px;
  border-bottom: 1px solid #f0f0f0;
}

.modal-header h2 {
  margin: 0;
  font-size: 20px;
  color: #333;
}

.modal-header .btn-close {
  font-size: 24px;
}

.form {
  padding: 20px;
}

.form-group {
  margin-bottom: 16px;
}

.form-group label {
  display: block;
  margin-bottom: 6px;
  font-size: 14px;
  font-weight: 500;
  color: #333;
}

.form-group input,
.form-group select {
  width: 100%;
  padding: 10px;
  border: 1px solid #e0e0e0;
  border-radius: 4px;
  font-size: 14px;
  font-family: inherit;
}

.form-group input:focus,
.form-group select:focus {
  outline: none;
  border-color: #2196f3;
  box-shadow: 0 0 0 3px rgba(33, 150, 243, 0.1);
}

.form-row {
  display: grid;
  grid-template-columns: 1fr 1fr;
  gap: 16px;
}

.modal-footer {
  display: flex;
  gap: 12px;
  justify-content: flex-end;
  padding: 20px;
  border-top: 1px solid #f0f0f0;
}
</style>
