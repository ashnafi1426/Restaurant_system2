<template>
  <DashboardLayout>
    <div class="space-y-6 bg-slate-50 dark:bg-slate-950 min-h-screen p-4 sm:p-6 max-w-full font-sans">

      <!-- Loading State -->
      <div v-if="loading" class="flex items-center justify-center py-20">
        <div class="flex flex-col items-center gap-3">
          <div class="w-10 h-10 border-4 border-teal-500/30 border-t-teal-500 rounded-full animate-spin"></div>
          <p class="text-sm text-slate-500 dark:text-slate-400 font-medium">Loading profile...</p>
        </div>
      </div>

      <template v-else>
        <!-- Temp Password Banner -->
        <div
          v-if="mustChangePassword"
          class="bg-amber-500/10 border border-amber-500/30 rounded-2xl p-4 flex items-start gap-3"
        >
          <AlertTriangle class="w-5 h-5 text-amber-500 flex-shrink-0 mt-0.5" />
          <div>
            <p class="text-sm font-black text-amber-700 dark:text-amber-400">Action Required: Change Your Temporary Password</p>
            <p class="text-xs text-amber-600 dark:text-amber-500 mt-0.5">
              You are using a temporary password set by the administrator. Please change it now to secure your account.
            </p>
          </div>
        </div>

        <!-- Profile Header Banner -->
        <div class="bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 p-6 rounded-3xl shadow-sm flex flex-col md:flex-row items-center justify-between gap-6">
          <div class="flex flex-col md:flex-row items-center gap-5 text-center md:text-left">
            <!-- Profile Avatar with photo upload -->
            <div class="relative flex-shrink-0 group">
              <div
                class="w-24 h-24 rounded-full overflow-hidden border-4 border-teal-500/30 shadow-md cursor-pointer relative"
                @click="triggerPhotoUpload"
              >
                <img
                  v-if="photoPreview || profilePhotoUrl"
                  :src="photoPreview || profilePhotoUrl || ''"
                  alt="Profile photo"
                  class="w-full h-full object-cover"
                />
                <div
                  v-else
                  class="w-full h-full bg-teal-500/10 text-teal-600 dark:text-teal-400 flex items-center justify-center font-black text-2xl"
                >
                  {{ (user?.first_name || 'R')?.[0]?.toUpperCase() }}
                </div>
                <!-- Hover Overlay -->
                <div class="absolute inset-0 bg-black/50 opacity-0 group-hover:opacity-100 transition-opacity flex items-center justify-center rounded-full">
                  <Camera class="w-6 h-6 text-white" />
                </div>
              </div>
              <!-- Upload spinner -->
              <div
                v-if="uploadingPhoto"
                class="absolute inset-0 flex items-center justify-center bg-black/40 rounded-full"
              >
                <div class="w-6 h-6 border-2 border-white/40 border-t-white rounded-full animate-spin"></div>
              </div>
              <!-- Hidden file input -->
              <input
                ref="photoInput"
                type="file"
                accept="image/jpeg,image/png,image/jpg,image/gif"
                class="hidden"
                @change="onPhotoSelected"
              />
            </div>

            <!-- User Header Details -->
            <div>
              <div class="flex flex-wrap items-center justify-center md:justify-start gap-2.5">
                <h1 class="text-2xl font-black text-slate-900 dark:text-white tracking-tight">
                  {{ user?.first_name }} {{ user?.last_name }}
                </h1>
                <span class="px-2.5 py-0.5 rounded-full text-[10px] font-black uppercase tracking-wider bg-emerald-500/10 text-emerald-600 dark:text-emerald-400 border border-emerald-500/20">
                  Active Receptionist
                </span>
              </div>
              <div class="flex flex-wrap items-center justify-center md:justify-start gap-2 mt-1.5 text-xs text-slate-500 dark:text-slate-400 font-medium">
                <span class="font-mono font-bold text-teal-600 dark:text-teal-400">Front Desk</span>
                <span>•</span>
                <span>{{ user?.email }}</span>
              </div>
              <p class="text-[10px] text-slate-400 mt-2 italic">Click avatar to upload a profile photo</p>
            </div>
          </div>
        </div>

        <!-- Global toast message -->
        <Transition name="fade">
          <div
            v-if="globalMessage.text"
            :class="[
              'rounded-2xl p-4 flex items-center gap-3 text-sm font-semibold',
              globalMessage.type === 'success'
                ? 'bg-emerald-500/10 border border-emerald-500/30 text-emerald-700 dark:text-emerald-400'
                : 'bg-red-500/10 border border-red-500/30 text-red-700 dark:text-red-400'
            ]"
          >
            <CheckCircle v-if="globalMessage.type === 'success'" class="w-4 h-4 flex-shrink-0" />
            <XCircle v-else class="w-4 h-4 flex-shrink-0" />
            {{ globalMessage.text }}
          </div>
        </Transition>

        <div class="grid grid-cols-1 lg:grid-cols-4 gap-6">
          <!-- Sidebar Quick Info -->
          <div class="lg:col-span-1 space-y-6">
            <div class="bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-3xl p-5 shadow-sm space-y-4">
              <h3 class="text-xs font-black text-slate-400 uppercase tracking-wider">Quick Info</h3>
              <div class="space-y-3 text-xs">
                <div class="flex items-start gap-3">
                  <Briefcase class="w-4 h-4 text-blue-500 mt-0.5 flex-shrink-0" />
                  <div>
                    <p class="text-[10px] font-bold text-slate-400 uppercase">Role</p>
                    <p class="font-extrabold text-slate-900 dark:text-white">Receptionist</p>
                  </div>
                </div>
                <div class="flex items-start gap-3">
                  <Mail class="w-4 h-4 text-emerald-500 mt-0.5 flex-shrink-0" />
                  <div class="min-w-0 flex-1">
                    <p class="text-[10px] font-bold text-slate-400 uppercase">Email</p>
                    <p class="font-extrabold text-slate-900 dark:text-white truncate">{{ user?.email }}</p>
                  </div>
                </div>
                <div class="flex items-start gap-3">
                  <Phone class="w-4 h-4 text-purple-500 mt-0.5 flex-shrink-0" />
                  <div>
                    <p class="text-[10px] font-bold text-slate-400 uppercase">Phone</p>
                    <p class="font-extrabold text-slate-900 dark:text-white">{{ user?.phone || 'Not provided' }}</p>
                  </div>
                </div>
                <div v-if="receptionistProfile?.shift" class="flex items-start gap-3">
                  <Clock class="w-4 h-4 text-orange-500 mt-0.5 flex-shrink-0" />
                  <div>
                    <p class="text-[10px] font-bold text-slate-400 uppercase">Shift</p>
                    <p class="font-extrabold text-slate-900 dark:text-white capitalize">{{ receptionistProfile.shift }}</p>
                  </div>
                </div>
                <div v-if="receptionistProfile?.desk_number" class="flex items-start gap-3">
                  <MapPin class="w-4 h-4 text-teal-500 mt-0.5 flex-shrink-0" />
                  <div>
                    <p class="text-[10px] font-bold text-slate-400 uppercase">Desk</p>
                    <p class="font-extrabold text-slate-900 dark:text-white">{{ receptionistProfile.desk_number }}</p>
                  </div>
                </div>
              </div>
            </div>
          </div>

          <!-- Main Form Content -->
          <div class="lg:col-span-3 space-y-6">

            <!-- Personal Information Card -->
            <div class="bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-3xl p-6 shadow-sm space-y-6">
              <div>
                <h3 class="text-lg font-black text-slate-900 dark:text-white">Personal Information</h3>
                <p class="text-xs text-slate-500 dark:text-slate-400 mt-0.5">Update your personal account details.</p>
              </div>

              <form @submit.prevent="saveProfile" class="space-y-4 text-xs font-sans">
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                  <div>
                    <label class="block text-[10px] font-extrabold uppercase text-slate-500 dark:text-slate-400 mb-1">First Name *</label>
                    <input
                      v-model="form.first_name"
                      type="text"
                      required
                      class="w-full px-3.5 py-2.5 rounded-xl bg-slate-50 dark:bg-slate-950 border border-slate-200 dark:border-slate-800 text-slate-900 dark:text-white font-bold focus:outline-none focus:border-teal-500 transition"
                    />
                  </div>
                  <div>
                    <label class="block text-[10px] font-extrabold uppercase text-slate-500 dark:text-slate-400 mb-1">Last Name *</label>
                    <input
                      v-model="form.last_name"
                      type="text"
                      required
                      class="w-full px-3.5 py-2.5 rounded-xl bg-slate-50 dark:bg-slate-950 border border-slate-200 dark:border-slate-800 text-slate-900 dark:text-white font-bold focus:outline-none focus:border-teal-500 transition"
                    />
                  </div>
                </div>

                <div>
                  <label class="block text-[10px] font-extrabold uppercase text-slate-500 dark:text-slate-400 mb-1">Email Address</label>
                  <input
                    :value="user?.email"
                    type="email"
                    disabled
                    class="w-full px-3.5 py-2.5 rounded-xl bg-slate-100 dark:bg-slate-800 border border-slate-200 dark:border-slate-700 text-slate-500 dark:text-slate-400 font-bold cursor-not-allowed"
                  />
                  <p class="text-[10px] text-slate-400 mt-1">Email cannot be changed. Contact admin to update.</p>
                </div>

                <div>
                  <label class="block text-[10px] font-extrabold uppercase text-slate-500 dark:text-slate-400 mb-1">Phone Number</label>
                  <input
                    v-model="form.phone"
                    type="tel"
                    placeholder="+251 XXX XXX XXX"
                    class="w-full px-3.5 py-2.5 rounded-xl bg-slate-50 dark:bg-slate-950 border border-slate-200 dark:border-slate-800 text-slate-900 dark:text-white font-bold focus:outline-none focus:border-teal-500 transition"
                  />
                </div>

                <!-- Profile error -->
                <div v-if="profileError" class="text-xs text-red-500 font-semibold flex items-center gap-1.5">
                  <XCircle class="w-3.5 h-3.5" />{{ profileError }}
                </div>

                <div class="flex items-center justify-end pt-4 border-t border-slate-100 dark:border-slate-800">
                  <button
                    type="submit"
                    :disabled="saving"
                    class="px-5 py-2 rounded-xl bg-teal-600 hover:bg-teal-700 text-white font-black text-xs shadow-md shadow-teal-600/20 transition flex items-center gap-1.5 cursor-pointer disabled:opacity-50"
                  >
                    <Loader2 v-if="saving" class="w-3.5 h-3.5 animate-spin" />
                    <Save v-else class="w-3.5 h-3.5" />
                    <span>{{ saving ? 'Saving...' : 'Save Changes' }}</span>
                  </button>
                </div>
              </form>
            </div>

            <!-- Change Password Card -->
            <div
              :class="[
                'bg-white dark:bg-slate-900 border rounded-3xl p-6 shadow-sm space-y-6 transition-all',
                mustChangePassword
                  ? 'border-amber-400/50 ring-2 ring-amber-400/20'
                  : 'border-slate-200 dark:border-slate-800'
              ]"
            >
              <div class="flex items-start gap-3">
                <div :class="['p-2 rounded-xl', mustChangePassword ? 'bg-amber-500/10' : 'bg-slate-100 dark:bg-slate-800']">
                  <Lock :class="['w-4 h-4', mustChangePassword ? 'text-amber-500' : 'text-slate-500']" />
                </div>
                <div>
                  <h3 class="text-lg font-black text-slate-900 dark:text-white">
                    {{ mustChangePassword ? '⚠ Change Temporary Password' : 'Change Password' }}
                  </h3>
                  <p class="text-xs text-slate-500 dark:text-slate-400 mt-0.5">
                    {{ mustChangePassword
                      ? 'Your account was created with a temporary password. Enter it below, then set a new secure password.'
                      : 'For your security, use a strong unique password.' }}
                  </p>
                </div>
              </div>

              <form @submit.prevent="changePassword" class="space-y-4 text-xs font-sans">
                <!-- Current / Temporary Password -->
                <div>
                  <label class="block text-[10px] font-extrabold uppercase text-slate-500 dark:text-slate-400 mb-1">
                    {{ mustChangePassword ? 'Temporary Password (from admin email)' : 'Current Password' }} *
                  </label>
                  <div class="relative">
                    <input
                      v-model="passwordForm.current_password"
                      :type="showCurrentPw ? 'text' : 'password'"
                      required
                      :placeholder="mustChangePassword ? 'Enter the temporary password sent to your email' : 'Enter your current password'"
                      class="w-full px-3.5 py-2.5 pr-10 rounded-xl bg-slate-50 dark:bg-slate-950 border border-slate-200 dark:border-slate-800 text-slate-900 dark:text-white font-bold focus:outline-none focus:border-teal-500 transition"
                    />
                    <button
                      type="button"
                      @click="showCurrentPw = !showCurrentPw"
                      class="absolute right-3 top-1/2 -translate-y-1/2 text-slate-400 hover:text-slate-600 dark:hover:text-slate-300"
                    >
                      <Eye v-if="!showCurrentPw" class="w-4 h-4" />
                      <EyeOff v-else class="w-4 h-4" />
                    </button>
                  </div>
                </div>

                <!-- New Password -->
                <div>
                  <label class="block text-[10px] font-extrabold uppercase text-slate-500 dark:text-slate-400 mb-1">New Password *</label>
                  <div class="relative">
                    <input
                      v-model="passwordForm.new_password"
                      :type="showNewPw ? 'text' : 'password'"
                      required
                      minlength="8"
                      placeholder="Minimum 8 characters"
                      class="w-full px-3.5 py-2.5 pr-10 rounded-xl bg-slate-50 dark:bg-slate-950 border border-slate-200 dark:border-slate-800 text-slate-900 dark:text-white font-bold focus:outline-none focus:border-teal-500 transition"
                    />
                    <button
                      type="button"
                      @click="showNewPw = !showNewPw"
                      class="absolute right-3 top-1/2 -translate-y-1/2 text-slate-400 hover:text-slate-600 dark:hover:text-slate-300"
                    >
                      <Eye v-if="!showNewPw" class="w-4 h-4" />
                      <EyeOff v-else class="w-4 h-4" />
                    </button>
                  </div>
                  <!-- Password strength indicator -->
                  <div v-if="passwordForm.new_password" class="mt-2 space-y-1">
                    <div class="flex gap-1">
                      <div
                        v-for="i in 4"
                        :key="i"
                        :class="[
                          'h-1 flex-1 rounded-full transition-all',
                          i <= passwordStrength ? strengthColor : 'bg-slate-200 dark:bg-slate-700'
                        ]"
                      ></div>
                    </div>
                    <p :class="['text-[10px] font-bold', strengthTextColor]">{{ strengthLabel }}</p>
                  </div>
                </div>

                <!-- Confirm New Password -->
                <div>
                  <label class="block text-[10px] font-extrabold uppercase text-slate-500 dark:text-slate-400 mb-1">Confirm New Password *</label>
                  <div class="relative">
                    <input
                      v-model="passwordForm.new_password_confirmation"
                      :type="showConfirmPw ? 'text' : 'password'"
                      required
                      placeholder="Re-enter new password"
                      :class="[
                        'w-full px-3.5 py-2.5 pr-10 rounded-xl bg-slate-50 dark:bg-slate-950 border text-slate-900 dark:text-white font-bold focus:outline-none transition',
                        passwordForm.new_password_confirmation && passwordForm.new_password !== passwordForm.new_password_confirmation
                          ? 'border-red-400 focus:border-red-500'
                          : passwordForm.new_password_confirmation && passwordForm.new_password === passwordForm.new_password_confirmation
                            ? 'border-emerald-400 focus:border-emerald-500'
                            : 'border-slate-200 dark:border-slate-800 focus:border-teal-500'
                      ]"
                    />
                    <button
                      type="button"
                      @click="showConfirmPw = !showConfirmPw"
                      class="absolute right-3 top-1/2 -translate-y-1/2 text-slate-400 hover:text-slate-600 dark:hover:text-slate-300"
                    >
                      <Eye v-if="!showConfirmPw" class="w-4 h-4" />
                      <EyeOff v-else class="w-4 h-4" />
                    </button>
                  </div>
                  <p
                    v-if="passwordForm.new_password_confirmation && passwordForm.new_password !== passwordForm.new_password_confirmation"
                    class="text-[10px] text-red-500 font-bold mt-1"
                  >
                    Passwords do not match.
                  </p>
                </div>

                <!-- Password change error -->
                <div v-if="passwordError" class="text-xs text-red-500 font-semibold flex items-center gap-1.5 bg-red-50 dark:bg-red-900/20 p-3 rounded-xl border border-red-200 dark:border-red-800">
                  <XCircle class="w-3.5 h-3.5 flex-shrink-0" />{{ passwordError }}
                </div>

                <div class="flex items-center justify-end pt-4 border-t border-slate-100 dark:border-slate-800">
                  <button
                    type="submit"
                    :disabled="changingPassword || !passwordForm.current_password || !passwordForm.new_password || passwordForm.new_password !== passwordForm.new_password_confirmation"
                    :class="[
                      'px-5 py-2 rounded-xl text-white font-black text-xs shadow-md transition flex items-center gap-1.5 cursor-pointer disabled:opacity-50 disabled:cursor-not-allowed',
                      mustChangePassword
                        ? 'bg-amber-500 hover:bg-amber-600 shadow-amber-500/20'
                        : 'bg-teal-600 hover:bg-teal-700 shadow-teal-600/20'
                    ]"
                  >
                    <Loader2 v-if="changingPassword" class="w-3.5 h-3.5 animate-spin" />
                    <ShieldCheck v-else class="w-3.5 h-3.5" />
                    <span>{{ changingPassword ? 'Updating...' : (mustChangePassword ? 'Set New Password' : 'Update Password') }}</span>
                  </button>
                </div>
              </form>
            </div>

          </div>
        </div>
      </template>
    </div>
  </DashboardLayout>
