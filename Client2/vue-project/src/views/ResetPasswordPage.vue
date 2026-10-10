<template>
  <div
    class="min-h-screen bg-gradient-to-br from-purple-50 to-blue-50 dark:from-gray-900 dark:to-gray-800 flex items-center justify-center p-4 font-sans"
  >
    <div class="w-full max-w-md">
      <!-- Success State -->
      <div
        v-if="resetSuccess"
        class="bg-white dark:bg-gray-800 rounded-2xl shadow-2xl p-8 text-center"
      >
        <div class="mb-6">
          <div
            class="mx-auto w-20 h-20 bg-green-100 dark:bg-green-900/30 rounded-full flex items-center justify-center animate-bounce"
          >
            <svg
              class="w-10 h-10 text-green-600 dark:text-green-400"
              fill="none"
              stroke="currentColor"
              viewBox="0 0 24 24"
            >
              <path
                stroke-linecap="round"
                stroke-linejoin="round"
                stroke-width="2"
                d="M5 13l4 4L19 7"
              />
            </svg>
          </div>
        </div>
        <h2 class="text-2xl font-bold text-gray-900 dark:text-white mb-2">
          {{ languageStore.t('password_reset_success_title', 'Password Reset Successfully!') }}
        </h2>
        <p class="text-gray-600 dark:text-gray-400 mb-6">
          {{
            languageStore.t(
              'password_reset_success_desc',
              'Your password has been updated. You can now log in with your new password.',
            )
          }}
        </p>

        <button
          @click="router.push('/login')"
          class="w-full bg-purple-600 hover:bg-purple-700 text-white font-semibold py-3 px-6 rounded-xl transition-colors duration-200 cursor-pointer"
        >
          {{ languageStore.t('continue_to_login', 'Continue to Login') }}
        </button>
      </div>

      <!-- Reset Form -->
      <div v-else class="bg-white dark:bg-gray-800 rounded-2xl shadow-2xl p-8">
        <!-- Header -->
        <div class="text-center mb-8">
          <div
            class="mx-auto w-16 h-16 bg-purple-100 dark:bg-purple-900/30 rounded-full flex items-center justify-center mb-4"
          >
            <svg
              class="w-8 h-8 text-purple-600 dark:text-purple-400"
              fill="none"
              stroke="currentColor"
              viewBox="0 0 24 24"
            >
              <path
                stroke-linecap="round"
                stroke-linejoin="round"
                stroke-width="2"
                d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"
              />
            </svg>
          </div>
          <h1 class="text-2xl font-bold text-gray-900 dark:text-white mb-2">
            {{ languageStore.t('create_new_password', 'Create New Password') }}
          </h1>
          <p class="text-gray-600 dark:text-gray-400 text-sm">
            {{
              languageStore.t('enter_new_password_below', 'Please enter your new password below')
            }}
          </p>
        </div>

        <!-- Error Message -->
        <div
          v-if="error"
          class="mb-6 p-4 bg-red-50 dark:bg-red-900/20 border border-red-200 dark:border-red-800 rounded-xl"
        >
          <div class="flex items-start">
            <svg
              class="w-5 h-5 text-red-600 dark:text-red-400 mt-0.5 mr-3 flex-shrink-0"
              fill="currentColor"
              viewBox="0 0 20 20"
            >
              <path
                fill-rule="evenodd"
                d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.707 7.293a1 1 0 00-1.414 1.414L8.586 10l-1.293 1.293a1 1 0 101.414 1.414L10 11.414l1.293 1.293a1 1 0 001.414-1.414L11.414 10l1.293-1.293a1 1 0 00-1.414-1.414L10 8.586 8.707 7.293z"
                clip-rule="evenodd"
              />
            </svg>
            <div class="flex-1">
              <p class="text-sm text-red-700 dark:text-red-300">
                {{ error }}
              </p>
            </div>
          </div>
        </div>

        <!-- Form -->
        <form @submit.prevent="handleReset" class="space-y-6">
          <!-- Password Field -->
          <div>
            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
              {{ languageStore.t('new_password', 'New Password') }}
            </label>
            <div class="relative">
              <input
                v-model="form.password"
                :type="showPassword ? 'text' : 'password'"
                :placeholder="languageStore.t('new_password', 'Enter new password')"
                class="w-full px-4 py-3 pr-10 border border-gray-300 dark:border-gray-600 rounded-xl focus:ring-2 focus:ring-purple-500 focus:border-transparent dark:bg-gray-700 dark:text-white"
                :class="{ 'border-red-500': errors.password }"
              />
              <button
                type="button"
                @click="showPassword = !showPassword"
                class="absolute right-3 top-1/2 -translate-y-1/2 text-gray-500 hover:text-gray-700 dark:text-gray-400 dark:hover:text-gray-200 cursor-pointer"
              >
                <svg
                  v-if="showPassword"
                  class="w-5 h-5"
                  fill="none"
                  stroke="currentColor"
                  viewBox="0 0 24 24"
                >
                  <path
                    stroke-linecap="round"
                    stroke-linejoin="round"
                    stroke-width="2"
                    d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"
                  />
                  <path
                    stroke-linecap="round"
                    stroke-linejoin="round"
                    stroke-width="2"
                    d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"
                  />
                </svg>
                <svg v-else class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path
                    stroke-linecap="round"
                    stroke-linejoin="round"
                    stroke-width="2"
                    d="M13.875 18.825A10.05 10.05 0 0112 19c-4.478 0-8.268-2.943-9.543-7a9.97 9.97 0 011.563-3.029m5.858.908a3 3 0 114.243 4.243M9.878 9.878l4.242 4.242M9.88 9.88l-3.29-3.29m7.532 7.532l3.29 3.29M3 3l3.59 3.59m0 0A9.953 9.953 0 0112 5c4.478 0 8.268 2.943 9.543 7a10.025 10.025 0 01-4.132 5.411m0 0L21 21"
                  />
                </svg>
              </button>
            </div>
            <p v-if="errors.password" class="mt-1 text-sm text-red-600 dark:text-red-400">
              {{ errors.password }}
            </p>
          </div>

          <!-- Confirm Password Field -->
          <div>
            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
              {{ languageStore.t('confirm_new_password', 'Confirm New Password') }}
            </label>
            <div class="relative">
              <input
                v-model="form.passwordConfirmation"
                :type="showPasswordConfirmation ? 'text' : 'password'"
                :placeholder="languageStore.t('confirm_new_password', 'Confirm new password')"
                class="w-full px-4 py-3 pr-10 border border-gray-300 dark:border-gray-600 rounded-xl focus:ring-2 focus:ring-purple-500 focus:border-transparent dark:bg-gray-700 dark:text-white"
                :class="{ 'border-red-500': errors.passwordConfirmation }"
              />
              <button
                type="button"
                @click="showPasswordConfirmation = !showPasswordConfirmation"
                class="absolute right-3 top-1/2 -translate-y-1/2 text-gray-500 hover:text-gray-700 dark:text-gray-400 dark:hover:text-gray-200 cursor-pointer"
              >
                <svg
                  v-if="showPasswordConfirmation"
                  class="w-5 h-5"
                  fill="none"
                  stroke="currentColor"
                  viewBox="0 0 24 24"
                >
                  <path
                    stroke-linecap="round"
                    stroke-linejoin="round"
                    stroke-width="2"
                    d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"
                  />
                  <path
                    stroke-linecap="round"
                    stroke-linejoin="round"
                    stroke-width="2"
                    d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"
                  />
                </svg>
                <svg v-else class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path
                    stroke-linecap="round"
                    stroke-linejoin="round"
                    stroke-width="2"
                    d="M13.875 18.825A10.05 10.05 0 0112 19c-4.478 0-8.268-2.943-9.543-7a9.97 9.97 0 011.563-3.029m5.858.908a3 3 0 114.243 4.243M9.878 9.878l4.242 4.242M9.88 9.88l-3.29-3.29m7.532 7.532l3.29 3.29M3 3l3.59 3.59m0 0A9.953 9.953 0 0112 5c4.478 0 8.268 2.943 9.543 7a10.025 10.025 0 01-4.132 5.411m0 0L21 21"
                  />
                </svg>
              </button>
            </div>
            <p
              v-if="errors.passwordConfirmation"
              class="mt-1 text-sm text-red-600 dark:text-red-400"
            >
              {{ errors.passwordConfirmation }}
            </p>
          </div>

          <!-- Password Strength Meter -->
          <div v-if="form.password" class="mb-6">
            <div class="flex items-center justify-between mb-2">
              <span class="text-sm font-medium text-gray-700 dark:text-gray-300">
                {{ languageStore.t('password_strength', 'Password Strength:') }}
              </span>
              <span
                class="text-sm font-semibold"
                :class="{
                  'text-red-600': passwordStrength.color === 'red',
                  'text-orange-600': passwordStrength.color === 'orange',
                  'text-yellow-600': passwordStrength.color === 'yellow',
                  'text-green-600': passwordStrength.color === 'green',
                }"
              >
                {{ localizedStrengthLabel }}
              </span>
            </div>
            <div class="w-full h-2 bg-gray-200 dark:bg-gray-700 rounded-full overflow-hidden">
              <div
                class="h-full transition-all duration-300 rounded-full"
                :class="{
                  'bg-red-500': passwordStrength.color === 'red',
                  'bg-orange-500': passwordStrength.color === 'orange',
                  'bg-yellow-500': passwordStrength.color === 'yellow',
                  'bg-green-500': passwordStrength.color === 'green',
                }"
                :style="{ width: `${(passwordStrength.score / 5) * 100}%` }"
              ></div>
            </div>
          </div>

          <!-- Submit Button -->
          <button
            type="submit"
            :disabled="resetting"
            class="w-full bg-gradient-to-r from-purple-600 to-blue-600 hover:from-purple-700 hover:to-blue-700 text-white font-semibold py-3 px-6 rounded-xl transition-all duration-200 shadow-lg hover:shadow-xl disabled:opacity-50 disabled:cursor-not-allowed transform hover:-translate-y-0.5 cursor-pointer"
          >
            <span v-if="resetting" class="flex items-center justify-center">
              <svg
                class="animate-spin -ml-1 mr-3 h-5 w-5 text-white"
                fill="none"
                viewBox="0 0 24 24"
              >
                <circle
                  class="opacity-25"
                  cx="12"
                  cy="12"
                  r="10"
                  stroke="currentColor"
                  stroke-width="4"
                ></circle>
                <path
                  class="opacity-75"
                  fill="currentColor"
                  d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"
                ></path>
              </svg>
              {{ languageStore.t('updating', 'Resetting Password...') }}
            </span>
            <span v-else>{{ languageStore.t('update_password', 'Reset Password') }}</span>
          </button>
        </form>

        <!-- Back to Login -->
        <div class="mt-6 text-center">
          <router-link
            to="/login"
            class="text-sm text-purple-600 dark:text-purple-400 hover:text-purple-700 dark:hover:text-purple-300 font-medium inline-flex items-center"
          >
            <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path
                stroke-linecap="round"
                stroke-linejoin="round"
                stroke-width="2"
                d="M10 19l-7-7m0 0l7-7m-7 7h18"
              />
            </svg>
            {{ languageStore.t('back_to_login', 'Back to Login') }}
          </router-link>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup lang="ts">
