<script setup lang="ts">
import { reactive, watch, ref, onMounted } from 'vue'
import { rbacService } from '../../services/rbacService'
import { useLanguageStore } from '@/stores/language'
import type { Role } from '../../types/rbacTypes'

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

const emit = defineEmits<{
  (e: 'submit', payload: typeof form): void
}>()

const languageStore = useLanguageStore()
const availableRoles = ref<Role[]>([])

const DEFAULT_ROLES = ['admin', 'receptionist', 'cashier', 'chef', 'manager', 'waiter']

onMounted(async () => {
  try {
    const data = await rbacService.getRoles()
    if (Array.isArray(data) && data.length > 0) {
      availableRoles.value = data
    }
  } catch (error) {
    console.error('[UserForm] Failed to load roles:', error)
  }
})

const form = reactive({
  first_name: '',
  last_name: '',
  email: '',
  phone: '',
  role: 'receptionist',
  is_active: true,
})

const populateForm = (data?: Record<string, any>) => {
  if (!data) return
  form.first_name = data.first_name || ''
  form.last_name = data.last_name || ''
  form.email = data.email || ''
  form.phone = data.phone || ''
  form.role = data.role || 'receptionist'
  form.is_active = data.is_active ?? true
}

watch(
  () => props.initialData,
  (newData) => populateForm(newData),
  { immediate: true },
)

const saveUser = () => {
  emit('submit', { ...form })
}

const getFieldError = (fieldName: string): string | null => {
  return props.errors[fieldName]?.[0] || null
}

const getInputClass = (fieldName: string) => [
  'w-full border rounded-lg px-3 sm:px-3.5 py-2 sm:py-2.5 text-xs sm:text-sm focus:ring-2 focus:ring-blue-500 focus:border-transparent transition duration-200',
  getFieldError(fieldName) ? 'border-red-500 ring-2 ring-red-200' : 'border-slate-300',
]
</script>

<template>
  <form @submit.prevent="saveUser" class="space-y-4 sm:space-y-5 md:space-y-6">
    <div class="grid grid-cols-1 sm:grid-cols-2 gap-3 sm:gap-4 md:gap-5">
      <div>
        <label class="block mb-1.5 sm:mb-2 text-xs sm:text-sm font-semibold text-slate-900">
          {{ languageStore.t('first_name', 'First Name') }} <span class="text-red-500">*</span>
        </label>
        <input
          v-model="form.first_name"
          type="text"
          required
          :placeholder="languageStore.t('first_name', 'Enter first name')"
          :class="getInputClass('first_name')"
          :disabled="loading"
        />
        <p
          v-if="getFieldError('first_name')"
          class="mt-1 text-xs text-red-600 flex items-center gap-1"
        >
          {{ getFieldError('first_name') }}
        </p>
      </div>

      <div>
        <label class="block mb-1.5 sm:mb-2 text-xs sm:text-sm font-semibold text-slate-900">
          {{ languageStore.t('last_name', 'Last Name') }} <span class="text-red-500">*</span>
        </label>
        <input
          v-model="form.last_name"
          type="text"
          required
          :placeholder="languageStore.t('last_name', 'Enter last name')"
          :class="getInputClass('last_name')"
          :disabled="loading"
        />
        <p
          v-if="getFieldError('last_name')"
          class="mt-1 text-xs text-red-600 flex items-center gap-1"
        >
          {{ getFieldError('last_name') }}
        </p>
      </div>
    </div>

    <div>
      <label class="block mb-1.5 sm:mb-2 text-xs sm:text-sm font-semibold text-slate-900">
        {{ languageStore.t('email', 'Email') }} <span class="text-red-500">*</span>
      </label>
      <input
        v-model="form.email"
        type="email"
        required
        :placeholder="languageStore.t('email', 'Enter email address')"
        :class="getInputClass('email')"
        :disabled="loading"
      />
      <p v-if="getFieldError('email')" class="mt-1 text-xs text-red-600 flex items-center gap-1">
        {{ getFieldError('email') }}
      </p>
    </div>

    <div>
      <label class="block mb-1.5 sm:mb-2 text-xs sm:text-sm font-semibold text-slate-900">
        {{ languageStore.t('phone', 'Phone') }}
        <span class="text-slate-400 text-xs">({{ languageStore.t('optional', 'Optional') }})</span>
      </label>
      <input
        v-model="form.phone"
        type="tel"
        placeholder="+251 XXX XXX XXX"
        :class="getInputClass('phone')"
        :disabled="loading"
      />
      <p v-if="getFieldError('phone')" class="mt-1 text-xs text-red-600 flex items-center gap-1">
        {{ getFieldError('phone') }}
      </p>
    </div>

    <div>
      <label class="block mb-1.5 sm:mb-2 text-xs sm:text-sm font-semibold text-slate-900">
        {{ languageStore.t('role', 'Role') }} <span class="text-red-500">*</span>
      </label>
      <select
        v-model="form.role"
        required
        :class="[getInputClass('role'), 'bg-white cursor-pointer']"
        :disabled="loading"
      >
        <option value="">-- {{ languageStore.t('select_role', 'Select Role') }} --</option>
        <template v-if="availableRoles.length > 0">
          <option v-for="r in availableRoles" :key="r.id" :value="r.slug || r.name.toLowerCase()">
            {{ r.name }}
          </option>
        </template>
        <template v-else>
          <option v-for="r in DEFAULT_ROLES" :key="r" :value="r" class="capitalize">
            {{ r.charAt(0).toUpperCase() + r.slice(1) }}
          </option>
        </template>
      </select>
      <p v-if="getFieldError('role')" class="mt-1 text-xs text-red-600 flex items-center gap-1">
        {{ getFieldError('role') }}
      </p>
    </div>

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
          {{ languageStore.t('active_user', 'Active User') }}
        </label>
        <p class="text-xs text-slate-500 mt-0.5">
          {{ languageStore.t('inactive_user_hint', 'Inactive users cannot log in to the system') }}
        </p>
      </div>
    </div>

    <button
      type="submit"
      :disabled="loading"
      :class="[
        'w-full sm:w-auto px-4 sm:px-6 py-2 sm:py-2.5 rounded-lg font-medium transition-colors duration-200 text-xs sm:text-sm cursor-pointer',
        loading
          ? 'bg-slate-300 cursor-not-allowed text-slate-600'
          : 'bg-blue-600 hover:bg-blue-700 text-white',
      ]"
    >
      <span v-if="loading" class="inline-flex items-center gap-2">
        <span class="animate-spin">⌛</span> {{ languageStore.t('saving', 'Saving...') }}
      </span>
      <span v-else>💾 {{ languageStore.t('save_user', 'Save User') }}</span>
    </button>
  </form>
</template>
