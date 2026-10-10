<script setup lang="ts">
import { ref, onMounted } from 'vue'
import { useRouter, useRoute } from 'vue-router'
import { useAuthStore } from '@/stores/auth'
import { useHotelStore } from '@/stores/hotelStore'
import { useThemeStore } from '../stores/theme'
import { useLanguageStore } from '@/stores/language'
import LanguageSelector from '@/components/common/LanguageSelector.vue'
import {
  CheckCircle,
  AlertCircle,
  X,
  ArrowLeft,
  Mail,
  Lock,
  Eye,
  EyeOff,
  Hotel,
  UserCheck,
  Sun,
  Moon,
  ShieldCheck,
} from 'lucide-vue-next'

const router = useRouter()
const route = useRoute()
const auth = useAuthStore()
const themeStore = useThemeStore()
const languageStore = useLanguageStore()

const email = ref('')
const password = ref('')
const showPassword = ref(false)
const loading = ref(false)
const rememberMe = ref(false)

// Toast notification state
const showToast = ref(false)
const toastType = ref<'success' | 'error'>('success')
const toastMessage = ref('')

const errors = ref({
  email: '',
  password: '',
  general: '',
})

onMounted(() => {
  themeStore.initTheme()

  // Clear any leftover auth state when navigating to login
  const token = localStorage.getItem('token')
  if (!token && auth.token) {
    // If no token in storage but auth still has token, clear it
    auth.setToken(null)
    auth.setUser(null)
    auth.setCurrentHotel(null)
  }
})

const validateForm = (): boolean => {
  errors.value = {
    email: '',
    password: '',
    general: '',
  }

  let isValid = true

  if (!email.value.trim()) {
    errors.value.email = 'Email address is required'
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

    // Sync hotelStore state in memory immediately
    const hotelStore = useHotelStore()
    if (result?.current_hotel) {
      hotelStore.setHotels(result?.hotels || [], result.current_hotel)
    }

    // Resolve dynamic role directly from backend response (hotel-scoped role or user role)
    const effectiveRole = (
      result?.current_hotel?.role ||
      result?.user?.role ||
      auth.currentRole ||
      'admin'
    )
      .toLowerCase()
      .trim()

    const userName = auth.user?.first_name
      ? `${auth.user.first_name} ${auth.user.last_name}`
      : auth.user?.email || 'User'
    const userRole = effectiveRole.charAt(0).toUpperCase() + effectiveRole.slice(1)

    toastType.value = 'success'
    toastMessage.value = `✓ Welcome Back!\nSuccessfully authenticated as ${userName} (${userRole})`
    showToast.value = true

    setTimeout(async () => {
      showToast.value = false

      if (route.query.redirect && typeof route.query.redirect === 'string') {
        return router.push(route.query.redirect)
      }
      if (effectiveRole === 'admin' || auth.isPlatformAdmin) {
        router.push('/admin')
      } else if (effectiveRole === 'manager') {
        router.push('/manager')
      } else if (effectiveRole === 'receptionist') {
        router.push('/receptionist')
      } else if (effectiveRole === 'cashier') {
        router.push('/cashier')
      } else if (effectiveRole === 'chef') {
        router.push('/chef/pending-orders')
      } else if (effectiveRole === 'waiter') {
        router.push('/waiter')
      } else {
        router.push(`/${effectiveRole}`)
      }
    }, 600)
  } catch (error: any) {
    console.error('[LoginView] Login error:', error)
    const errorMsg = error?.message || 'Invalid credentials. Please verify your email and password.'
    toastType.value = 'error'
    toastMessage.value = `Authentication Failure\n${errorMsg}`
    showToast.value = true
    setTimeout(() => {
      showToast.value = false
    }, 500)
    errors.value.general = errorMsg
  } finally {
    loading.value = false
  }
}
</script>

