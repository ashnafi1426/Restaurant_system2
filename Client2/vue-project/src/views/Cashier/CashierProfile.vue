<template>
  <DashboardLayout>
    <div
      class="space-y-6 bg-slate-50 dark:bg-slate-950 min-h-screen p-4 sm:p-6 max-w-full font-sans"
    >
      <div v-if="loading" class="flex items-center justify-center py-24">
        <div class="flex flex-col items-center gap-3">
          <div
            class="w-10 h-10 border-4 border-blue-500 border-t-transparent rounded-full animate-spin"
          ></div>
          <p class="text-sm text-slate-500 dark:text-slate-400 font-semibold">
            {{ languageStore.t('loading', 'Loading profile...') }}
          </p>
        </div>
      </div>

      <template v-else>
        <transition name="fade-slide">
          <div
            v-if="alert.show"
            :class="[
              'flex items-start gap-3 px-4 py-3 rounded-2xl text-sm font-semibold border',
              alert.type === 'success'
                ? 'bg-emerald-50 dark:bg-emerald-900/20 border-emerald-300 dark:border-emerald-700 text-emerald-700 dark:text-emerald-300'
                : 'bg-red-50 dark:bg-red-900/20 border-red-300 dark:border-red-700 text-red-700 dark:text-red-300',
            ]"
          >
            <component
              :is="alert.type === 'success' ? CheckCircle : AlertCircle"
              class="w-5 h-5 mt-0.5 flex-shrink-0"
            />
            <span>{{ alert.message }}</span>
            <button
              @click="alert.show = false"
              class="ml-auto opacity-60 hover:opacity-100 transition cursor-pointer"
            >
              <X class="w-4 h-4" />
            </button>
          </div>
        </transition>

        <div
          class="bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 p-6 rounded-3xl shadow-xs flex flex-col md:flex-row items-center gap-6"
        >
          <div class="flex flex-col md:flex-row items-center gap-5 text-center md:text-left">
            <div
              class="relative flex-shrink-0 group cursor-pointer"
              @click="triggerPhotoUpload"
              :title="languageStore.t('click_avatar_upload', 'Click to change photo')"
            >
              <div
                class="w-24 h-24 rounded-full overflow-hidden border-4 border-blue-500/30 shadow-md bg-blue-500/10 flex items-center justify-center"
              >
                <img
                  v-if="photoPreview || profileData.cashier?.profile_photo"
                  :src="photoPreview || getPhotoUrl(profileData.cashier?.profile_photo)"
                  alt="Profile"
                  class="w-full h-full object-cover"
                />
                <span v-else class="text-blue-600 dark:text-blue-400 font-black text-2xl">
                  {{ (profileData.first_name || 'C')?.[0]?.toUpperCase() }}
                </span>
              </div>

              <div
                class="absolute inset-0 rounded-full bg-black/50 flex items-center justify-center opacity-0 group-hover:opacity-100 transition-opacity"
              >
                <Camera class="w-6 h-6 text-white" />
              </div>

              <div
                v-if="uploadingPhoto"
                class="absolute inset-0 rounded-full bg-black/60 flex items-center justify-center"
              >
                <div
                  class="w-6 h-6 border-2 border-white border-t-transparent rounded-full animate-spin"
                ></div>
              </div>
            </div>

            <input
              ref="photoInput"
              type="file"
              accept="image/jpeg,image/png,image/jpg,image/gif"
              class="hidden"
              @change="onPhotoSelected"
            />

            <div>
              <div class="flex flex-wrap items-center justify-center md:justify-start gap-2.5">
                <h1 class="text-2xl font-black text-slate-900 dark:text-white tracking-tight">
                  {{ profileData.first_name }} {{ profileData.last_name }}
                </h1>
                <span
                  class="px-2.5 py-0.5 rounded-full text-[10px] font-black uppercase tracking-wider bg-emerald-500/10 text-emerald-600 dark:text-emerald-400 border border-emerald-500/20"
                >
                  {{ profileData.cashier?.status || 'Active' }}
                  {{ languageStore.t('cashier', 'Cashier') }}
                </span>
              </div>

              <div
                class="flex flex-wrap items-center justify-center md:justify-start gap-2 mt-1.5 text-xs text-slate-500 dark:text-slate-400 font-medium"
              >
                <span class="font-mono font-bold text-blue-600 dark:text-blue-400">{{
                  languageStore.t('payments_billing', 'POS & Billing')
                }}</span>
                <span>•</span>
                <span>{{ profileData.email }}</span>
              </div>
              <p class="text-[11px] text-slate-400 mt-1">
                {{
                  languageStore.t(
                    'click_avatar_upload',
                    'Click your avatar to upload a profile photo',
                  )
                }}
              </p>
            </div>
          </div>
        </div>

        <div class="grid grid-cols-1 lg:grid-cols-4 gap-6">
          <div class="lg:col-span-1 space-y-6">
            <div
              class="bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-3xl p-5 shadow-xs space-y-4"
            >
              <h3 class="text-xs font-black text-slate-400 uppercase tracking-wider">
                {{ languageStore.t('quick_info', 'Quick Info') }}
              </h3>
              <div class="space-y-3 text-xs">
                <div class="flex items-start gap-3">
                  <Briefcase class="w-4 h-4 text-blue-500 mt-0.5 flex-shrink-0" />
                  <div>
                    <p class="text-[10px] font-bold text-slate-400 uppercase">
                      {{ languageStore.t('role', 'Role') }}
                    </p>
                    <p class="font-extrabold text-slate-900 dark:text-white capitalize">
                      {{ profileData.role || languageStore.t('cashier', 'Cashier') }}
                    </p>
                  </div>
                </div>

                <div class="flex items-start gap-3">
                  <Mail class="w-4 h-4 text-emerald-500 mt-0.5 flex-shrink-0" />
                  <div class="min-w-0 flex-1">
                    <p class="text-[10px] font-bold text-slate-400 uppercase">
                      {{ languageStore.t('email', 'Email') }}
                    </p>
                    <p class="font-extrabold text-slate-900 dark:text-white truncate">
                      {{ profileData.email }}
                    </p>
                  </div>
                </div>

                <div class="flex items-start gap-3">
                  <Phone class="w-4 h-4 text-purple-500 mt-0.5 flex-shrink-0" />
                  <div>
                    <p class="text-[10px] font-bold text-slate-400 uppercase">
                      {{ languageStore.t('phone', 'Phone') }}
                    </p>
                    <p class="font-extrabold text-slate-900 dark:text-white">
                      {{ profileData.phone || languageStore.t('not_provided', 'Not provided') }}
                    </p>
                  </div>
                </div>

                <div v-if="profileData.cashier?.shift" class="flex items-start gap-3">
                  <Clock class="w-4 h-4 text-amber-500 mt-0.5 flex-shrink-0" />
                  <div>
                    <p class="text-[10px] font-bold text-slate-400 uppercase">
                      {{ languageStore.t('shift', 'Shift') }}
                    </p>
                    <p class="font-extrabold text-slate-900 dark:text-white capitalize">
                      {{ profileData.cashier.shift }}
                    </p>
                  </div>
                </div>

                <div v-if="profileData.cashier?.register_number" class="flex items-start gap-3">
                  <Hash class="w-4 h-4 text-indigo-500 mt-0.5 flex-shrink-0" />
                  <div>
                    <p class="text-[10px] font-bold text-slate-400 uppercase">
                      {{ languageStore.t('desk', 'Register #') }}
                    </p>
                    <p class="font-extrabold text-slate-900 dark:text-white">
                      {{ profileData.cashier.register_number }}
                    </p>
                  </div>
                </div>
              </div>
            </div>
          </div>
          <div class="lg:col-span-3 space-y-6">
            <div
              class="bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-3xl p-6 shadow-xs space-y-6"
            >
              <div>
                <h3 class="text-lg font-black text-slate-900 dark:text-white">
                  {{ languageStore.t('personal_info', 'Personal Information') }}
                </h3>
                <p class="text-xs text-slate-500 dark:text-slate-400 mt-0.5">
                  {{
                    languageStore.t('personal_info_desc', 'Update your cashier account details.')
                  }}
                </p>
              </div>

              <form @submit.prevent="saveProfile" class="space-y-4 text-xs font-sans">
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                  <div>
                    <label
                      class="block text-[10px] font-extrabold uppercase text-slate-500 dark:text-slate-400 mb-1"
                      >{{ languageStore.t('first_name', 'First Name') }} *</label
                    >
                    <input
                      v-model="form.first_name"
                      type="text"
                      required
                      class="w-full px-3.5 py-2.5 rounded-xl bg-slate-50 dark:bg-slate-950 border border-slate-200 dark:border-slate-800 text-slate-900 dark:text-white font-bold focus:outline-none focus:border-blue-500 transition"
                    />
                  </div>

                  <div>
                    <label
                      class="block text-[10px] font-extrabold uppercase text-slate-500 dark:text-slate-400 mb-1"
                      >{{ languageStore.t('last_name', 'Last Name') }} *</label
                    >
                    <input
                      v-model="form.last_name"
                      type="text"
                      required
                      class="w-full px-3.5 py-2.5 rounded-xl bg-slate-50 dark:bg-slate-950 border border-slate-200 dark:border-slate-800 text-slate-900 dark:text-white font-bold focus:outline-none focus:border-blue-500 transition"
                    />
                  </div>
                </div>

                <div>
                  <label
                    class="block text-[10px] font-extrabold uppercase text-slate-500 dark:text-slate-400 mb-1"
                    >{{ languageStore.t('email', 'Email Address') }}</label
                  >
                  <input
                    v-model="form.email"
                    type="email"
                    disabled
                    class="w-full px-3.5 py-2.5 rounded-xl bg-slate-100 dark:bg-slate-800 border border-slate-200 dark:border-slate-700 text-slate-400 dark:text-slate-500 font-bold cursor-not-allowed"
                  />
                  <p class="text-[10px] text-slate-400 mt-1">
                    {{
                      languageStore.t(
                        'email_cant_change',
                        'Email cannot be changed. Contact your administrator.',
                      )
                    }}
                  </p>
                </div>

                <div>
                  <label
                    class="block text-[10px] font-extrabold uppercase text-slate-500 dark:text-slate-400 mb-1"
                    >{{ languageStore.t('phone', 'Phone Number') }}</label
                  >
                  <input
                    v-model="form.phone"
                    type="tel"
                    placeholder="+251 XXX XXX XXX"
                    class="w-full px-3.5 py-2.5 rounded-xl bg-slate-50 dark:bg-slate-950 border border-slate-200 dark:border-slate-800 text-slate-900 dark:text-white font-bold focus:outline-none focus:border-blue-500 transition"
                  />
                </div>

                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                  <div>
                    <label
                      class="block text-[10px] font-extrabold uppercase text-slate-500 dark:text-slate-400 mb-1"
                      >{{ languageStore.t('shift', 'Shift') }}</label
                    >
                    <select
                      v-model="form.shift"
                      class="w-full px-3.5 py-2.5 rounded-xl bg-slate-50 dark:bg-slate-950 border border-slate-200 dark:border-slate-800 text-slate-900 dark:text-white font-bold focus:outline-none focus:border-blue-500 transition"
                    >
                      <option value="">Select shift</option>
                      <option value="morning">Morning</option>
                      <option value="afternoon">Afternoon</option>
                      <option value="evening">Evening</option>
                      <option value="night">Night</option>
                    </select>
                  </div>
                  <div>
                    <label
                      class="block text-[10px] font-extrabold uppercase text-slate-500 dark:text-slate-400 mb-1"
                      >Register Number</label
                    >
                    <input
                      v-model="form.register_number"
                      type="text"
                      placeholder="e.g., REG-001"
                      class="w-full px-3.5 py-2.5 rounded-xl bg-slate-50 dark:bg-slate-950 border border-slate-200 dark:border-slate-800 text-slate-900 dark:text-white font-bold focus:outline-none focus:border-blue-500 transition"
                    />
                  </div>
                </div>

                <div>
                  <label
                    class="block text-[10px] font-extrabold uppercase text-slate-500 dark:text-slate-400 mb-1"
                    >Bio</label
                  >
                  <textarea
                    v-model="form.bio"
                    rows="3"
                    placeholder="A short bio about yourself..."
                    class="w-full px-3.5 py-2.5 rounded-xl bg-slate-50 dark:bg-slate-950 border border-slate-200 dark:border-slate-800 text-slate-900 dark:text-white font-bold focus:outline-none focus:border-blue-500 transition resize-none"
                  ></textarea>
                </div>

                <div class="flex justify-end pt-4 border-t border-slate-100 dark:border-slate-800">
                  <button
                    type="submit"
                    :disabled="saving"
                    class="px-5 py-2.5 rounded-xl bg-blue-600 hover:bg-blue-700 text-white font-black text-xs shadow-md shadow-blue-600/20 transition flex items-center gap-1.5 disabled:opacity-50 cursor-pointer"
                  >
                    <div
                      v-if="saving"
                      class="w-3.5 h-3.5 border-2 border-white border-t-transparent rounded-full animate-spin"
                    ></div>
                    <Save v-else class="w-3.5 h-3.5" />
                    <span>{{
                      saving
                        ? languageStore.t('saving', 'Saving...')
                        : languageStore.t('save_changes', 'Save Changes')
                    }}</span>
                  </button>
                </div>
              </form>
            </div>

            <div
              class="bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-3xl p-6 shadow-xs space-y-6"
            >
              <div class="flex items-center gap-3">
                <div class="w-9 h-9 rounded-xl bg-amber-500/10 flex items-center justify-center">
                  <Lock class="w-4 h-4 text-amber-600 dark:text-amber-400" />
                </div>
                <div>
                  <h3 class="text-lg font-black text-slate-900 dark:text-white">
                    {{ languageStore.t('change_password', 'Change Password') }}
                  </h3>
                  <p class="text-xs text-slate-500 dark:text-slate-400 mt-0.5">
                    {{
                      languageStore.t(
                        'change_password_desc',
                        'Change your temporary or current password here.',
                      )
                    }}
                  </p>
                </div>
              </div>

              <form @submit.prevent="changePassword" class="space-y-4 text-xs font-sans">
                <div>
                  <label
                    class="block text-[10px] font-extrabold uppercase text-slate-500 dark:text-slate-400 mb-1"
                  >
                    {{ languageStore.t('current_password', 'Current Password') }} *
                  </label>
                  <div class="relative">
                    <input
                      v-model="pwForm.current_password"
                      :type="showCurrentPw ? 'text' : 'password'"
                      required
                      :placeholder="
                        languageStore.t('enter_current_password', 'Enter your current password')
                      "
                      class="w-full px-3.5 py-2.5 pr-10 rounded-xl bg-slate-50 dark:bg-slate-950 border border-slate-200 dark:border-slate-800 text-slate-900 dark:text-white font-bold focus:outline-none focus:border-amber-500 transition"
                    />
                    <button
                      type="button"
                      @click="showCurrentPw = !showCurrentPw"
                      class="absolute right-3 top-1/2 -translate-y-1/2 text-slate-400 hover:text-slate-600 transition cursor-pointer"
                    >
                      <Eye v-if="!showCurrentPw" class="w-4 h-4" />
                      <EyeOff v-else class="w-4 h-4" />
                    </button>
                  </div>
                </div>

                <div>
                  <label
                    class="block text-[10px] font-extrabold uppercase text-slate-500 dark:text-slate-400 mb-1"
                    >{{ languageStore.t('new_password', 'New Password') }} *</label
                  >
                  <div class="relative">
                    <input
                      v-model="pwForm.new_password"
                      :type="showNewPw ? 'text' : 'password'"
                      required
                      minlength="8"
                      :placeholder="languageStore.t('min_8_chars', 'Minimum 8 characters')"
                      class="w-full px-3.5 py-2.5 pr-10 rounded-xl bg-slate-50 dark:bg-slate-950 border border-slate-200 dark:border-slate-800 text-slate-900 dark:text-white font-bold focus:outline-none focus:border-amber-500 transition"
                    />
                    <button
                      type="button"
                      @click="showNewPw = !showNewPw"
                      class="absolute right-3 top-1/2 -translate-y-1/2 text-slate-400 hover:text-slate-600 transition cursor-pointer"
                    >
                      <Eye v-if="!showNewPw" class="w-4 h-4" />
                      <EyeOff v-else class="w-4 h-4" />
                    </button>
                  </div>

                  <div v-if="pwForm.new_password" class="mt-2 space-y-1">
                    <div class="flex gap-1">
                      <div
                        v-for="i in 4"
                        :key="i"
                        class="h-1 flex-1 rounded-full transition-all"
                        :class="
                          passwordStrength >= i ? strengthColor : 'bg-slate-200 dark:bg-slate-700'
                        "
                      ></div>
                    </div>
                    <p class="text-[10px] font-bold" :class="strengthTextColor">
                      {{ strengthLabel }}
                    </p>
                  </div>
                </div>

                <div>
                  <label
                    class="block text-[10px] font-extrabold uppercase text-slate-500 dark:text-slate-400 mb-1"
                    >{{ languageStore.t('confirm_new_password', 'Confirm New Password') }} *</label
                  >
                  <div class="relative">
                    <input
                      v-model="pwForm.new_password_confirmation"
                      :type="showConfirmPw ? 'text' : 'password'"
                      required
                      :placeholder="
                        languageStore.t('reenter_new_password', 'Re-enter new password')
                      "
                      :class="[
                        'w-full px-3.5 py-2.5 pr-10 rounded-xl bg-slate-50 dark:bg-slate-950 border text-slate-900 dark:text-white font-bold focus:outline-none transition',
                        pwForm.new_password_confirmation &&
                        pwForm.new_password !== pwForm.new_password_confirmation
                          ? 'border-red-400 focus:border-red-400'
                          : 'border-slate-200 dark:border-slate-800 focus:border-amber-500',
                      ]"
                    />
                    <button
                      type="button"
                      @click="showConfirmPw = !showConfirmPw"
                      class="absolute right-3 top-1/2 -translate-y-1/2 text-slate-400 hover:text-slate-600 transition cursor-pointer"
                    >
                      <Eye v-if="!showConfirmPw" class="w-4 h-4" />
                      <EyeOff v-else class="w-4 h-4" />
                    </button>
                  </div>
                  <p
                    v-if="
                      pwForm.new_password_confirmation &&
                      pwForm.new_password !== pwForm.new_password_confirmation
                    "
                    class="text-[10px] text-red-500 font-bold mt-1"
                  >
                    {{ languageStore.t('passwords_not_match', 'Passwords do not match') }}
                  </p>
                </div>

                <div class="flex justify-end pt-4 border-t border-slate-100 dark:border-slate-800">
                  <button
                    type="submit"
                    :disabled="
                      changingPw ||
                      !!(
                        pwForm.new_password_confirmation &&
                        pwForm.new_password !== pwForm.new_password_confirmation
                      )
                    "
                    class="px-5 py-2.5 rounded-xl bg-amber-500 hover:bg-amber-600 text-white font-black text-xs shadow-md shadow-amber-500/20 transition flex items-center gap-1.5 disabled:opacity-50 cursor-pointer"
                  >
                    <div
                      v-if="changingPw"
                      class="w-3.5 h-3.5 border-2 border-white border-t-transparent rounded-full animate-spin"
                    ></div>
                    <Lock v-else class="w-3.5 h-3.5" />
                    <span>{{
                      changingPw
                        ? languageStore.t('updating', 'Changing...')
                        : languageStore.t('update_password', 'Change Password')
                    }}</span>
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
  Briefcase,
  Mail,
  Phone,
  Save,
  Lock,
  Eye,
  EyeOff,
  Camera,
  Clock,
  ShieldCheck,
  CheckCircle,
  AlertCircle,
  X,
  Hash,
} from 'lucide-vue-next'
import api from '@/api/auth'
import { useLanguageStore } from '@/stores/language'

