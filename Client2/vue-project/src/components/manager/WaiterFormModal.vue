<template>
  <Teleport to="body">
    <Transition name="modal">
      <div v-if="isOpen" class="modal-overlay" @click.self="close">
        <div class="modal-container">
            <!-- Modal Header -->
            <div class="modal-header">
              <div class="header-content">
                <div class="header-icon">
                  <UserPlus :size="20" />
                </div>
                <div class="header-text">
                  <h2>{{ props.isEditMode ? 'Edit Waiter' : 'Register New Waiter' }}</h2>
                  <p class="header-subtitle">{{ props.isEditMode ? 'Update waiter information' : 'Create waiter account' }}</p>
                </div>
              </div>
              <button class="btn-close" @click="close" type="button" title="Close">
                <X :size="18" />
              </button>
            </div>

      <!-- Modal Body with Two Columns -->
      <div class="modal-body">
        <form @submit.prevent="submitForm" class="form-container">
          <!-- Left Column -->
          <div class="form-column">
            <!-- Personal Information Section -->
            <div class="form-section">
              <div class="section-header">
                <div class="section-icon person-icon">👤</div>
                <h3>Personal</h3>
              </div>

              <div class="form-row">
                <div class="form-group">
                  <label for="first_name">First Name {{ props.isEditMode ? '(Read-only)' : '*' }}</label>
                  <input
                    id="first_name"
                    v-model="newUserData.first_name"
                    type="text"
                    placeholder="John"
                    class="form-control"
                    :disabled="props.isEditMode"
                    required
                  />
                  <span v-if="fieldErrors.first_name" class="error">{{ fieldErrors.first_name }}</span>
                </div>
                <div class="form-group">
                  <label for="last_name">Last Name {{ props.isEditMode ? '(Read-only)' : '*' }}</label>
                  <input
                    id="last_name"
                    v-model="newUserData.last_name"
                    type="text"
                    placeholder="Smith"
                    class="form-control"
                    :disabled="props.isEditMode"
                    required
                  />
                  <span v-if="fieldErrors.last_name" class="error">{{ fieldErrors.last_name }}</span>
                </div>
              </div>

              <div class="form-group">
                <label for="email">Email {{ props.isEditMode ? '(Read-only)' : '*' }}</label>
                <input
                  id="email"
                  v-model="newUserData.email"
                  type="email"
                  placeholder="john@example.com"
                  class="form-control"
                  :disabled="props.isEditMode"
                  required
                />
                <span v-if="fieldErrors.email" class="error">{{ fieldErrors.email }}</span>
              </div>

              <div class="form-group">
                <label for="phone">Phone {{ props.isEditMode ? '(Editable)' : '*' }}</label>
                <input
                  id="phone"
                  v-model="newUserData.phone"
                  type="tel"
                  placeholder="+1 234567890"
                  class="form-control"
                  :required="!props.isEditMode"
                />
                <span v-if="fieldErrors.phone" class="error">{{ fieldErrors.phone }}</span>
              </div>

              <div class="form-group">
                <label for="employee_number">Employee Number</label>
                <input
                  id="employee_number"
                  v-model="formData.employee_number"
                  type="text"
                  placeholder="e.g., W001"
                  class="form-control"
                />
                <span v-if="fieldErrors.employee_number" class="error">{{ fieldErrors.employee_number }}</span>
              </div>
            </div>

            <!-- Info Note for Activation -->
            <div v-if="!props.isEditMode" class="form-section">
              <div class="info-banner">
                <div class="info-icon">ℹ️</div>
                <div class="info-content">
                  <h4>Account Activation</h4>
                  <p>The waiter will receive an email with an activation link to set their own password.</p>
                </div>
              </div>
            </div>
          </div>

          <!-- Right Column -->
          <div class="form-column">
            <!-- Assignment Section -->
            <div class="form-section">
              <div class="section-header">
                <div class="section-icon work-icon">👨‍💼</div>
                <h3>Assignment</h3>
              </div>

              <div class="form-group">
                <label for="section">Section *</label>
                <input
                  id="section"
                  v-model="formData.section"
                  type="text"
                  placeholder="e.g., Restaurant A, Table Section 1"
                  class="form-control"
                  required
                />
                <span v-if="fieldErrors.section" class="error">{{ fieldErrors.section }}</span>
              </div>

              <div class="form-group">
                <label for="shift">Shift *</label>
                <select id="shift" v-model="formData.shift" class="form-control" required>
                  <option value="">Select...</option>
                  <option value="morning">🌅 Morning</option>
                  <option value="afternoon">🌤️ Afternoon</option>
                  <option value="evening">🌆 Evening</option>
                  <option value="night">🌙 Night</option>
                </select>
                <span v-if="fieldErrors.shift" class="error">{{ fieldErrors.shift }}</span>
              </div>

              <div class="form-group">
                <label for="experience_level">Experience Level *</label>
                <select id="experience_level" v-model="formData.experience_level" class="form-control" required>
                  <option value="">Select...</option>
                  <option value="junior">📚 Junior</option>
                  <option value="senior">⭐ Senior</option>
                  <option value="head">👑 Head</option>
                </select>
                <span v-if="fieldErrors.experience_level" class="error">{{ fieldErrors.experience_level }}</span>
              </div>

              <div class="form-group">
                <label for="maximum_orders">Maximum Orders *</label>
                <select id="maximum_orders" v-model.number="formData.maximum_orders" class="form-control" required>
                  <option value="">Select...</option>
                  <option value="5">5 Orders</option>
                  <option value="8">8 Orders</option>
                  <option value="10">10 Orders</option>
                  <option value="15">15 Orders</option>
                  <option value="20">20 Orders</option>
                </select>
                <span v-if="fieldErrors.maximum_orders" class="error">{{ fieldErrors.maximum_orders }}</span>
              </div>

              <div class="form-group">
                <label>Status *</label>
                <div v-if="props.isEditMode" class="status-group">
                  <label class="status-check">
                    <input v-model="formData.status" type="radio" value="active" />
                    <span>✓ Active</span>
                  </label>
                  <label class="status-check">
                    <input v-model="formData.status" type="radio" value="inactive" />
                    <span>✗ Inactive</span>
                  </label>
                </div>
                <div v-else class="info-note">
                  <span class="status-badge inactive">⏸️ Inactive (Until Activation)</span>
                  <p class="hint">Status will automatically become "Active" when the waiter activates their account.</p>
                </div>
              </div>
            </div>

            <!-- Floor Assignments Section -->
            <div class="form-section">
              <div class="section-header">
                <div class="section-icon floor-icon">🏢</div>
                <h3>Floor Assignments</h3>
              </div>

              <div v-if="loadingFloors" class="loading-message">
                <Loader :size="14" class="spin" />
                Loading floors...
              </div>

              <div v-else class="assignments-list">
                <div v-for="(assignment, index) in formData.floor_assignments" 
                     :key="index" 
                     class="assignment-row">
                  <select v-model="assignment.floor_id" class="form-control" required>
                    <option value="">Select Floor...</option>
                    <option v-for="floor in floors" :key="floor.id" :value="floor.id">
                      Floor {{ floor.floor_number }} - {{ floor.name }}
                    </option>
                  </select>
                  
                  <select v-model="assignment.shift_id" class="form-control" required>
                    <option value="">Select Shift...</option>
                    <option v-for="shift in shifts" :key="shift.id" :value="shift.id">
                      {{ shift.name }} ({{ shift.start_time }} - {{ shift.end_time }})
                    </option>
                  </select>
                  
                  <select v-model="assignment.priority" class="form-control">
                    <option value="primary">⭐ Primary</option>
                    <option value="secondary">👥 Secondary</option>
                    <option value="backup">🔄 Backup</option>
                  </select>
                  
                  <button type="button" @click="removeFloorAssignment(index)" class="btn-remove" title="Remove">
                    <Trash2 :size="14" />
                  </button>
                </div>
                
                <button type="button" @click="addFloorAssignment" class="btn-add">
                  <Plus :size="14" />
                  Add Floor Assignment
                </button>
                
                <p v-if="formData.floor_assignments.length === 0" class="hint warning-hint">
                  💡 Add at least one floor assignment to enable automatic order routing
                </p>
              </div>
            </div>
          </div>
        </form>

        <!-- Error Alert -->
        <div v-if="errorMessage" class="alert alert-error">
          <AlertCircle :size="14" />
          <div>
            <p class="alert-title">Error</p>
            <p class="alert-msg">{{ errorMessage }}</p>
          </div>
        </div>
      </div>

      <!-- Modal Footer -->
      <div class="modal-footer">
        <button type="button" class="btn btn-secondary" @click="close">Cancel</button>
        <button type="submit" class="btn btn-primary" @click="submitForm" :disabled="submitting">
          <Loader v-if="submitting" :size="14" class="spin" />
          {{ submitting ? (props.isEditMode ? 'Updating...' : 'Registering...') : (props.isEditMode ? 'Update' : 'Register') }}
        </button>
      </div>
        </div>
      </div>
    </Transition>
  </Teleport>
