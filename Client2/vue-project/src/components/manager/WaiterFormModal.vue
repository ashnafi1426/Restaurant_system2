<template>
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
</template>

<script setup lang="ts">
import { ref, watch } from 'vue'
import { X, UserPlus, AlertCircle, Loader } from 'lucide-vue-next'

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

const formData = ref({
  section: '',
  shift: '',
  experience_level: '',
  status: 'inactive', // Default to inactive for new waiters
  maximum_orders: 5,
  employee_number: '',
})

const newUserData = ref({
  first_name: '',
  last_name: '',
  email: '',
  phone: '',
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

const resetForm = () => {
  formData.value = { section: '', shift: '', experience_level: '', status: 'inactive', maximum_orders: 5, employee_number: '' }
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

.modal-overlay {
  position: fixed;
  top: 0;
  left: 0;
  right: 0;
  bottom: 0;
  background: rgba(0, 0, 0, 0.6);
  display: flex;
  align-items: center;
  justify-content: center;
  z-index: 1000;
  padding: 12px;
  backdrop-filter: blur(4px);
}

.modal-container {
  background: white;
  border-radius: 12px;
  max-width: 900px;
  width: 100%;
  max-height: 80vh;
  display: flex;
  flex-direction: column;
  box-shadow: 0 20px 40px rgba(0, 0, 0, 0.15);
  animation: slideUp 0.3s ease-out;
}

@keyframes slideUp {
  from { opacity: 0; transform: translateY(20px); }
  to { opacity: 1; transform: translateY(0); }
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
  color: #ff9800;
  display: block;
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
</style>