const languageStore = useLanguageStore()

const loading = ref(true)
const saving = ref(false)
const changingPw = ref(false)
const uploadingPhoto = ref(false)

const photoInput = ref<HTMLInputElement | null>(null)
const photoPreview = ref<string | null>(null)

const profileData = ref<any>({
  first_name: '',
  last_name: '',
  email: '',
  phone: '',
  role: '',
  cashier: null,
})

const form = ref({
  first_name: '',
  last_name: '',
  email: '',
  phone: '',
  shift: '',
  register_number: '',
  bio: '',
})

const pwForm = ref({
  current_password: '',
  new_password: '',
  new_password_confirmation: '',
})

const showCurrentPw = ref(false)
const showNewPw = ref(false)
const showConfirmPw = ref(false)

const alert = ref({ show: false, type: 'success' as 'success' | 'error', message: '' })

const showAlert = (type: 'success' | 'error', message: string) => {
  alert.value = { show: true, type, message }
  setTimeout(() => {
    alert.value.show = false
  }, 5000)
}

const getPhotoUrl = (path: string | null | undefined) => {
  if (!path) return null
  if (path.startsWith('http')) return path
  return `http://127.0.0.1:8000/storage/${path}`
}

const populateForm = (data: any) => {
  form.value.first_name = data.first_name || ''
  form.value.last_name = data.last_name || ''
  form.value.email = data.email || ''
  form.value.phone = data.phone || ''
  form.value.shift = data.cashier?.shift || ''
  form.value.register_number = data.cashier?.register_number || ''
  form.value.bio = data.cashier?.bio || ''
}

