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
            <h2>Register New Waiter</h2>
            <p class="header-subtitle">Create waiter account</p>
          </div>
        </div>
        <button class="btn-close" @click="close" title="Close">
          <X :size="18" />
        </button>
      </div>

      <!-- Modal Body with Two Columns -->
      <div class="modal-body">
        <form @submit.prevent="submitForm" class="form-container">
          <!-- Left Column -->
          <div class="form-column">
            <!-- Personal Information -->
            <div class="form-section">
              <div class="section-header">
                <div class="section-icon person-icon">👤</div>
                <h3>Personal</h3>
              </div>

              <div class="form-row">
                <div class="form-group">
                  <label for="first_name">First Name *</label>
                  <input
                    id="first_name"
                    v-model="newUserData.first_name"
                    type="text"
                    placeholder="John"
                    class="form-control"
                    required
                  />
                  <span v-if="fieldErrors.first_name" class="error">{{ fieldErrors.first_name }}</span>
                </div>
                <div class="form-group">
                  <label for="last_name">Last Name *</label>
                  <input
                    id="last_name"
                    v-model="newUserData.last_name"
                    type="text"
                    placeholder="Smith"
                    class="form-control"
                    required
                  />
                  <span v-if="fieldErrors.last_name" class="error">{{ fieldErrors.last_name }}</span>
                </div>
              </div>

              <div class="form-group">
                <label for="email">Email *</label>
                <input
                  id="email"
                  v-model="newUserData.email"
                  type="email"
                  placeholder="john@example.com"
                  class="form-control"
                  required
                />
                <span v-if="fieldErrors.email" class="error">{{ fieldErrors.email }}</span>
              </div>

              <div class="form-group">
                <label for="phone">Phone *</label>
                <input
                  id="phone"
                  v-model="newUserData.phone"
                  type="tel"
                  placeholder="+1 234567890"
                  class="form-control"
                  required
                />
                <span v-if="fieldErrors.phone" class="error">{{ fieldErrors.phone }}</span>
              </div>
            </div>

            <!-- Credentials -->
            <div class="form-section">
              <div class="section-header">
                <div class="section-icon lock-icon">🔐</div>
                <h3>Credentials</h3>
              </div>

              <div class="form-group">
                <label for="password">Password *</label>
                <input
                  id="password"
                  v-model="newUserData.password"
                  type="password"
                  placeholder="Min 8 chars"
                  class="form-control"
                  required
                  minlength="8"
                />
                <span v-if="fieldErrors.password" class="error">{{ fieldErrors.password }}</span>
                <small class="hint">⚠ Minimum 8 characters</small>
              </div>
            </div>
          </div>

          <!-- Right Column -->
          <div class="form-column">
            <!-- Assignment -->
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
                  placeholder="Section A"
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
                <label for="experience_level">Experience *</label>
                <select id="experience_level" v-model="formData.experience_level" class="form-control" required>
                  <option value="">Select...</option>
                  <option value="junior">📚 Junior</option>
                  <option value="senior">⭐ Senior</option>
                  <option value="head">👑 Head</option>
                </select>
                <span v-if="fieldErrors.experience_level" class="error">{{ fieldErrors.experience_level }}</span>
              </div>

              <div class="form-group">
                <label>Status *</label>
                <div class="status-group">
                  <label class="status-check">
                    <input v-model="formData.status" type="radio" value="active" />
                    <span>✓ Active</span>
                  </label>
                  <label class="status-check">
                    <input v-model="formData.status" type="radio" value="on_break" />
                    <span>⏸ Break</span>
                  </label>
                  <label class="status-check">
                    <input v-model="formData.status" type="radio" value="inactive" />
                    <span>✗ Inactive</span>
                  </label>
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
          {{ submitting ? 'Registering...' : 'Register' }}
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
  status: 'active',
})

const newUserData = ref({
  first_name: '',
  last_name: '',
  email: '',
  phone: '',
  password: '',
})

watch(() => props.isOpen, (newVal) => {
  if (newVal) resetForm()
})

const resetForm = () => {
  formData.value = { section: '', shift: '', experience_level: '', status: 'active' }
  newUserData.value = { first_name: '', last_name: '', email: '', phone: '', password: '' }
  errorMessage.value = ''
  fieldErrors.value = {}
}

const validateForm = (): boolean => {
  fieldErrors.value = {}
  let isValid = true

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
  if (!newUserData.value.password || newUserData.value.password.length < 8) {
    fieldErrors.value.password = 'Min 8 chars'
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
