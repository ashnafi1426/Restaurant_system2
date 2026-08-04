<script setup lang="ts">
import { ref } from 'vue'
import { useRouter } from 'vue-router'
import { useAuthStore } from '../stores/auth'
import { CheckCircle, AlertCircle, X } from 'lucide-vue-next'

const router = useRouter()
const auth = useAuthStore()
const email = ref('')
const password = ref('')
const showPassword = ref(false)
const loading = ref(false)

// Toast notification state
const showToast = ref(false)
const toastType = ref<'success' | 'error'>('success')
const toastMessage = ref('')

const errors = ref({
  email: '',
  password: '',
  general: '',
})

const validateForm = (): boolean => {
  errors.value = {
    email: '',
    password: '',
    general: '',
  }

  let isValid = true

  if (!email.value.trim()) {
    errors.value.email = 'Email is required'
    isValid = false
  } else if (!/^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(email.value)) {
    errors.value.email = 'Please enter a valid email address'
    isValid = false
  }

  if (!password.value.trim()) {
    errors.value.password = 'Password is required'
    isValid = false
  } else if (password.value.length < 6) {
    errors.value.password = 'Password must be at least 6 characters'
    isValid = false
  }

  return isValid
}

const login = async (): Promise<void> => {
  if (!validateForm()) return
  loading.value = true
  try {
    const result = await auth.login(email.value, password.value)
    const userName = auth.user?.first_name ? `${auth.user.first_name} ${auth.user.last_name}` : auth.user?.email || 'User'
    const userRole = auth.user?.role ? auth.user.role.charAt(0).toUpperCase() + auth.user.role.slice(1) : 'User'
    toastType.value = 'success'
    toastMessage.value = `✓ Login successful!\n📋 Welcome ${userName} | Role: ${userRole}`
    showToast.value = true
    
    // Auto-hide after 3 seconds then redirect
    setTimeout(async () => {
      showToast.value = false
      const role = auth.user?.role
      const roleRoutes: Record<string, string> = {
        admin: '/admin',
        receptionist: '/receptionist',
        cashier: '/cashier',
        chef: '/chef',
        manager: '/manager',
        waiter: '/waiter',
      }
      router.push(roleRoutes[role] || '/')
    }, 3000)
  } catch (error: any) {
    console.error('[LOGIN] Error:', error)
    console.error('[LOGIN] Error response:', error.response?.data)
    const errorMsg = error?.message || 'Invalid email or password'
    toastType.value = 'error'
    toastMessage.value = `✗ ${errorMsg}`
    showToast.value = true
    setTimeout(() => {
      showToast.value = false
    }, 4000)
    errors.value.general = errorMsg
  } finally {
    loading.value = false
  }
}
</script>

