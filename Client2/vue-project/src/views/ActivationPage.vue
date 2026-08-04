<template>
  <div class="min-h-screen bg-gradient-to-br from-purple-50 to-blue-50 dark:from-gray-900 dark:to-gray-800 flex items-center justify-center p-4">
    <div class="w-full max-w-md">
      <!-- Loading State -->
      <div v-if="validatingToken" class="bg-white dark:bg-gray-800 rounded-2xl shadow-2xl p-8 text-center">
        <div class="animate-spin rounded-full h-16 w-16 border-t-4 border-b-4 border-purple-600 mx-auto mb-4"></div>
        <h2 class="text-xl font-semibold text-gray-800 dark:text-white mb-2">
          Validating Activation Link
        </h2>
        <p class="text-gray-600 dark:text-gray-400">
          Please wait while we verify your activation token...
        </p>
      </div>

      <!-- Error States -->
      <div v-else-if="errorType" class="bg-white dark:bg-gray-800 rounded-2xl shadow-2xl p-8">
        <!-- Invalid Token -->
        <div v-if="errorType === 'invalid_token'" class="text-center">
          <div class="mb-6">
            <div class="mx-auto w-16 h-16 bg-red-100 dark:bg-red-900/30 rounded-full flex items-center justify-center">
              <svg class="w-8 h-8 text-red-600 dark:text-red-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
              </svg>
            </div>
          </div>
          
          <h2 class="text-2xl font-bold text-gray-900 dark:text-white mb-2">
            Invalid Activation Link
          </h2>
          <p class="text-gray-600 dark:text-gray-400 mb-6">
            This activation link is invalid or has already been used.
          </p>
          
          <button
            @click="router.push('/login')"
            class="w-full bg-purple-600 hover:bg-purple-700 text-white font-semibold py-3 px-6 rounded-xl transition-colors duration-200"
          >
            Go to Login
          </button>
        </div>

        <!-- Expired Token -->
        <div v-else-if="errorType === 'expired'" class="text-center">
          <div class="mb-6">
            <div class="mx-auto w-16 h-16 bg-orange-100 dark:bg-orange-900/30 rounded-full flex items-center justify-center">
              <svg class="w-8 h-8 text-orange-600 dark:text-orange-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
              </svg>
            </div>
          </div>
          
          <h2 class="text-2xl font-bold text-gray-900 dark:text-white mb-2">
            Activation Link Expired
          </h2>
          <p class="text-gray-600 dark:text-gray-400 mb-6">
            This activation link has expired. Request a new one to activate your account.
          </p>
          
          <div v-if="!showResendForm">
            <button
              @click="showResendForm = true"
              class="w-full bg-purple-600 hover:bg-purple-700 text-white font-semibold py-3 px-6 rounded-xl transition-colors duration-200"
            >
              Request New Link
            </button>
          </div>
          
          <!-- Resend Form -->
          <div v-else>
            <input
              v-model="resendEmail"
              type="email"
              placeholder="Enter your email"
              class="w-full px-4 py-3 border border-gray-300 dark:border-gray-600 rounded-xl focus:ring-2 focus:ring-purple-500 focus:border-transparent dark:bg-gray-700 dark:text-white mb-4"
            />
            <button
              @click="handleResendActivation"
              :disabled="resending || !resendEmail"
              class="w-full bg-purple-600 hover:bg-purple-700 text-white font-semibold py-3 px-6 rounded-xl transition-colors duration-200 disabled:opacity-50 disabled:cursor-not-allowed mb-3"
            >
              <span v-if="resending">Sending...</span>
              <span v-else>Send New Link</span>
            </button>
            <button
              @click="router.push('/login')"
              class="w-full bg-gray-200 hover:bg-gray-300 dark:bg-gray-700 dark:hover:bg-gray-600 text-gray-800 dark:text-white font-semibold py-3 px-6 rounded-xl transition-colors duration-200"
            >
              Back to Login
            </button>
          </div>
        </div>

        <!-- Already Activated -->
        <div v-else-if="errorType === 'already_activated'" class="text-center">
          <div class="mb-6">
            <div class="mx-auto w-16 h-16 bg-green-100 dark:bg-green-900/30 rounded-full flex items-center justify-center">
              <svg class="w-8 h-8 text-green-600 dark:text-green-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
              </svg>
            </div>
          </div>
          
          <h2 class="text-2xl font-bold text-gray-900 dark:text-white mb-2">
            Account Already Activated
          </h2>
          <p class="text-gray-600 dark:text-gray-400 mb-6">
            Your account is already activated. You can log in now.
          </p>
          
          <button
            @click="router.push('/login')"
            class="w-full bg-purple-600 hover:bg-purple-700 text-white font-semibold py-3 px-6 rounded-xl transition-colors duration-200"
          >
            Go to Login
          </button>
        </div>
      </div>

      <!-- Activation Form -->
      <div v-else-if="user" class="bg-white dark:bg-gray-800 rounded-2xl shadow-2xl p-8">
        <!-- Success State -->
        <div v-if="activationSuccess" class="text-center">
          <div class="mb-6">
            <div class="mx-auto w-20 h-20 bg-green-100 dark:bg-green-900/30 rounded-full flex items-center justify-center animate-bounce">
              <svg class="w-10 h-10 text-green-600 dark:text-green-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
              </svg>
            </div>
          </div>
          
          <h2 class="text-2xl font-bold text-gray-900 dark:text-white mb-2">
            Account Activated Successfully!
          </h2>
          <p class="text-gray-600 dark:text-gray-400 mb-6">
            Your account has been activated. You can now log in with your new password.
          </p>
          
          <button
            @click="router.push('/login')"
            class="w-full bg-purple-600 hover:bg-purple-700 text-white font-semibold py-3 px-6 rounded-xl transition-colors duration-200"
          >
            Continue to Login
          </button>
        </div>

        <!-- Password Creation Form -->
        <form v-else @submit.prevent="handleActivation">
          <div class="mb-6 text-center">
            <div class="mx-auto w-16 h-16 bg-purple-100 dark:bg-purple-900/30 rounded-full flex items-center justify-center mb-4">
              <svg class="w-8 h-8 text-purple-600 dark:text-purple-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z" />
              </svg>
            </div>
            <h2 class="text-2xl font-bold text-gray-900 dark:text-white mb-2">
              Create Your Password
            </h2>
            <p class="text-gray-600 dark:text-gray-400">
              Hello <strong>{{ user.first_name }} {{ user.last_name }}</strong>
            </p>
          </div>

          <!-- User Info Card -->
          <div class="bg-purple-50 dark:bg-purple-900/20 border border-purple-200 dark:border-purple-800 rounded-xl p-4 mb-6">
            <div class="flex items-center justify-between mb-2">
              <span class="text-sm font-medium text-gray-700 dark:text-gray-300">Email:</span>
              <span class="text-sm text-gray-900 dark:text-white font-semibold">{{ user.email }}</span>
            </div>
            <div class="flex items-center justify-between">
              <span class="text-sm font-medium text-gray-700 dark:text-gray-300">Role:</span>
              <span class="inline-block px-3 py-1 bg-purple-600 text-white text-xs font-semibold rounded-full">
                {{ user.role }}
              </span>
            </div>
          </div>

          <!-- Password Field -->
          <div class="mb-4">
            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
              Password
            </label>
            <div class="relative">
              <input
                v-model="form.password"
                :type="showPassword ? 'text' : 'password'"
                placeholder="Enter your password"
                class="w-full px-4 py-3 pr-10 border border-gray-300 dark:border-gray-600 rounded-xl focus:ring-2 focus:ring-purple-500 focus:border-transparent dark:bg-gray-700 dark:text-white"
                :class="{ 'border-red-500': errors.password }"
              />
              <button
                type="button"
                @click="showPassword = !showPassword"
                class="absolute right-3 top-1/2 -translate-y-1/2 text-gray-500 hover:text-gray-700 dark:text-gray-400 dark:hover:text-gray-200"
              >
                <svg v-if="showPassword" class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" />
                </svg>
                <svg v-else class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13.875 18.825A10.05 10.05 0 0112 19c-4.478 0-8.268-2.943-9.543-7a9.97 9.97 0 011.563-3.029m5.858.908a3 3 0 114.243 4.243M9.878 9.878l4.242 4.242M9.88 9.88l-3.29-3.29m7.532 7.532l3.29 3.29M3 3l3.59 3.59m0 0A9.953 9.953 0 0112 5c4.478 0 8.268 2.943 9.543 7a10.025 10.025 0 01-4.132 5.411m0 0L21 21" />
                </svg>
              </button>
            </div>
            <p v-if="errors.password" class="mt-1 text-sm text-red-600 dark:text-red-400">
              {{ errors.password }}
            </p>
          </div>

          <!-- Confirm Password Field -->
          <div class="mb-4">
            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
              Confirm Password
            </label>
            <div class="relative">
              <input
                v-model="form.passwordConfirmation"
                :type="showPasswordConfirmation ? 'text' : 'password'"
                placeholder="Confirm your password"
                class="w-full px-4 py-3 pr-10 border border-gray-300 dark:border-gray-600 rounded-xl focus:ring-2 focus:ring-purple-500 focus:border-transparent dark:bg-gray-700 dark:text-white"
                :class="{ 'border-red-500': errors.passwordConfirmation }"
              />
              <button
                type="button"
                @click="showPasswordConfirmation = !showPasswordConfirmation"
                class="absolute right-3 top-1/2 -translate-y-1/2 text-gray-500 hover:text-gray-700 dark:text-gray-400 dark:hover:text-gray-200"
              >
                <svg v-if="showPasswordConfirmation" class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" />
                </svg>
                <svg v-else class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13.875 18.825A10.05 10.05 0 0112 19c-4.478 0-8.268-2.943-9.543-7a9.97 9.97 0 011.563-3.029m5.858.908a3 3 0 114.243 4.243M9.878 9.878l4.242 4.242M9.88 9.88l-3.29-3.29m7.532 7.532l3.29 3.29M3 3l3.59 3.59m0 0A9.953 9.953 0 0112 5c4.478 0 8.268 2.943 9.543 7a10.025 10.025 0 01-4.132 5.411m0 0L21 21" />
                </svg>
              </button>
            </div>
            <p v-if="errors.passwordConfirmation" class="mt-1 text-sm text-red-600 dark:text-red-400">
              {{ errors.passwordConfirmation }}
            </p>
          </div>

          <!-- Password Strength Meter -->
          <div v-if="form.password" class="mb-6">
            <div class="flex items-center justify-between mb-2">
              <span class="text-sm font-medium text-gray-700 dark:text-gray-300">
                Password Strength:
              </span>
              <span
                class="text-sm font-semibold"
                :class="{
                  'text-red-600': passwordStrength.color === 'red',
                  'text-orange-600': passwordStrength.color === 'orange',
                  'text-yellow-600': passwordStrength.color === 'yellow',
                  'text-green-600': passwordStrength.color === 'green'
                }"
              >
                {{ passwordStrength.label }}
              </span>
            </div>
            <div class="w-full h-2 bg-gray-200 dark:bg-gray-700 rounded-full overflow-hidden">
              <div
                class="h-full transition-all duration-300 rounded-full"
                :class="{
                  'bg-red-500': passwordStrength.color === 'red',
                  'bg-orange-500': passwordStrength.color === 'orange',
                  'bg-yellow-500': passwordStrength.color === 'yellow',
                  'bg-green-500': passwordStrength.color === 'green'
                }"
                :style="{ width: `${(passwordStrength.score / 5) * 100}%` }"
              ></div>
            </div>
            <ul v-if="passwordStrength.feedback.length > 0" class="mt-2 space-y-1">
              <li
                v-for="(feedback, index) in passwordStrength.feedback"
                :key="index"
                class="text-xs text-gray-600 dark:text-gray-400 flex items-center"
              >
                <svg class="w-3 h-3 mr-1" fill="currentColor" viewBox="0 0 20 20">
                  <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.707 7.293a1 1 0 00-1.414 1.414L8.586 10l-1.293 1.293a1 1 0 101.414 1.414L10 11.414l1.293 1.293a1 1 0 001.414-1.414L11.414 10l1.293-1.293a1 1 0 00-1.414-1.414L10 8.586 8.707 7.293z" clip-rule="evenodd" />
                </svg>
                {{ feedback }}
              </li>
            </ul>
          </div>

          <!-- Requirements List -->
          <div class="bg-blue-50 dark:bg-blue-900/20 border border-blue-200 dark:border-blue-800 rounded-xl p-4 mb-6">
            <h3 class="text-sm font-semibold text-blue-900 dark:text-blue-200 mb-2">
              Password Requirements:
            </h3>
            <ul class="space-y-1 text-xs text-blue-800 dark:text-blue-300">
              <li class="flex items-center">
                <svg class="w-3 h-3 mr-2" fill="currentColor" viewBox="0 0 20 20">
                  <path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd" />
                </svg>
                Minimum 8 characters
              </li>
              <li class="flex items-center">
                <svg class="w-3 h-3 mr-2" fill="currentColor" viewBox="0 0 20 20">
                  <path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd" />
                </svg>
                At least one uppercase letter
              </li>
              <li class="flex items-center">
                <svg class="w-3 h-3 mr-2" fill="currentColor" viewBox="0 0 20 20">
                  <path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd" />
                </svg>
                At least one lowercase letter
              </li>
              <li class="flex items-center">
                <svg class="w-3 h-3 mr-2" fill="currentColor" viewBox="0 0 20 20">
                  <path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd" />
                </svg>
                At least one number
              </li>
              <li class="flex items-center">
                <svg class="w-3 h-3 mr-2" fill="currentColor" viewBox="0 0 20 20">
                  <path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd" />
                </svg>
                At least one special character (!@#$%^&*)
              </li>
            </ul>
          </div>

          <!-- Error Message -->
          <div v-if="activationError" class="mb-4 bg-red-50 dark:bg-red-900/20 border border-red-200 dark:border-red-800 text-red-700 dark:text-red-300 px-4 py-3 rounded-xl">
            {{ activationError }}
          </div>

          <!-- Submit Button -->
          <button
            type="submit"
            :disabled="activating"
            class="w-full bg-gradient-to-r from-purple-600 to-blue-600 hover:from-purple-700 hover:to-blue-700 text-white font-semibold py-3 px-6 rounded-xl transition-all duration-200 shadow-lg hover:shadow-xl disabled:opacity-50 disabled:cursor-not-allowed transform hover:-translate-y-0.5"
          >
            <span v-if="activating" class="flex items-center justify-center">
              <svg class="animate-spin -ml-1 mr-3 h-5 w-5 text-white" fill="none" viewBox="0 0 24 24">
                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
              </svg>
              Activating...
            </span>
            <span v-else>Activate Account</span>
          </button>
        </form>
      </div>
    </div>
  </div>
</template>

<script setup lang="ts">
import { ref, computed, onMounted } from 'vue'
import { useRoute, useRouter } from 'vue-router'
import { useActivationStore } from '../stores/activationStore'

const route = useRoute()
const router = useRouter()
const activationStore = useActivationStore()

// State
const form = ref({
  password: '',
  passwordConfirmation: ''
})

const errors = ref({
  password: '',
  passwordConfirmation: ''
})

const showPassword = ref(false)
const showPasswordConfirmation = ref(false)
const activationSuccess = ref(false)
const activationError = ref('')
const showResendForm = ref(false)
const resendEmail = ref('')

// Computed
const { validatingToken, activating, resending, user, errorType } = computed(() => activationStore).value

const passwordStrength = computed(() => {
  return activationStore.checkPasswordStrength(form.value.password)
})

// Methods
const validateForm = (): boolean => {
  errors.value = {
    password: '',
    passwordConfirmation: ''
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

const handleActivation = async () => {
  if (!validateForm()) return

  const token = route.params.token as string
  const result = await activationStore.activateAccount(
    token,
    form.value.password,
    form.value.passwordConfirmation
  )

  if (result.success) {
    activationSuccess.value = true
    setTimeout(() => {
      router.push('/login')
    }, 3000)
  } else {
    activationError.value = result.message
  }
}

const handleResendActivation = async () => {
  if (!resendEmail.value) return

  const result = await activationStore.resendActivation(resendEmail.value)
  
  if (result.success) {
    alert('A new activation email has been sent. Please check your inbox.')
    router.push('/login')
  } else {
    alert(result.message)
  }
}

// Lifecycle
onMounted(async () => {
  const token = route.params.token as string
  if (token) {
    const result = await activationStore.validateToken(token)
    if (result.user?.email) {
      resendEmail.value = result.user.email
    }
  }
})
</script>