</template>

<script setup lang="ts">
import { ref, computed, onMounted } from 'vue'
import DashboardLayout from '@/Layouts/DashboardLayout.vue'
import {
  Briefcase, Mail, Phone, Save, Lock, Eye, EyeOff,
  Camera, CheckCircle, XCircle, AlertTriangle, Loader2,
  ShieldCheck, Clock, MapPin
} from 'lucide-vue-next'
import api from '@/api/auth'
import { useAuthStore } from '@/stores/auth'

const authStore = useAuthStore()

// ─── State ───────────────────────────────────────
const loading = ref(true)
const saving = ref(false)
const changingPassword = ref(false)
const uploadingPhoto = ref(false)
const mustChangePassword = ref(false)

const user = ref<any>(null)
const receptionistProfile = ref<any>(null)
const profilePhotoUrl = ref<string | null>(null)
const photoPreview = ref<string | null>(null)
const photoInput = ref<HTMLInputElement | null>(null)

const form = ref({ first_name: '', last_name: '', phone: '' })
const passwordForm = ref({ current_password: '', new_password: '', new_password_confirmation: '' })

const showCurrentPw = ref(false)
const showNewPw = ref(false)
const showConfirmPw = ref(false)

const profileError = ref('')
const passwordError = ref('')
const globalMessage = ref<{ text: string; type: 'success' | 'error' }>({ text: '', type: 'success' })