<template>
  <div
    class="min-h-screen flex items-center justify-center bg-gradient-to-br from-slate-50 via-blue-50 to-indigo-50 px-4 relative overflow-hidden"
  >
    <!-- Simplified Background -->
    <div class="absolute inset-0 overflow-hidden pointer-events-none">
      <div
        class="absolute -top-32 -right-32 w-64 h-64 bg-purple-200/30 rounded-full blur-3xl"
      ></div>
      <div
        class="absolute -bottom-32 -left-32 w-64 h-64 bg-blue-200/30 rounded-full blur-3xl"
      ></div>
    </div>

    <!-- Toast Notification -->
    <Transition name="slide-fade">
      <div 
        v-if="showToast" 
        :class="[
          'fixed top-6 right-6 z-[9999] max-w-sm w-full',
          'animate-slide-in'
        ]"
      >
        <!-- SUCCESS TOAST -->
        <div 
          v-if="toastType === 'success'"
          class="bg-white rounded-xl shadow-2xl overflow-hidden border-l-4 border-green-500"
        >
          <div class="flex items-start gap-4 p-5">
            <!-- ICON -->
            <div class="flex-shrink-0 pt-0.5">
              <div class="flex items-center justify-center h-6 w-6 rounded-full bg-green-100">
                <CheckCircle :size="20" class="text-green-600" />
              </div>
            </div>

            <!-- TEXT CONTENT -->
            <div class="flex-1 min-w-0">
              <p class="text-sm font-semibold text-green-800">
                {{ toastMessage.split('\n')[0] || 'Success!' }}
              </p>
              <p v-if="toastMessage.includes('\n')" class="text-sm text-green-700 mt-1">
                {{ toastMessage.split('\n').slice(1).join('\n') }}
              </p>
            </div>

            <!-- CLOSE BUTTON -->
            <button
              @click="showToast = false"
              class="flex-shrink-0 text-green-600 hover:text-green-800 transition p-1"
            >
              <X :size="18" />
            </button>
          </div>
        </div>

        <!-- ERROR TOAST -->
        <div 
          v-else
          class="bg-white rounded-xl shadow-2xl overflow-hidden border-l-4 border-red-500"
        >
          <div class="flex items-start gap-4 p-5">
            <!-- ICON -->
            <div class="flex-shrink-0 pt-0.5">
              <div class="flex items-center justify-center h-6 w-6 rounded-full bg-red-100">
                <AlertCircle :size="20" class="text-red-600" />
              </div>
            </div>

            <!-- TEXT CONTENT -->
            <div class="flex-1 min-w-0">
              <p class="text-sm font-semibold text-red-800">
                {{ toastMessage.split('\n')[0] || 'Error!' }}
              </p>
              <p v-if="toastMessage.includes('\n')" class="text-sm text-red-700 mt-1">
                {{ toastMessage.split('\n').slice(1).join('\n') }}
              </p>
            </div>

            <!-- CLOSE BUTTON -->
            <button
              @click="showToast = false"
              class="flex-shrink-0 text-red-600 hover:text-red-800 transition p-1"
            >
              <X :size="18" />
            </button>
          </div>
        </div>
      </div>
    </Transition>

    <!-- Compact Login Card -->
    <div class="w-full max-w-sm relative animate-fadeInUp">
      <!-- Card -->
      <div
        class="bg-white/90 backdrop-blur-sm rounded-2xl shadow-xl p-6 md:p-7 border border-white/50"
      >
        <!-- Compact Header -->
        <div class="text-center mb-5">
          <!-- Small Logo -->
          <div
            class="inline-flex items-center justify-center w-12 h-12 bg-gradient-to-br from-blue-600 to-purple-600 rounded-xl shadow-md mb-3"
          >
            <svg class="w-6 h-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path
                stroke-linecap="round"
                stroke-linejoin="round"
                stroke-width="2"
                d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"
              />
            </svg>
          </div>

          <h1 class="text-xl font-bold text-slate-800">
            Hotel
            <span class="text-transparent bg-clip-text bg-gradient-to-r from-blue-600 to-purple-600"
              >Management</span
            >
          </h1>
          <p class="text-slate-500 text-xs mt-1 font-medium">Sign in to continue</p>
        </div>

        <!-- General Error - Compact -->
        <div
          v-if="errors.general"
          class="mb-4 bg-red-50/80 border-l-3 border-red-500 text-red-700 px-3 py-2 rounded-lg text-xs animate-shake"
        >
          <div class="flex items-center gap-1.5">
            <svg class="w-4 h-4 flex-shrink-0" fill="currentColor" viewBox="0 0 20 20">
              <path
                fill-rule="evenodd"
                d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.707 7.293a1 1 0 00-1.414 1.414L8.586 10l-1.293 1.293a1 1 0 101.414 1.414L10 11.414l1.293 1.293a1 1 0 001.414-1.414L11.414 10l1.293-1.293a1 1 0 00-1.414-1.414L10 8.586 8.707 7.293z"
                clip-rule="evenodd"
              />
            </svg>
            {{ errors.general }}
          </div>
        </div>
        <div class="mb-3.5">
          <label class="block mb-1 text-xs font-semibold text-slate-700"> Email Address </label>
          <div class="relative">
            <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
              <svg
                class="w-4 h-4 text-slate-400"
                fill="none"
                stroke="currentColor"
                viewBox="0 0 24 24"
              >
                <path
                  stroke-linecap="round"
                  stroke-linejoin="round"
                  stroke-width="2"
                  d="M16 12a4 4 0 10-8 0 4 4 0 008 0zm0 0v1.5a2.5 2.5 0 005 0V12a9 9 0 10-9 9m4.5-1.206a8.959 8.959 0 01-4.5 1.207"
                />
              </svg>
            </div>
            <input
              v-model="email"
              type="email"
              placeholder="admin@example.com"
              :class="[
                'w-full pl-9 pr-3 py-2 text-sm border rounded-lg focus:outline-none focus:ring-1 transition-all duration-200 text-slate-700 placeholder-slate-400',
                errors.email
                  ? 'border-red-300 focus:border-red-500 focus:ring-red-200 bg-red-50/50'
                  : 'border-slate-200 focus:border-blue-500 focus:ring-blue-200 hover:border-blue-300',
              ]"
            />
          </div>
          <p v-if="errors.email" class="mt-1 text-xs text-red-500 flex items-center gap-1">
            <svg class="w-3.5 h-3.5" fill="currentColor" viewBox="0 0 20 20">
              <path
                fill-rule="evenodd"
                d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7-4a1 1 0 11-2 0 1 1 0 012 0zM9 9a1 1 0 000 2v3a1 1 0 001 1h1a1 1 0 100-2v-3a1 1 0 00-1-1H9z"
                clip-rule="evenodd"
              />
            </svg>
            {{ errors.email }}
          </p>
        </div>

        <!-- Password Field - Compact -->
        <div class="mb-4">
          <div class="flex items-center justify-between mb-1">
            <label class="text-xs font-semibold text-slate-700">Password</label>
            <router-link
              to="/forgot-password"
              class="text-[10px] text-blue-600 hover:text-blue-800 hover:underline font-medium transition-colors"
            >
              Forgot?
            </router-link>
          </div>
          <div class="relative">
            <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
              <svg
                class="w-4 h-4 text-slate-400"
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
            <input
              v-model="password"
              :type="showPassword ? 'text' : 'password'"
              placeholder="Enter password"
              :class="[
                'w-full pl-9 pr-9 py-2 text-sm border rounded-lg focus:outline-none focus:ring-1 transition-all duration-200 text-slate-700 placeholder-slate-400',
                errors.password
                  ? 'border-red-300 focus:border-red-500 focus:ring-red-200 bg-red-50/50'
                  : 'border-slate-200 focus:border-blue-500 focus:ring-blue-200 hover:border-blue-300',
              ]"
            />
            <button
              type="button"
              @click="showPassword = !showPassword"
              class="absolute inset-y-0 right-0 pr-3 flex items-center text-slate-400 hover:text-slate-600 transition-colors"
            >
              <svg
                v-if="!showPassword"
                class="w-4 h-4"
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
              <svg v-else class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path
                  stroke-linecap="round"
                  stroke-linejoin="round"
                  stroke-width="2"
                  d="M13.875 18.825A10.05 10.05 0 0112 19c-4.478 0-8.268-2.943-9.542-7a9.97 9.97 0 011.563-3.029m5.858.908a3 3 0 114.243 4.243M9.878 9.878l4.242 4.242M9.88 9.88l-3.29-3.29m7.532 7.532l3.29 3.29M3 3l3.59 3.59m0 0A9.953 9.953 0 0112 5c4.478 0 8.268 2.943 9.542 7a10.025 10.025 0 01-4.132 5.411m0 0L21 21"
                />
              </svg>
            </button>
          </div>
          <p v-if="errors.password" class="mt-1 text-xs text-red-500 flex items-center gap-1">
            <svg class="w-3.5 h-3.5" fill="currentColor" viewBox="0 0 20 20">
              <path
                fill-rule="evenodd"
                d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7-4a1 1 0 11-2 0 1 1 0 012 0zM9 9a1 1 0 000 2v3a1 1 0 001 1h1a1 1 0 100-2v-3a1 1 0 00-1-1H9z"
                clip-rule="evenodd"
              />
            </svg>
            {{ errors.password }}
          </p>
        </div>

        <!-- Login Button - Compact -->
        <button
          @click="login"
          :disabled="loading"
          class="w-full relative overflow-hidden bg-gradient-to-r from-blue-600 to-purple-600 hover:from-blue-700 hover:to-purple-700 disabled:from-slate-400 disabled:to-slate-400 text-white font-semibold py-2.5 rounded-lg transition-all duration-300 shadow-md hover:shadow-lg disabled:cursor-not-allowed text-sm"
        >
          <span v-if="loading" class="flex items-center justify-center gap-2">
            <!-- UNIFIED CYAN + YELLOW SPINNER -->
            <div class="relative w-4 h-4">
              <!-- Static background - BRIGHT CYAN -->
              <svg class="absolute inset-0 w-full h-full" viewBox="0 0 100 100">
                <circle cx="50" cy="50" r="40" fill="none" stroke="#0EA5E9" stroke-width="5" opacity="0.3" />
              </svg>
              
              <!-- Animated spinner - BRIGHT YELLOW -->
              <div class="absolute inset-0 animate-spin-fast" style="animation: spin 1.5s linear infinite;">
                <svg viewBox="0 0 100 100" class="w-full h-full">
                  <circle cx="50" cy="50" r="40" fill="none" stroke="#FBBF24" stroke-width="6" stroke-linecap="round" stroke-dasharray="60 240" />
                </svg>
              </div>
            </div>
            Signing In...
          </span>
          <span v-else class="flex items-center justify-center gap-1.5">
            Sign In
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path
                stroke-linecap="round"
                stroke-linejoin="round"
                stroke-width="2"
                d="M13 7l5 5m0 0l-5 5m5-5H6"
              />
            </svg>
          </span>
        </button>

        <!-- Divider - Compact -->
        <div class="relative my-4">
          <div class="absolute inset-0 flex items-center">
            <div class="w-full border-t border-slate-200/80"></div>
          </div>
          <div class="relative flex justify-center text-[10px]">
            <span class="px-3 bg-white/90 text-slate-400">Secure Access</span>
          </div>
        </div>

        <!-- Footer - Compact -->
        <div class="flex items-center justify-center gap-2 text-[10px] text-slate-400">
          <span class="inline-flex items-center gap-1">
            <svg class="w-3 h-3" fill="currentColor" viewBox="0 0 20 20">
              <path
                fill-rule="evenodd"
                d="M5 9V7a5 5 0 0110 0v2a2 2 0 012 2v5a2 2 0 01-2 2H5a2 2 0 01-2-2v-5a2 2 0 012-2zm8-2v2H7V7a3 3 0 016 0z"
                clip-rule="evenodd"
              />
            </svg>
            SSL
          </span>
          <span>•</span>
          <span>v2.0</span>
          <span>•</span>
          <span>&copy; 2024</span>
        </div>
      </div>
    </div>
  </div>