import { ref, computed, onMounted } from 'vue'
import { useRoute, useRouter } from 'vue-router'
import { usePasswordResetStore } from '../stores/passwordResetStore'
import { useLanguageStore } from '@/stores/language'

const route = useRoute()
const router = useRouter()
const passwordResetStore = usePasswordResetStore()
const languageStore = useLanguageStore()

const form = ref({
  password: '',
  passwordConfirmation: '',
})

const errors = ref({
  password: '',
  passwordConfirmation: '',
})

const showPassword = ref(false)
const showPasswordConfirmation = ref(false)
const resetSuccess = ref(false)
const error = ref('')
const resetting = ref(false)

const token = ref(route.query.token as string)
const email = ref(route.query.email as string)

const passwordStrength = computed(() => {
  return passwordResetStore.checkPasswordStrength(form.value.password)
})

const localizedStrengthLabel = computed(() => {
  const lbl = passwordStrength.value.label?.toLowerCase() || ''
  if (lbl.includes('weak')) return languageStore.t('weak', 'Weak')
  if (lbl.includes('medium')) return languageStore.t('medium', 'Medium')
  if (lbl.includes('strong')) return languageStore.t('strong', 'Strong')
  return passwordStrength.value.label
})

const validateForm = (): boolean => {
  errors.value = {
    password: '',
    passwordConfirmation: '',
  }

  if (!form.value.password) {
    errors.value.password = 'Password is required'
    return false
  }

  if (form.value.password.length < 8) {
    errors.value.password = 'Password must be at least 8 characters'
    return false
  }

  if (passwordStrength.value.score < 5) {
    errors.value.password = 'Password does not meet all requirements'
    return false
  }

  if (!form.value.passwordConfirmation) {
    errors.value.passwordConfirmation = 'Please confirm your password'
    return false
  }

  if (form.value.password !== form.value.passwordConfirmation) {
    errors.value.passwordConfirmation = 'Passwords do not match'
    return false
  }

  return true
}

const handleReset = async () => {
  if (!validateForm()) return

  if (!email.value || !token.value) {
    error.value = 'Invalid reset link. Please request a new password reset.'
    return
  }

  resetting.value = true
  error.value = ''

  const result = await passwordResetStore.resetPassword(
    email.value,
    token.value,
    form.value.password,
    form.value.passwordConfirmation,
  )

  resetting.value = false

  if (result.success) {
    resetSuccess.value = true
    setTimeout(() => {
      router.push('/login')
    }, 3000)
  } else {
    error.value = result.message

    // If token expired or invalid, redirect to forgot password after delay
    if (result.errorType === 'expired' || result.errorType === 'invalid_token') {
      setTimeout(() => {
        router.push('/forgot-password')
      }, 3000)
    }
  }
}

// Verify token on mount
onMounted(async () => {
  if (!email.value || !token.value) {
    error.value = 'Invalid reset link. Missing required parameters.'
    return
  }
})
</script>