</template>

<script setup lang="ts">
import { ref, watch, onMounted } from 'vue'
import { X, UserPlus, AlertCircle, Loader, Plus, Trash2 } from 'lucide-vue-next'
import { floorService } from '@/services/manager/floorService'
import { shiftService } from '@/services/manager/shiftService'

interface Props {
  isOpen: boolean
  isEditMode?: boolean
  waiterData?: any
}

const props = withDefaults(defineProps<Props>(), {
  isEditMode: false,
})

const emit = defineEmits<{
  (e: 'close'): void
  (e: 'submit', data: any): void
}>()

const submitting = ref(false)
const errorMessage = ref('')
const fieldErrors = ref<Record<string, string>>({})

const floors = ref<any[]>([])
const shifts = ref<any[]>([])
const loadingFloors = ref(false)

const formData = ref({
  section: '',
  shift: '',
  experience_level: '',
  status: 'inactive', // Default to inactive for new waiters
  maximum_orders: 5,
  employee_number: '',
  floor_assignments: [] as Array<{
    floor_id: string
    shift_id: string
    priority: string
    assignment_date: string
  }>
})

const newUserData = ref({
  first_name: '',
  last_name: '',
  email: '',
  phone: '',
})

// Load floors and shifts on mount
onMounted(async () => {
  try {
    loadingFloors.value = true
    const [floorsRes, shiftsRes] = await Promise.all([
      floorService.getFloors(),
      shiftService.getShifts()
    ])
    
    // Extract data from paginated response
    floors.value = floorsRes.data?.data || floorsRes.data || []
    shifts.value = shiftsRes.data || []
    
    console.log('[WaiterFormModal] Loaded floors:', floors.value.length)
    console.log('[WaiterFormModal] Loaded shifts:', shifts.value.length)
  } catch (error) {
    console.error('[WaiterFormModal] Error loading floors/shifts:', error)
    errorMessage.value = 'Failed to load floors and shifts'
  } finally {
    loadingFloors.value = false
  }
})