// ─── Password strength ───────────────────────────
const passwordStrength = computed(() => {
  const pw = passwordForm.value.new_password
  if (!pw) return 0
  let score = 0
  if (pw.length >= 8) score++
  if (/[A-Z]/.test(pw)) score++
  if (/[0-9]/.test(pw)) score++
  if (/[^A-Za-z0-9]/.test(pw)) score++
  return score
})
const strengthColor = computed(() => {
  const s = passwordStrength.value
  if (s <= 1) return 'bg-red-500'
  if (s === 2) return 'bg-amber-500'
  if (s === 3) return 'bg-teal-500'
  return 'bg-emerald-500'
})
const strengthTextColor = computed(() => {
  const s = passwordStrength.value
  if (s <= 1) return 'text-red-500'
  if (s === 2) return 'text-amber-500'
  if (s === 3) return 'text-teal-500'
  return 'text-emerald-500'
})
const strengthLabel = computed(() => {
  const s = passwordStrength.value
  if (s <= 1) return 'Weak – add uppercase, numbers, and symbols'
  if (s === 2) return 'Fair – getting better'
  if (s === 3) return 'Good – almost there'
  return 'Strong password'
})

// ─── Helpers ─────────────────────────────────────
function showGlobalMessage(text: string, type: 'success' | 'error') {
  globalMessage.value = { text, type }
  setTimeout(() => { globalMessage.value = { text: '', type: 'success' } }, 4500)
}