<template>
  <div
    class="h-screen max-h-screen w-full flex flex-col justify-between items-center bg-slate-50 dark:bg-slate-950 text-slate-900 dark:text-slate-100 font-sans relative overflow-hidden p-3 sm:p-5 transition-colors duration-300"
  >
    <!-- Ambient Background Glows (Dark Mode Only) -->
    <div class="absolute inset-0 overflow-hidden pointer-events-none z-0">
      <div
        class="absolute -top-40 -left-40 w-96 h-96 bg-amber-500/0 dark:bg-amber-500/15 rounded-full blur-[120px]"
      ></div>
      <div
        class="absolute bottom-0 right-0 w-[30rem] h-[30rem] bg-indigo-500/0 dark:bg-indigo-600/20 rounded-full blur-[140px]"
      ></div>
    </div>

    <!-- Toast Notification Component -->
    <Transition name="toast-slide">
      <div v-if="showToast" class="fixed top-4 right-4 z-[9999] max-w-md w-full px-4">
        <div
          :class="[
            'rounded-2xl p-4 shadow-2xl backdrop-blur-xl border flex items-start gap-3.5 transition-all duration-300',
            toastType === 'success'
              ? 'bg-emerald-900/90 dark:bg-emerald-950/90 border-emerald-500/40 text-emerald-100 shadow-emerald-950/30'
              : 'bg-rose-900/90 dark:bg-rose-950/90 border-rose-500/40 text-rose-100 shadow-rose-950/30',
          ]"
        >
          <div
            class="p-2 rounded-xl"
            :class="
              toastType === 'success'
                ? 'bg-emerald-500/20 text-emerald-300'
                : 'bg-rose-500/20 text-rose-300'
            "
          >
            <CheckCircle v-if="toastType === 'success'" class="w-5 h-5" />
            <AlertCircle v-else class="w-5 h-5" />
          </div>

          <div class="flex-1 pt-0.5">
            <h4 class="font-semibold text-sm tracking-wide">
              {{ toastMessage.split('\n')[0] }}
            </h4>
            <p v-if="toastMessage.includes('\n')" class="text-xs opacity-90 mt-1 leading-relaxed">
              {{ toastMessage.split('\n').slice(1).join(' ') }}
            </p>
          </div>

          <button
            @click="showToast = false"
            class="opacity-70 hover:opacity-100 transition p-1 hover:bg-white/10 rounded-lg"
          >
            <X class="w-4 h-4" />
          </button>
        </div>
      </div>
    </Transition>

    <!-- Top Navigation & Brand Header -->
    <header class="w-full max-w-lg flex items-center justify-between z-10 py-1 sm:py-2">
      <button
        type="button"
        @click="router.push({ name: 'home' })"
        class="inline-flex items-center gap-2 text-xs font-bold text-slate-700 dark:text-slate-300 hover:text-amber-600 dark:hover:text-amber-400 transition-colors py-1.5 px-3 rounded-xl bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 shadow-xs cursor-pointer"
      >
        <ArrowLeft class="w-3.5 h-3.5" />
        <span class="hidden sm:inline">{{
          languageStore.t('return_to_guest_site', 'Return to Guest Site')
        }}</span>
      </button>

      <!-- Center Brand Badge -->
      <div
        class="flex items-center gap-2.5 px-3.5 py-1.5 rounded-2xl bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 shadow-xs"
      >
        <div
          class="flex h-7 w-7 items-center justify-center rounded-lg overflow-hidden p-0.5 bg-amber-500/10 border border-amber-500/20"
        >
          <img
            src="/images/Hotel logo.png"
            alt="Metropolitan Hotels Logo"
            class="w-full h-full object-contain"
          />
        </div>
        <span class="text-xs sm:text-sm font-black text-slate-900 dark:text-white tracking-tight"
          >Metropolitan Hotels</span
        >
        <span
          class="text-[9px] uppercase font-bold px-1.5 py-0.5 rounded-full bg-amber-500/10 text-amber-700 dark:text-amber-300 border border-amber-500/20"
        >
          Portal
        </span>
      </div>

      <!-- Controls: Language Selector & Dark / Light Theme Toggle -->
      <div class="flex items-center gap-1.5">
        <LanguageSelector variant="compact" />

        <button
          type="button"
          @click="themeStore.toggleTheme()"
          class="inline-flex items-center gap-2 text-xs font-bold px-3 py-1.5 rounded-xl border border-slate-200 dark:border-slate-800 bg-white dark:bg-slate-900 text-slate-800 dark:text-slate-200 hover:bg-slate-100 dark:hover:bg-slate-800 transition-all shadow-xs cursor-pointer"
          :title="themeStore.isDark ? 'Switch to Light Mode' : 'Switch to Dark Mode'"
        >
          <Sun v-if="themeStore.isDark" class="w-3.5 h-3.5 text-amber-400 animate-spin-slow" />
          <Moon v-else class="w-3.5 h-3.5 text-slate-700" />
          <span class="hidden sm:inline">{{ themeStore.isDark ? 'Light' : 'Dark' }}</span>
        </button>
      </div>
    </header>

    <!-- Center Clean Fixed Login Card Form -->
    <main class="my-auto w-full max-w-md z-10 py-2 sm:py-4">
      <div
        class="bg-white dark:bg-slate-900 border border-slate-200/90 dark:border-slate-800 rounded-3xl p-6 sm:p-8 shadow-xl dark:shadow-2xl dark:shadow-slate-950/80 space-y-4 sm:space-y-5 transition-colors duration-300"
      >
        <!-- Form Header with Branded Emblem -->
        <div class="space-y-2 text-center">
          <div
            class="mx-auto w-16 h-16 rounded-2xl bg-white dark:bg-slate-950 border border-slate-200/90 dark:border-slate-800 p-2 shadow-sm flex items-center justify-center mb-1 overflow-hidden ring-1 ring-amber-500/20"
          >
            <img
              src="/images/Hotel logo.png"
              alt="Metropolitan Hotels Logo"
              class="w-full h-full object-contain"
            />
          </div>
          <h2 class="text-xl sm:text-2xl font-black text-slate-900 dark:text-white tracking-tight">
            {{ languageStore.t('staff_admin_portal', 'Staff & Admin Portal') }}
          </h2>
          <p class="text-xs text-amber-600 dark:text-amber-400 font-bold uppercase tracking-wider">
            Metropolitan Hotels &mdash; Crafting Premier Stays
          </p>
        </div>

        <!-- General Server Error Banner -->
        <div
          v-if="errors.general"
          class="bg-rose-50 dark:bg-rose-950/40 border border-rose-200 dark:border-rose-800/60 text-rose-700 dark:text-rose-300 p-3 rounded-2xl text-xs flex items-start gap-2 animate-shake"
        >
          <AlertCircle class="w-4 h-4 flex-shrink-0 text-rose-500 mt-0.5" />
          <span class="leading-relaxed font-medium">{{ errors.general }}</span>
        </div>

        <!-- Login Form Inputs (With Chrome & Browser Autocomplete Support) -->
        <form @submit.prevent="login" method="POST" autocomplete="on" class="space-y-4">
          <!-- Email Input -->
          <div class="space-y-1">
            <label for="email" class="block text-xs font-bold text-slate-900 dark:text-slate-200">
              {{ languageStore.t('email_address', 'Work Email Address') }}
            </label>
            <div class="relative">
              <div
                class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none text-slate-400 dark:text-slate-500"
              >
                <Mail class="w-4 h-4" />
              </div>
              <input
                id="email"
                name="email"
                v-model="email"
                type="email"
                autocomplete="username"
                :placeholder="languageStore.t('enter_email', 'name@grandhorizon.com')"
                :class="[
                  'w-full pl-9 pr-3.5 py-2.5 text-sm bg-slate-50 dark:bg-slate-950/70 border rounded-xl focus:outline-none transition-all duration-200 text-slate-900 dark:text-slate-100 placeholder-slate-400 dark:placeholder-slate-500 font-medium',
                  errors.email
                    ? 'border-rose-500 focus:border-rose-500 focus:ring-2 focus:ring-rose-500/20 bg-rose-50/50 dark:bg-rose-950/20'
                    : 'border-slate-300 dark:border-slate-800 focus:border-amber-500 focus:ring-2 focus:ring-amber-500/20 hover:border-slate-400 dark:hover:border-slate-700',
                ]"
              />
            </div>
            <p
              v-if="errors.email"
              class="text-[11px] text-rose-500 flex items-center gap-1 pt-0.5 font-medium"
            >
              <AlertCircle class="w-3 h-3" /> {{ errors.email }}
            </p>
          </div>

          <!-- Password Input -->
          <div class="space-y-1">
            <div class="flex items-center justify-between">
              <label
                for="password"
                class="block text-xs font-bold text-slate-900 dark:text-slate-200"
              >
                {{ languageStore.t('password', 'Password') }}
              </label>
              <router-link
                to="/forgot-password"
                class="text-xs text-amber-600 dark:text-amber-400 hover:text-amber-700 dark:hover:text-amber-300 font-bold transition-colors hover:underline"
              >
                {{ languageStore.t('forgot_password', 'Forgot Password?') }}
              </router-link>
            </div>
            <div class="relative">
              <div
                class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none text-slate-400 dark:text-slate-500"
              >
                <Lock class="w-4 h-4" />
              </div>
              <input
                id="password"
                name="password"
                v-model="password"
                :type="showPassword ? 'text' : 'password'"
                autocomplete="current-password"
                :placeholder="languageStore.t('enter_password', '••••••••••••')"
                :class="[
                  'w-full pl-9 pr-9 py-2.5 text-sm bg-slate-50 dark:bg-slate-950/70 border rounded-xl focus:outline-none transition-all duration-200 text-slate-900 dark:text-slate-100 placeholder-slate-400 dark:placeholder-slate-500 font-medium',
                  errors.password
                    ? 'border-rose-500 focus:border-rose-500 focus:ring-2 focus:ring-rose-500/20 bg-rose-50/50 dark:bg-rose-950/20'
                    : 'border-slate-300 dark:border-slate-800 focus:border-amber-500 focus:ring-2 focus:ring-amber-500/20 hover:border-slate-400 dark:hover:border-slate-700',
                ]"
              />
              <button
                type="button"
                @click="showPassword = !showPassword"
                class="absolute inset-y-0 right-0 pr-3 flex items-center text-slate-400 hover:text-slate-600 dark:hover:text-slate-300 transition-colors cursor-pointer"
              >
                <Eye v-if="!showPassword" class="w-4 h-4" />
                <EyeOff v-else class="w-4 h-4" />
              </button>
            </div>
            <p
              v-if="errors.password"
              class="text-[11px] text-rose-500 flex items-center gap-1 pt-0.5 font-medium"
            >
              <AlertCircle class="w-3 h-3" /> {{ errors.password }}
            </p>
          </div>

          <!-- Remember Me -->
          <div class="flex items-center justify-between pt-0.5">
            <label class="flex items-center gap-2 cursor-pointer group">
              <input
                v-model="rememberMe"
                type="checkbox"
                class="w-4 h-4 rounded border-slate-300 dark:border-slate-700 bg-white dark:bg-slate-950 text-amber-500 focus:ring-amber-500/40 focus:ring-offset-0 transition"
              />
              <span
                class="text-xs text-slate-700 dark:text-slate-300 group-hover:text-slate-900 dark:group-hover:text-slate-100 transition font-semibold"
              >
                {{ languageStore.t('remember_me', 'Keep me signed in') }}
              </span>
            </label>
          </div>

          <!-- Submit Button -->
          <button
            type="submit"
            :disabled="loading"
            class="w-full relative overflow-hidden bg-gradient-to-r from-amber-500 via-amber-600 to-amber-500 hover:from-amber-400 hover:to-amber-500 text-slate-950 font-black py-3 px-4 rounded-xl transition-all duration-300 shadow-lg shadow-amber-500/25 hover:shadow-amber-500/40 disabled:opacity-50 disabled:cursor-not-allowed text-sm flex items-center justify-center gap-2 mt-1 cursor-pointer"
          >
            <div v-if="loading" class="flex items-center gap-2">
              <div
                class="w-4 h-4 border-2 border-slate-950 border-t-transparent rounded-full animate-spin"
              ></div>
              <span>{{ languageStore.t('signing_in', 'Authenticating...') }}</span>
            </div>
            <div v-else class="flex items-center gap-2">
              <span>{{ languageStore.t('sign_in', 'Sign In to Dashboard') }}</span>
              <UserCheck class="w-4 h-4" />
            </div>
          </button>
        </form>
      </div>
    </main>

    <!-- Minimalist Security & Copyright Footer -->
    <footer
      class="w-full max-w-lg text-center text-xs text-slate-600 dark:text-slate-400 space-y-0.5 font-medium z-10 py-1 sm:py-2"
    >
      <p>&copy; 2026 Grand Horizon Luxury Hotel & Dining System.</p>
      <p class="text-[11px] text-slate-500 dark:text-slate-500">
        {{
          languageStore.t('all_systems_operational', 'Enterprise Edition • 256-Bit SSL Encrypted')
        }}
      </p>
    </footer>
  </div>
</template>

<style scoped>
@keyframes shake {
  0%,
  100% {
    transform: translateX(0);
  }
  20%,
  60% {
    transform: translateX(-3px);
  }
  40%,
  80% {
    transform: translateX(3px);
  }
}

.animate-shake {
  animation: shake 0.4s cubic-bezier(0.36, 0.07, 0.19, 0.97) both;
}

@keyframes spinSlow {
  from {
    transform: rotate(0deg);
  }
  to {
    transform: rotate(360deg);
  }
}

.animate-spin-slow {
  animation: spinSlow 12s linear infinite;
}

/* Toast Slide Transition */
.toast-slide-enter-active {
  transition: all 0.4s cubic-bezier(0.16, 1, 0.3, 1);
}
.toast-slide-leave-active {
  transition: all 0.3s cubic-bezier(0.7, 0, 0.84, 0);
}
.toast-slide-enter-from {
  transform: translateY(-20px) scale(0.95);
  opacity: 0;
}
.toast-slide-leave-to {
  transform: translateX(100px);
  opacity: 0;
}
</style>