watch(() => props.isOpen, (newVal) => {
  if (newVal) {
    if (props.isEditMode && props.waiterData) {
      // Load existing waiter data for editing
      console.log('[WaiterFormModal] Loading edit data:', props.waiterData)
      formData.value = {
        section: props.waiterData.section || '',
        shift: props.waiterData.shift || '',
        experience_level: props.waiterData.experience_level || props.waiterData.experienceLevel || '',
        status: props.waiterData.status || 'active',
        maximum_orders: props.waiterData.maximum_orders || 5,
        employee_number: props.waiterData.employee_number || '',
        floor_assignments: props.waiterData.floor_assignments || []
      }
      // Don't load user data in edit mode (read-only)
      newUserData.value = {
        first_name: props.waiterData.user?.first_name || '',
        last_name: props.waiterData.user?.last_name || '',
        email: props.waiterData.user?.email || '',
        phone: props.waiterData.phone || props.waiterData.user?.phone || '',
      }
    } else {
      resetForm()
    }
  }
})

const addFloorAssignment = () => {
  formData.value.floor_assignments.push({
    floor_id: '',
    shift_id: '',
    priority: 'primary',
    assignment_date: new Date().toISOString().split('T')[0]
  })
}

const removeFloorAssignment = (index: number) => {
  formData.value.floor_assignments.splice(index, 1)
}

const resetForm = () => {
  formData.value = { 
    section: '', 
    shift: '', 
    experience_level: '', 
    status: 'inactive', 
    maximum_orders: 5, 
    employee_number: '',
    floor_assignments: []
  }
  newUserData.value = { first_name: '', last_name: '', email: '', phone: '' }
  errorMessage.value = ''
  fieldErrors.value = {}
}