function getPhotoUrl(path: string | null): string | null {
  if (!path) return null
  if (path.startsWith('http')) return path
  return `http://127.0.0.1:8000/storage/${path}`
}

// ─── Load profile ─────────────────────────────────
async function loadProfile() {
  loading.value = true
  try {
    const res = await api.get('/receptionist/profile')
    if (res.data?.success) {
      const data = res.data.data
      user.value = data
      receptionistProfile.value = data.receptionist
      form.value.first_name = data.first_name || ''
      form.value.last_name = data.last_name || ''
      form.value.phone = data.phone || ''
      if (data.receptionist?.profile_photo) {
        profilePhotoUrl.value = getPhotoUrl(data.receptionist.profile_photo)
      }
      // Check temp password flag set during login/activation
      if (localStorage.getItem('receptionist_must_change_password') === 'true') {
        mustChangePassword.value = true
      }
    }
  } catch (err: any) {
    console.error('[Profile] Failed to load:', err)
    // Fallback to auth store data
    if (authStore.user) {
      user.value = { ...authStore.user }
      form.value.first_name = authStore.user.first_name || ''
      form.value.last_name = authStore.user.last_name || ''
      form.value.phone = authStore.user.phone || ''
    }
  } finally {
    loading.value = false
  }
}

// ─── Save profile ─────────────────────────────────
async function saveProfile() {
  profileError.value = ''
  saving.value = true
  try {
    const res = await api.put('/receptionist/profile', {
      first_name: form.value.first_name,
      last_name: form.value.last_name,
      phone: form.value.phone,
    })
    if (res.data?.success) {
      if (user.value) {
        user.value.first_name = form.value.first_name
        user.value.last_name = form.value.last_name
        user.value.phone = form.value.phone
      }
      await authStore.fetchCurrentUser()
      showGlobalMessage('Profile updated successfully!', 'success')
    } else {
      profileError.value = res.data?.message || 'Update failed.'
    }
  } catch (err: any) {
    profileError.value = err.response?.data?.message || 'Failed to update profile. Please try again.'
  } finally {
    saving.value = false
  }
}