// ── Password Strength ─────────────────────────────────────────────────────────
const passwordStrength = computed(() => {
  const pw = pwForm.value.new_password
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
  if (s === 1) return 'bg-red-500'
  if (s === 2) return 'bg-amber-400'
  if (s === 3) return 'bg-blue-400'
  return 'bg-emerald-500'
})

const strengthTextColor = computed(() => {
  const s = passwordStrength.value
  if (s === 1) return 'text-red-500'
  if (s === 2) return 'text-amber-500'
  if (s === 3) return 'text-blue-500'
  return 'text-emerald-500'
})

const strengthLabel = computed(() => {
  const s = passwordStrength.value
  if (s === 1) return languageStore.t('weak', 'Weak')
  if (s === 2) return languageStore.t('fair', 'Fair')
  if (s === 3) return languageStore.t('good', 'Good')
  return languageStore.t('strong', 'Strong')
})

// ── API Calls ─────────────────────────────────────────────────────────────────
const fetchProfile = async () => {
  loading.value = true
  try {
    const res = await api.get('/cashier/profile')
    if (res.data?.success) {
      profileData.value = res.data.data
      populateForm(res.data.data)
    } else {
      showAlert('error', res.data?.message || 'Failed to load profile.')
    }
  } catch (err: any) {
    console.error('[CASHIER PROFILE] Error fetching profile:', err)
    showAlert('error', err.response?.data?.message || 'Failed to load profile data.')
  } finally {
    loading.value = false
  }
}