const validateForm = (): boolean => {
  fieldErrors.value = {}
  let isValid = true

  // In edit mode, only validate the fields that can be edited
  if (props.isEditMode) {
    // User info is read-only, so skip validation
    if (!formData.value.section?.trim()) {
      fieldErrors.value.section = 'Required'
      isValid = false
    }
    if (!formData.value.shift) {
      fieldErrors.value.shift = 'Required'
      isValid = false
    }
    if (!formData.value.experience_level) {
      fieldErrors.value.experience_level = 'Required'
      isValid = false
    }
    if (!formData.value.maximum_orders) {
      fieldErrors.value.maximum_orders = 'Required'
      isValid = false
    }
    return isValid
  }

  // Create mode - validate all fields (NO PASSWORD NEEDED)
  if (!newUserData.value.first_name?.trim()) {
    fieldErrors.value.first_name = 'Required'
    isValid = false
  }
  if (!newUserData.value.last_name?.trim()) {
    fieldErrors.value.last_name = 'Required'
    isValid = false
  }
  if (!newUserData.value.email?.trim()) {
    fieldErrors.value.email = 'Required'
    isValid = false
  }
  if (!newUserData.value.phone?.trim()) {
    fieldErrors.value.phone = 'Required'
    isValid = false
  }
  if (!formData.value.section?.trim()) {
    fieldErrors.value.section = 'Required'
    isValid = false
  }
  if (!formData.value.shift) {
    fieldErrors.value.shift = 'Required'
    isValid = false
  }
  if (!formData.value.experience_level) {
    fieldErrors.value.experience_level = 'Required'
    isValid = false
  }
  if (!formData.value.maximum_orders) {
    fieldErrors.value.maximum_orders = 'Required'
    isValid = false
  }

  return isValid
}

const submitForm = async () => {
  try {
    errorMessage.value = ''
    if (!validateForm()) {
      errorMessage.value = 'Please fill all required fields'
      return
    }

    submitting.value = true
    const submitData = {
      ...formData.value,
      ...newUserData.value,
    }

    emit('submit', submitData)
    close()
  } catch (error: any) {
    errorMessage.value = error.message || 'Error'
  } finally {
    submitting.value = false
  }
}

const close = () => {
  resetForm()
  emit('close')
}
</script>

<style scoped>
* {
  box-sizing: border-box;
}

/* Modal Overlay - Full Screen with Centering */
.modal-overlay {
  position: fixed;
  top: 0;
  left: 0;
  right: 0;
  bottom: 0;
  background: rgba(0, 0, 0, 0.6);
  backdrop-filter: blur(4px);
  display: flex;
  align-items: center;
  justify-content: center;
  z-index: 9999;
  padding: 20px;
}

/* Modal Container - Centered Content */
.modal-container {
  position: relative;
  background: white;
  border-radius: 12px;
  max-width: 900px;
  width: 100%;
  max-height: 85vh;
  display: flex;
  flex-direction: column;
  box-shadow: 0 20px 40px rgba(0, 0, 0, 0.15);
  animation: slideUp 0.3s ease-out;
}

@keyframes slideUp {
  from { 
    opacity: 0; 
    transform: translateY(20px) scale(0.95); 
  }
  to { 
    opacity: 1; 
    transform: translateY(0) scale(1); 
  }
}

/* Header */
.modal-header {
  background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
  color: white;
  padding: 20px 24px;
  border-radius: 12px 12px 0 0;
  display: flex;
  justify-content: space-between;
  align-items: center;
  gap: 16px;
  flex-shrink: 0;
}

.header-content {
  display: flex;
  gap: 12px;
  align-items: center;
  flex: 1;
}

.header-icon {
  width: 40px;
  height: 40px;
  background: rgba(255, 255, 255, 0.2);
  border-radius: 8px;
  display: flex;
  align-items: center;
  justify-content: center;
  flex-shrink: 0;
}

.header-text h2 {
  margin: 0;
  font-size: 18px;
  font-weight: 700;
}

.header-subtitle {
  margin: 4px 0 0 0;
  font-size: 12px;
  opacity: 0.9;
}

.btn-close {
  background: rgba(255, 255, 255, 0.2);
  border: none;
  color: white;
  padding: 6px;
  border-radius: 6px;
  cursor: pointer;
  flex-shrink: 0;
  transition: 0.2s;
}

