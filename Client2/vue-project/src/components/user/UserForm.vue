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
}

const props = withDefaults(defineProps<UserFormProps>(), {
  initialData: () => ({}),
  loading: false,
  errors: () => ({}),
})

const emit = defineEmits(['submit'])

const form = reactive({
  first_name: props.initialData?.first_name || '',
  last_name: props.initialData?.last_name || '',
  email: props.initialData?.email || '',
  phone: props.initialData?.phone || '',
  password_hash: '',
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
  <form @submit.prevent="saveUser" class="space-y-4">
    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
      <div>
        <label class="block mb-2 font-medium text-gray-700">
          First Name <span class="text-red-500">*</span>
        </label>
        <input
          v-model="form.first_name"
          type="text"
          required
          :class="[
            'w-full border rounded-lg px-4 py-2 focus:ring-2 focus:ring-blue-500 focus:border-transparent',
            getFieldError('first_name') ? 'border-red-500' : 'border-gray-300',
          ]"
          placeholder="Enter first name"
          :disabled="loading"
        />
        <p v-if="getFieldError('first_name')" class="mt-1 text-sm text-red-600">
          {{ getFieldError('first_name') }}
        </p>
      </div>

      <div>
        <label class="block mb-2 font-medium text-gray-700">
          Last Name <span class="text-red-500">*</span>
        </label>
        <input
          v-model="form.last_name"
          type="text"
          required
          :class="[
            'w-full border rounded-lg px-4 py-2 focus:ring-2 focus:ring-blue-500 focus:border-transparent',
            getFieldError('last_name') ? 'border-red-500' : 'border-gray-300',
          ]"
          placeholder="Enter last name"
          :disabled="loading"
        />
        <p v-if="getFieldError('last_name')" class="mt-1 text-sm text-red-600">
          {{ getFieldError('last_name') }}
        </p>
      </div>
    </div>

    <div>
      <label class="block mb-2 font-medium text-gray-700">
        Email <span class="text-red-500">*</span>
      </label>
      <input
        v-model="form.email"
        type="email"
        required
        :class="[
          'w-full border rounded-lg px-4 py-2 focus:ring-2 focus:ring-blue-500 focus:border-transparent',
          getFieldError('email') ? 'border-red-500' : 'border-gray-300',
        ]"
        placeholder="Enter email address"
        :disabled="loading"
      />
      <p v-if="getFieldError('email')" class="mt-1 text-sm text-red-600">
        {{ getFieldError('email') }}
      </p>
    </div>

    <div>
      <label class="block mb-2 font-medium text-gray-700"> Phone </label>
      <input
        v-model="form.phone"
        type="tel"
        :class="[
          'w-full border rounded-lg px-4 py-2 focus:ring-2 focus:ring-blue-500 focus:border-transparent',
          getFieldError('phone') ? 'border-red-500' : 'border-gray-300',
        ]"
        placeholder="Enter phone number (optional)"
        :disabled="loading"
      />
      <p v-if="getFieldError('phone')" class="mt-1 text-sm text-red-600">
        {{ getFieldError('phone') }}
      </p>
    </div>

    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
      <div>
        <label class="block mb-2 font-medium text-gray-700">
          Password <span v-if="!initialData?.email" class="text-red-500">*</span>
        </label>
        <input
          v-model="form.password"
          type="password"
          :required="!initialData?.email"
          minlength="8"
          :class="[
            'w-full border rounded-lg px-4 py-2 focus:ring-2 focus:ring-blue-500 focus:border-transparent',
            getFieldError('password') ? 'border-red-500' : 'border-gray-300',
          ]"
          :placeholder="
            initialData?.email ? 'Leave blank to keep current password' : 'Minimum 8 characters'
          "
          :disabled="loading"
        />
        <p v-if="getFieldError('password')" class="mt-1 text-sm text-red-600">
          {{ getFieldError('password') }}
        </p>
      </div>

      <div>
        <label class="block mb-2 font-medium text-gray-700">
          Confirm Password <span v-if="!initialData?.email" class="text-red-500">*</span>
        </label>
        <input
          v-model="form.password_confirmation"
          type="password"
          :required="!initialData?.email && form.password !== ''"
          minlength="8"
          :class="[
            'w-full border rounded-lg px-4 py-2 focus:ring-2 focus:ring-blue-500 focus:border-transparent',
            getFieldError('password_confirmation') ? 'border-red-500' : 'border-gray-300',
          ]"
          placeholder="Re-enter password"
          :disabled="loading"
        />
        <p v-if="getFieldError('password_confirmation')" class="mt-1 text-sm text-red-600">
          {{ getFieldError('password_confirmation') }}
        </p>
      </div>
    </div>

    <div>
      <label class="block mb-2 font-medium text-gray-700">
        Role <span class="text-red-500">*</span>
      </label>
      <select
        v-model="form.role"
        required
        :class="[
          'w-full border rounded-lg px-4 py-2 focus:ring-2 focus:ring-blue-500 focus:border-transparent',
          getFieldError('role') ? 'border-red-500' : 'border-gray-300',
        ]"
        :disabled="loading"
      >
        <option value="admin">Admin</option>
        <option value="receptionist">Receptionist</option>
        <option value="cashier">Cashier</option>
        <option value="chef">Chef</option>
        <option value="manager">Manager</option>
      </select>
      <p v-if="getFieldError('role')" class="mt-1 text-sm text-red-600">
        {{ getFieldError('role') }}
      </p>
    </div>

    <div>
      <label class="flex items-center space-x-2 cursor-pointer">
        <input
          v-model="form.is_active"
          type="checkbox"
          class="w-5 h-5 text-blue-600 border-gray-300 rounded focus:ring-2 focus:ring-blue-500"
          :disabled="loading"
        />
        <span class="font-medium text-gray-700">Active User</span>
      </label>
      <p class="text-sm text-gray-500 mt-1 ml-7">Inactive users cannot log in to the system</p>
    </div>

    <button
      type="submit"
      :disabled="loading"
      :class="[
        'px-6 py-2 rounded-lg font-medium transition-colors',
        loading
          ? 'bg-gray-400 cursor-not-allowed text-white'
          : 'bg-blue-600 hover:bg-blue-700 text-white',
      ]"
    >
      <span v-if="loading">Saving...</span>
      <span v-else>Save User</span>
    </button>
  </form>
</template>