// ─── Change password ──────────────────────────────
async function changePassword() {
  passwordError.value = ''
  if (passwordForm.value.new_password !== passwordForm.value.new_password_confirmation) {
    passwordError.value = 'New passwords do not match.'
    return
  }
  if (passwordForm.value.new_password.length < 8) {
    passwordError.value = 'New password must be at least 8 characters.'
    return
  }
  changingPassword.value = true
  try {
    const res = await api.post('/receptionist/profile/change-password', {
      current_password: passwordForm.value.current_password,
      new_password: passwordForm.value.new_password,
      new_password_confirmation: passwordForm.value.new_password_confirmation,
    })
    if (res.data?.success) {
      localStorage.removeItem('receptionist_must_change_password')
      mustChangePassword.value = false
      passwordForm.value = { current_password: '', new_password: '', new_password_confirmation: '' }
      showGlobalMessage('Password changed successfully! Your account is now secured.', 'success')
    } else {
      passwordError.value = res.data?.message || 'Password change failed.'
    }
  } catch (err: any) {
    const errData = err.response?.data
    if (err.response?.status === 422 && errData?.errors) {
      const msgs = Object.values(errData.errors).flat() as string[]
      passwordError.value = msgs[0] || 'Validation failed.'
    } else {
      passwordError.value = errData?.message || 'Failed to change password. Please check your current/temporary password and try again.'
    }
  } finally {
    changingPassword.value = false
  }
}