const saveProfile = async () => {
  saving.value = true
  try {
    const res = await api.put('/cashier/profile', {
      first_name: form.value.first_name,
      last_name: form.value.last_name,
      phone: form.value.phone,
      shift: form.value.shift,
      register_number: form.value.register_number,
      bio: form.value.bio,
    })
    if (res.data?.success) {
      profileData.value = {
        ...profileData.value,
        first_name: res.data.data.first_name,
        last_name: res.data.data.last_name,
        phone: res.data.data.phone,
        cashier: res.data.data.cashier
          ? { ...profileData.value.cashier, ...res.data.data.cashier }
          : profileData.value.cashier,
      }
      showAlert('success', 'Profile updated successfully!')
    } else {
      showAlert('error', res.data?.message || 'Failed to update profile.')
    }
  } catch (err: any) {
    console.error('[CASHIER PROFILE] Error updating profile:', err)
    const errors = err.response?.data?.errors
    if (errors) {
      const first = Object.values(errors)[0] as string[]
      showAlert('error', first[0])
    } else {
      showAlert('error', err.response?.data?.message || 'Failed to update profile.')
    }
  } finally {
    saving.value = false
  }
}

const changePassword = async () => {
  if (pwForm.value.new_password !== pwForm.value.new_password_confirmation) {
    showAlert('error', 'New passwords do not match.')
    return
  }
  if (pwForm.value.new_password.length < 8) {
    showAlert('error', 'New password must be at least 8 characters.')
    return
  }
  changingPw.value = true
  try {
    const res = await api.post('/cashier/profile/change-password', {
      current_password: pwForm.value.current_password,
      new_password: pwForm.value.new_password,
      new_password_confirmation: pwForm.value.new_password_confirmation,
    })
    if (res.data?.success) {
      pwForm.value = { current_password: '', new_password: '', new_password_confirmation: '' }
      showAlert('success', 'Password changed successfully! Your account is now secured.')
    } else {
      showAlert('error', res.data?.message || 'Failed to change password.')
    }
  } catch (err: any) {
    console.error('[CASHIER PROFILE] Error changing password:', err)
    const errors = err.response?.data?.errors
    if (errors) {
      const first = Object.values(errors)[0] as string[]
      showAlert('error', first[0])
    } else {
      showAlert(
        'error',
        err.response?.data?.message || 'Incorrect current password or server error.',
      )
    }
  } finally {
    changingPw.value = false
  }
}