.btn-close:hover {
  background: rgba(255, 255, 255, 0.3);
}

/* Body */
.modal-body {
  flex: 1;
  overflow-y: auto;
  padding: 24px;
}

.form-container {
  display: grid;
  grid-template-columns: 1fr 1fr;
  gap: 24px;
  margin-bottom: 16px;
}

.form-column {
  display: flex;
  flex-direction: column;
  gap: 18px;
}

.form-section {
  display: flex;
  flex-direction: column;
  gap: 12px;
}

.section-header {
  display: flex;
  align-items: center;
  gap: 8px;
  margin-bottom: 4px;
}

.section-icon {
  font-size: 18px;
  width: 32px;
  height: 32px;
  display: flex;
  align-items: center;
  justify-content: center;
  border-radius: 6px;
  flex-shrink: 0;
}

.person-icon { background: #e3f2fd; }
.lock-icon { background: #f3e5f5; }
.work-icon { background: #e8f5e9; }
.floor-icon { background: #fff3e0; }

.section-header h3 {
  margin: 0;
  font-size: 12px;
  font-weight: 700;
  color: #1a1a1a;
  text-transform: uppercase;
  letter-spacing: 0.3px;
}

.form-group {
  display: flex;
  flex-direction: column;
  gap: 6px;
}

.form-row {
  display: grid;
  grid-template-columns: 1fr 1fr;
  gap: 12px;
}

label {
  font-size: 12px;
  font-weight: 600;
  color: #2c3e50;
}

.form-control {
  width: 100%;
  padding: 10px 11px;
  border: 1px solid #ddd;
  border-radius: 6px;
  font-size: 13px;
  font-family: inherit;
  background: white;
  color: #2c3e50;
  transition: 0.2s;
}

.form-control:hover {
  border-color: #bbb;
}

.form-control:focus {
  outline: none;
  border-color: #667eea;
  box-shadow: 0 0 0 2px rgba(102, 126, 234, 0.1);
}

.form-control::placeholder {
  color: #aaa;
}

.error {
  font-size: 11px;
  color: #e74c3c;
  font-weight: 500;
}

.hint {
  font-size: 11px;
  color: #999;
  display: block;
  margin: 0;
}

.warning-hint {
  color: #ff9800 !important;
  background: #fff8e1;
  padding: 8px 10px;
  border-radius: 4px;
  border-left: 3px solid #ff9800;
}

/* Info Banner */
.info-banner {
  display: flex;
  gap: 12px;
  padding: 14px 16px;
  background: linear-gradient(135deg, #e3f2fd 0%, #bbdefb 100%);
  border-radius: 8px;
  border-left: 4px solid #2196f3;
  align-items: flex-start;
}

.info-icon {
  font-size: 22px;
  flex-shrink: 0;
  margin-top: 2px;
}

.info-content h4 {
  margin: 0 0 4px 0;
  font-size: 13px;
  font-weight: 700;
  color: #1565c0;
}

.info-content p {
  margin: 0;
  font-size: 12px;
  color: #1976d2;
  line-height: 1.5;
}

.info-note {
  display: flex;
  flex-direction: column;
  gap: 8px;
  padding: 12px;
  background: #f5f5f5;
  border-radius: 6px;
  border-left: 3px solid #ff9800;
}

.status-badge {
  display: inline-flex;
  align-items: center;
  gap: 6px;
  padding: 6px 12px;
  border-radius: 6px;
  font-size: 12px;
  font-weight: 600;
  width: fit-content;
}

.status-badge.inactive {
  background: #fff3e0;
  color: #e65100;
  border: 1px solid #ffb74d;
}

.status-group {
  display: grid;
  grid-template-columns: 1fr 1fr 1fr;
  gap: 8px;
}

.status-check {
  display: flex;
  align-items: center;
  gap: 6px;
  padding: 8px 10px;
  border: 1px solid #ddd;
  border-radius: 6px;
  cursor: pointer;
  font-size: 12px;
  transition: 0.2s;
}

.status-check:hover {
  border-color: #667eea;
  background: #f8f9ff;
}

.status-check input {
  width: 14px;
  height: 14px;
  cursor: pointer;
  accent-color: #667eea;
  margin: 0;
}

.alert {
  display: flex;
  gap: 10px;
  padding: 12px;
  border-radius: 6px;
  font-size: 12px;
  border-left: 3px solid;
  margin-bottom: 16px;
}

.alert-error {
  background: #fef5f5;
  border-left-color: #e74c3c;
  color: #c0392b;
}

.alert-title {
  font-weight: 600;
  margin: 0 0 2px 0;
}

.alert-msg {
  margin: 0;
  font-size: 11px;
}

/* Footer */
.modal-footer {
  display: flex;
  justify-content: flex-end;
  gap: 10px;
  padding: 16px 24px;
  border-top: 1px solid #eee;
  background: #fafbfc;
  flex-shrink: 0;
}

.btn {
  padding: 9px 18px;
  border: none;
  border-radius: 6px;
  font-weight: 600;
  font-size: 12px;
  cursor: pointer;
  transition: 0.2s;
  display: flex;
  align-items: center;
  gap: 6px;
  min-width: 80px;
  justify-content: center;
}

.btn-secondary {
  background: #f0f3f8;
  color: #2c3e50;
  border: 1px solid #ddd;
}

.btn-secondary:hover {
  background: #e8eef5;
}

.btn-primary {
  background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
  color: white;
  box-shadow: 0 2px 8px rgba(102, 126, 234, 0.2);
}

.btn-primary:hover:not(:disabled) {
  transform: translateY(-1px);
  box-shadow: 0 4px 12px rgba(102, 126, 234, 0.3);
}

.btn-primary:disabled {
  opacity: 0.6;
  cursor: not-allowed;
}

.spin {
  animation: spin 1s linear infinite;
}

@keyframes spin {
  to { transform: rotate(360deg); }
}

/* Mobile */
@media (max-width: 768px) {
  .modal-overlay {
    padding: 12px;
  }

  .modal-container {
    max-height: 90vh;
  }

  .form-container {
    grid-template-columns: 1fr;
  }

  .modal-body {
    padding: 16px;
  }

  .modal-footer {
    flex-direction: column-reverse;
  }

  .btn {
    width: 100%;
  }
}

/* Scrollbar */
.modal-body::-webkit-scrollbar {
  width: 4px;
}

.modal-body::-webkit-scrollbar-track {
  background: #f1f1f1;
}

.modal-body::-webkit-scrollbar-thumb {
  background: #ccc;
  border-radius: 2px;
}

.modal-body::-webkit-scrollbar-thumb:hover {
  background: #999;
}

/* Floor Assignments */
.assignments-list {
  display: flex;
  flex-direction: column;
  gap: 10px;
}

.assignment-row {
  display: grid;
  grid-template-columns: 2fr 2fr 1.5fr auto;
  gap: 8px;
  align-items: center;
  padding: 10px;
  background: #f8f9fa;
  border-radius: 6px;
  border: 1px solid #e0e0e0;
  transition: 0.2s;
}

.assignment-row:hover {
  background: #f0f3f8;
  border-color: #667eea;
}

.btn-remove {
  padding: 8px;
  background: #fee;
  border: 1px solid #fcc;
  border-radius: 6px;
  color: #e74c3c;
  cursor: pointer;
  transition: 0.2s;
  display: flex;
  align-items: center;
  justify-content: center;
  flex-shrink: 0;
}

.btn-remove:hover {
  background: #fdd;
  border-color: #e74c3c;
}

.btn-add {
  display: flex;
  align-items: center;
  justify-content: center;
  gap: 6px;
  padding: 10px 14px;
  background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
  color: white;
  border: none;
  border-radius: 6px;
  font-size: 12px;
  font-weight: 600;
  cursor: pointer;
  transition: 0.2s;
  margin-top: 4px;
}

.btn-add:hover {
  transform: translateY(-1px);
  box-shadow: 0 4px 12px rgba(102, 126, 234, 0.3);
}

.loading-message {
  display: flex;
  align-items: center;
  gap: 8px;
  padding: 12px;
  background: #f8f9fa;
  border-radius: 6px;
  font-size: 12px;
  color: #666;
}

/* Vue Transition Animations */
.modal-enter-active,
.modal-leave-active {
  transition: opacity 0.3s ease;
}

.modal-enter-from,
.modal-leave-to {
  opacity: 0;
}

.modal-enter-active .modal-container,
.modal-leave-active .modal-container {
  transition: transform 0.3s ease, opacity 0.3s ease;
}

.modal-enter-from .modal-container,
.modal-leave-to .modal-container {
  transform: scale(0.95) translateY(20px);
  opacity: 0;
}

/* ==========================================================================
   Dark Mode Support
   ========================================================================== */
:global(.dark) .modal-container,
.dark .modal-container {
  background: #0f172a !important;
  color: #f8fafc !important;
  border: 1px solid #1e293b !important;
  box-shadow: 0 25px 50px -12px rgba(0, 0, 0, 0.5) !important;
}

:global(.dark) .modal-body,
.dark .modal-body {
  background: #0f172a !important;
}

:global(.dark) label,
.dark label {
  color: #cbd5e1 !important;
}

:global(.dark) .section-header h3,
.dark .section-header h3 {
  color: #f8fafc !important;
}

:global(.dark) .form-control,
.dark .form-control {
  background-color: #1e293b !important;
  border-color: #334155 !important;
  color: #f8fafc !important;
}

:global(.dark) .form-control:hover,
.dark .form-control:hover {
  border-color: #475569 !important;
}

:global(.dark) .form-control:focus,
.dark .form-control:focus {
  border-color: #818cf8 !important;
  box-shadow: 0 0 0 2px rgba(129, 140, 248, 0.25) !important;
}

:global(.dark) .form-control::placeholder,
.dark .form-control::placeholder {
  color: #64748b !important;
}

:global(.dark) .form-control:disabled,
.dark .form-control:disabled {
  background-color: #0f172a !important;
  color: #64748b !important;
  border-color: #334155 !important;
}

:global(.dark) select.form-control option,
.dark select.form-control option {
  background-color: #0f172a !important;
  color: #f8fafc !important;
}

:global(.dark) .info-banner,
.dark .info-banner {
  background: #1e293b !important;
  border-left-color: #3b82f6 !important;
}

:global(.dark) .info-content h4,
.dark .info-content h4 {
  color: #60a5fa !important;
}

:global(.dark) .info-content p,
.dark .info-content p {
  color: #93c5fd !important;
}

:global(.dark) .info-note,
.dark .info-note {
  background: #1e293b !important;
  border-left-color: #f59e0b !important;
}

:global(.dark) .hint,
.dark .hint {
  color: #94a3b8 !important;
}

:global(.dark) .status-badge.inactive,
.dark .status-badge.inactive {
  background: #451a03 !important;
  color: #fbbf24 !important;
  border-color: #b45309 !important;
}

:global(.dark) .status-check,
.dark .status-check {
  border-color: #334155 !important;
  background: #1e293b !important;
  color: #f8fafc !important;
}

:global(.dark) .status-check:hover,
.dark .status-check:hover {
  border-color: #818cf8 !important;
  background: #312e81 !important;
}

:global(.dark) .assignment-row,
.dark .assignment-row {
  background: #1e293b !important;
  border-color: #334155 !important;
}

:global(.dark) .assignment-row:hover,
.dark .assignment-row:hover {
  background: #334155 !important;
  border-color: #818cf8 !important;
}

:global(.dark) .btn-remove,
.dark .btn-remove {
  background: #450a0a !important;
  border-color: #7f1d1d !important;
  color: #f87171 !important;
}

:global(.dark) .modal-footer,
.dark .modal-footer {
  background: #0f172a !important;
  border-top-color: #1e293b !important;
}

:global(.dark) .btn-secondary,
.dark .btn-secondary {
  background: #1e293b !important;
  color: #e2e8f0 !important;
  border-color: #334155 !important;
}

:global(.dark) .btn-secondary:hover,
.dark .btn-secondary:hover {
  background: #334155 !important;
}

:global(.dark) .person-icon,
.dark .person-icon {
  background: #1e3a8a !important;
  color: #93c5fd !important;
}

:global(.dark) .work-icon,
.dark .work-icon {
  background: #14532d !important;
  color: #86efac !important;
}

:global(.dark) .floor-icon,
.dark .floor-icon {
  background: #78350f !important;
  color: #fde047 !important;
}

:global(.dark) .loading-message,
.dark .loading-message {
  background: #1e293b !important;
  color: #94a3b8 !important;
}
</style>
