<script setup lang="ts">
import { reactive, watch } from 'vue'

interface UserFormProps {
  initialData?: {
    first_name?: string
    last_name?: string
    email?: string
    phone?: string
    role?: string
    is_active?: boolean
  }
  loading?: boolean
  errors?: Record<string, string[]>
  isEditMode?: boolean
}

const props = withDefaults(defineProps<UserFormProps>(), {
  initialData: () => ({}),
  loading: false,
  errors: () => ({}),
  isEditMode: false,
})

const emit = defineEmits(['submit'])

const form = reactive({
  first_name: props.initialData?.first_name || '',
  last_name: props.initialData?.last_name || '',
  email: props.initialData?.email || '',
  phone: props.initialData?.phone || '',
  password: '',
  password_confirmation: '',
  role: props.initialData?.role || 'receptionist',
  is_active: props.initialData?.is_active ?? true,
})
// Watch for changes in initial data (for edit mode)
watch(
  () => props.initialData,
  (newData) => {
    if (newData) {
      form.first_name = newData.first_name || ''
      form.last_name = newData.last_name || ''
      form.email = newData.email || ''
      form.phone = newData.phone || ''
      form.role = newData.role || 'receptionist'
      form.is_active = newData.is_active ?? true
      // Clear password fields on edit mode data load
      form.password = ''
      form.password_confirmation = ''
    }
  },
  { deep: true },
)

const saveUser = () => {
  emit('submit', form)
}

const getFieldError = (fieldName: string): string | null => {
  return props.errors[fieldName]?.[0] || null
}
</script>