</template>

<style scoped>
@keyframes fadeInUp {
  from {
    opacity: 0;
    transform: translateY(15px);
  }
  to {
    opacity: 1;
    transform: translateY(0);
  }
}

@keyframes shake {
  0%,
  100% {
    transform: translateX(0);
  }
  10%,
  30%,
  50%,
  70%,
  90% {
    transform: translateX(-2px);
  }
  20%,
  40%,
  60%,
  80% {
    transform: translateX(2px);
  }
}

@keyframes spin {
  from {
    transform: rotate(0deg);
  }
  to {
    transform: rotate(360deg);
  }
}

/* Slide-fade transition for toast notifications */
.slide-fade-enter-active {
  transition: all 0.4s cubic-bezier(0.23, 1, 0.320, 1);
}

.slide-fade-leave-active {
  transition: all 0.3s cubic-bezier(0.600, 0, 0.735, 0.045);
}

.slide-fade-enter-from {
  transform: translateX(100px);
  opacity: 0;
}

.slide-fade-leave-to {
  transform: translateX(100px);
  opacity: 0;
}

/* Toast slide-in animation from top-right */
@keyframes animate-slide-in {
  0% {
    transform: translateX(calc(100% + 24px));
    opacity: 0;
  }
  100% {
    transform: translateX(0);
    opacity: 1;
  }
}

.animate-slide-in {
  animation: animate-slide-in 0.4s cubic-bezier(0.23, 1, 0.320, 1) forwards;
}

.animate-fadeInUp {
  animation: fadeInUp 0.4s ease-out forwards;
}

.animate-shake {
  animation: shake 0.4s ease-in-out;
}

.animate-spin-fast {
  animation: spin 1.5s linear infinite;
}

/* Smooth hover transitions */
input,
button {
  transition: all 0.2s ease;
}

/* Better focus styles */
input:focus {
  outline: none;
}

/* Custom scrollbar */
::-webkit-scrollbar {
  width: 6px;
}

::-webkit-scrollbar-track {
  background: #f1f1f1;
  border-radius: 3px;
}

::-webkit-scrollbar-thumb {
  background: #c1c1c1;
  border-radius: 3px;
}

::-webkit-scrollbar-thumb:hover {
  background: #a8a8a8;
}

/* Input autofill styles */
input:-webkit-autofill {
  -webkit-box-shadow: 0 0 0 30px white inset !important;
  -webkit-text-fill-color: #1e293b !important;
}
</style>