// ─── Photo upload ─────────────────────────────────
function triggerPhotoUpload() {
  photoInput.value?.click()
}

async function onPhotoSelected(event: Event) {
  const input = event.target as HTMLInputElement
  const file = input.files?.[0]
  if (!file) return

  const allowed = ['image/jpeg', 'image/png', 'image/jpg', 'image/gif']
  if (!allowed.includes(file.type)) {
    showGlobalMessage('Invalid file type. Please upload a JPEG, PNG or GIF.', 'error')
    return
  }
  if (file.size > 2 * 1024 * 1024) {
    showGlobalMessage('Photo too large. Maximum size is 2MB.', 'error')
    return
  }

  // Show local preview immediately
  const reader = new FileReader()
  reader.onload = (e) => { photoPreview.value = e.target?.result as string }
  reader.readAsDataURL(file)

  uploadingPhoto.value = true
  try {
    const formData = new FormData()
    formData.append('photo', file)
    const res = await api.post('/receptionist/profile/photo', formData)
    if (res.data?.success) {
      const newPath = res.data.data?.photo_url || res.data.data?.profile_photo
      profilePhotoUrl.value = newPath ? getPhotoUrl(newPath) : photoPreview.value
      showGlobalMessage('Profile photo updated successfully!', 'success')
    } else {
      showGlobalMessage(res.data?.message || 'Photo upload failed.', 'error')
      photoPreview.value = null
    }
  } catch (err: any) {
    showGlobalMessage(err.response?.data?.message || 'Failed to upload photo. Please try again.', 'error')
    photoPreview.value = null
  } finally {
    uploadingPhoto.value = false
    if (photoInput.value) photoInput.value.value = ''
  }
}

// ─── Init ─────────────────────────────────────────
onMounted(() => { loadProfile() })
</script>

<style scoped>
.fade-enter-active,
.fade-leave-active {
  transition: opacity 0.3s ease, transform 0.3s ease;
}
.fade-enter-from,
.fade-leave-to {
  opacity: 0;
  transform: translateY(-6px);
}
</style>