<template>
  <form @submit.prevent="saveUser" class="space-y-4 sm:space-y-5 md:space-y-6">
    <!-- First and Last Name -->
    <div class="grid grid-cols-1 sm:grid-cols-2 gap-3 sm:gap-4 md:gap-5">
      <div>
        <label class="block mb-1.5 sm:mb-2 text-xs sm:text-sm font-semibold text-slate-900">
          First Name <span class="text-red-500">*</span>
        </label>
        <input
          v-model="form.first_name"
          type="text"
          required
          placeholder="Enter first name"
          :class="[
            'w-full border rounded-lg px-3 sm:px-3.5 py-2 sm:py-2.5 text-xs sm:text-sm focus:ring-2 focus:ring-blue-500 focus:border-transparent transition duration-200',
            getFieldError('first_name') ? 'border-red-500 ring-2 ring-red-200' : 'border-slate-300',
          ]"
          :disabled="loading"
        />
        <p
          v-if="getFieldError('first_name')"
          class="mt-1 text-xs text-red-600 flex items-center gap-1"
        >
          <span>❌</span> {{ getFieldError('first_name') }}
        </p>
      </div>

      <div>
        <label class="block mb-1.5 sm:mb-2 text-xs sm:text-sm font-semibold text-slate-900">
          Last Name <span class="text-red-500">*</span>
        </label>
        <input
          v-model="form.last_name"
          type="text"
          required
          placeholder="Enter last name"
          :class="[
            'w-full border rounded-lg px-3 sm:px-3.5 py-2 sm:py-2.5 text-xs sm:text-sm focus:ring-2 focus:ring-blue-500 focus:border-transparent transition duration-200',
            getFieldError('last_name') ? 'border-red-500 ring-2 ring-red-200' : 'border-slate-300',
          ]"
          :disabled="loading"
        />
        <p
          v-if="getFieldError('last_name')"
          class="mt-1 text-xs text-red-600 flex items-center gap-1"
        >
          <span>❌</span> {{ getFieldError('last_name') }}
        </p>
      </div>
    </div>

    <!-- Email -->
    <div>
      <label class="block mb-1.5 sm:mb-2 text-xs sm:text-sm font-semibold text-slate-900">
        Email <span class="text-red-500">*</span>
      </label>
      <input
        v-model="form.email"
        type="email"
        required
        placeholder="Enter email address"
        :class="[
          'w-full border rounded-lg px-3 sm:px-3.5 py-2 sm:py-2.5 text-xs sm:text-sm focus:ring-2 focus:ring-blue-500 focus:border-transparent transition duration-200',
          getFieldError('email') ? 'border-red-500 ring-2 ring-red-200' : 'border-slate-300',
        ]"
        :disabled="loading"
      />
      <p v-if="getFieldError('email')" class="mt-1 text-xs text-red-600 flex items-center gap-1">
        <span>❌</span> {{ getFieldError('email') }}
      </p>
    </div>

    <!-- Phone -->
    <div>
      <label class="block mb-1.5 sm:mb-2 text-xs sm:text-sm font-semibold text-slate-900">
        Phone <span class="text-slate-400 text-xs">(Optional)</span>
      </label>
      <input
        v-model="form.phone"
        type="tel"
        placeholder="Enter phone number"
        :class="[
          'w-full border rounded-lg px-3 sm:px-3.5 py-2 sm:py-2.5 text-xs sm:text-sm focus:ring-2 focus:ring-blue-500 focus:border-transparent transition duration-200',
          getFieldError('phone') ? 'border-red-500 ring-2 ring-red-200' : 'border-slate-300',
        ]"
        :disabled="loading"
      />
      <p v-if="getFieldError('phone')" class="mt-1 text-xs text-red-600 flex items-center gap-1">
        <span>❌</span> {{ getFieldError('phone') }}
      </p>
    </div>

    <!-- Password Fields -->
    <div class="grid grid-cols-1 sm:grid-cols-2 gap-3 sm:gap-4 md:gap-5">
      <div>
        <label class="block mb-1.5 sm:mb-2 text-xs sm:text-sm font-semibold text-slate-900">
          Password <span v-if="!isEditMode" class="text-red-500">*</span>
        </label>
        <input
          v-model="form.password"
          type="password"
          :required="!isEditMode"
          minlength="8"
          :placeholder="isEditMode ? 'Leave blank to keep current' : 'Minimum 8 characters'"
          :class="[
            'w-full border rounded-lg px-3 sm:px-3.5 py-2 sm:py-2.5 text-xs sm:text-sm focus:ring-2 focus:ring-blue-500 focus:border-transparent transition duration-200',
            getFieldError('password')
              ? 'border-red-500 ring-2 ring-red-200'
              : 'border-slate-300',
          ]"
          :disabled="loading"
        />
        <p
          v-if="getFieldError('password')"
          class="mt-1 text-xs text-red-600 flex items-center gap-1"
        >
          <span>❌</span> {{ getFieldError('password') }}
        </p>
      </div>

      <div>
        <label class="block mb-1.5 sm:mb-2 text-xs sm:text-sm font-semibold text-slate-900">
          Confirm Password <span v-if="!isEditMode" class="text-red-500">*</span>
        </label>
        <input
          v-model="form.password_confirmation"
          type="password"
          :required="!isEditMode && form.password !== ''"
          minlength="8"
          placeholder="Re-enter password"
          :class="[
            'w-full border rounded-lg px-3 sm:px-3.5 py-2 sm:py-2.5 text-xs sm:text-sm focus:ring-2 focus:ring-blue-500 focus:border-transparent transition duration-200',
            getFieldError('password_confirmation')
              ? 'border-red-500 ring-2 ring-red-200'
              : 'border-slate-300',
          ]"
          :disabled="loading"
        />
        <p
          v-if="getFieldError('password_confirmation')"
          class="mt-1 text-xs text-red-600 flex items-center gap-1"
        >
          <span>❌</span> {{ getFieldError('password_confirmation') }}
        </p>
      </div>
    </div>

    <!-- Role -->
    <div>
      <label class="block mb-1.5 sm:mb-2 text-xs sm:text-sm font-semibold text-slate-900">
        Role <span class="text-red-500">*</span>
      </label>
      <select
        v-model="form.role"
        required
        :class="[
          'w-full border rounded-lg px-3 sm:px-3.5 py-2 sm:py-2.5 text-xs sm:text-sm focus:ring-2 focus:ring-blue-500 focus:border-transparent transition duration-200 bg-white',
          getFieldError('role') ? 'border-red-500 ring-2 ring-red-200' : 'border-slate-300',
        ]"
        :disabled="loading"
      >
        <option value="">-- Select Role --</option>
        <option value="admin">👑 Admin</option>
        <option value="receptionist">🏨 Receptionist</option>
        <option value="cashier">💳 Cashier</option>
        <option value="chef">👨‍🍳 Chef</option>
        <option value="manager">📊 Manager</option>
      </select>
      <p v-if="getFieldError('role')" class="mt-1 text-xs text-red-600 flex items-center gap-1">
        <span>❌</span> {{ getFieldError('role') }}
      </p>
    </div>

    <!-- Active Status Checkbox -->
    <div
      class="flex items-start gap-2 sm:gap-3 p-3 sm:p-4 bg-slate-50 border border-slate-200 rounded-lg"
    >
      <input
        v-model="form.is_active"
        type="checkbox"
        id="active-checkbox"
        class="w-4 h-4 sm:w-5 sm:h-5 mt-0.5 sm:mt-0 accent-blue-600 cursor-pointer"
        :disabled="loading"
      />
      <div class="flex-1 min-w-0">
        <label
          for="active-checkbox"
          class="text-xs sm:text-sm font-semibold text-slate-900 cursor-pointer"
        >
          Active User
        </label>
        <p class="text-xs text-slate-500 mt-0.5">Inactive users cannot log in to the system</p>
      </div>
    </div>

    <!-- Submit Button -->
    <button
      type="submit"
      :disabled="loading"
      :class="[
        'w-full sm:w-auto px-4 sm:px-6 py-2 sm:py-2.5 rounded-lg font-medium transition-colors duration-200 text-xs sm:text-sm',
        loading
          ? 'bg-slate-300 cursor-not-allowed text-slate-600'
          : 'bg-blue-600 hover:bg-blue-700 text-white',
      ]"
    >
      <span v-if="loading" class="inline-flex items-center gap-2">
        <span class="animate-spin">⌛</span> Saving...
      </span>
      <span v-else>💾 Save User</span>
    </button>
  </form>
</template>