const triggerPhotoUpload = () => {
  photoInput.value?.click()
}

const onPhotoSelected = async (event: Event) => {
  const input = event.target as HTMLInputElement
  const file = input.files?.[0]
  if (!file) return

  const validTypes = ['image/jpeg', 'image/png', 'image/jpg', 'image/gif']
  if (!validTypes.includes(file.type)) {
    showAlert('error', 'Please select a valid image file (JPEG, PNG, JPG, or GIF)')
    if (photoInput.value) photoInput.value.value = ''
    return
  }

  if (file.size > 2048 * 1024) {
    showAlert('error', 'Image file size must be less than 2MB')
    if (photoInput.value) photoInput.value.value = ''
    return
  }

  const reader = new FileReader()
  reader.onload = (e) => {
    photoPreview.value = e.target?.result as string
  }
  reader.readAsDataURL(file)

  uploadingPhoto.value = true
  try {
    const formData = new FormData()
    formData.append('photo', file)

    const res = await api.post('/cashier/profile/photo', formData)

    if (res.data?.success) {
      if (profileData.value.cashier) {
        profileData.value.cashier.profile_photo = res.data.data?.profile_photo
      }
      showAlert('success', 'Profile photo updated successfully!')
    } else {
      photoPreview.value = null
      showAlert('error', res.data?.message || 'Failed to upload photo.')
    }
  } catch (err: any) {
    console.error('[CASHIER PROFILE] Photo upload error:', err)
    photoPreview.value = null
    showAlert('error', err.response?.data?.message || 'Failed to upload photo. Please try again.')
  } finally {
    uploadingPhoto.value = false
    if (photoInput.value) photoInput.value.value = ''
  }
}

// ── Lifecycle ─────────────────────────────────────────────────────────────────
onMounted(fetchProfile)
</script>

<style scoped>
.fade-slide-enter-active,
.fade-slide-leave-active {
  transition: all 0.3s ease;
}
.fade-slide-enter-from,
.fade-slide-leave-to {
  opacity: 0;
  transform: translateY(-8px);
}
</style>
