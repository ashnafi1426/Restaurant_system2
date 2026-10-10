<template>
  <DashboardLayout>
    <div
      class="space-y-6 bg-slate-50 dark:bg-slate-950 min-h-screen p-4 sm:p-6 max-w-full font-sans"
    >
      <!-- Profile Header Banner -->
      <div
        class="bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 p-6 rounded-3xl shadow-xs flex flex-col md:flex-row items-center justify-between gap-6"
      >
        <div class="flex flex-col md:flex-row items-center gap-5 text-center md:text-left">
          <!-- Profile Avatar -->
          <div class="relative flex-shrink-0">
            <img
              :src="profilePhotoUrl"
              alt="Profile"
              class="w-24 h-24 rounded-full object-cover border-4 border-amber-500/30 shadow-md"
            />
            <label
              for="photo-upload"
              class="absolute bottom-0 right-0 bg-blue-600 hover:bg-blue-700 text-white p-2 rounded-full cursor-pointer transition shadow-md"
              :title="languageStore.t('upload_profile_photo', 'Upload Profile Photo')"
            >
              <Camera class="w-3.5 h-3.5" />
            </label>
            <input
              id="photo-upload"
              type="file"
              accept="image/*"
              class="hidden"
              @change="handlePhotoUpload"
            />
          </div>

          <!-- User Header Details -->
          <div>
            <div class="flex flex-wrap items-center justify-center md:justify-start gap-2.5">
              <h1 class="text-2xl font-black text-slate-900 dark:text-white tracking-tight">
                {{ profile?.full_name || languageStore.t('system_admin', 'System Admin') }}
              </h1>
              <span
                class="px-2.5 py-0.5 rounded-full text-[10px] font-black uppercase tracking-wider bg-emerald-500/10 text-emerald-600 dark:text-emerald-400 border border-emerald-500/20"
              >
                {{ profile?.administrator?.status || languageStore.t('active', 'Active') }}
              </span>
            </div>

            <div
              class="flex flex-wrap items-center justify-center md:justify-start gap-2 mt-1.5 text-xs text-slate-500 dark:text-slate-400 font-medium"
            >
              <span class="font-mono font-bold text-amber-600 dark:text-amber-400">{{
                profile?.administrator?.employee_code || 'ADM971'
              }}</span>
              <span>•</span>
              <span>{{
                profile?.administrator?.department ||
                languageStore.t('administration', 'Administration')
              }}</span>
              <span>•</span>
              <span>{{ profile?.email }}</span>
            </div>
          </div>
        </div>

        <button
          @click="loadProfile"
          :disabled="loading"
          class="px-4 py-2 rounded-xl bg-slate-100 dark:bg-slate-800 hover:bg-slate-200 dark:hover:bg-slate-700 text-slate-800 dark:text-slate-200 text-xs font-extrabold transition flex items-center gap-1.5 cursor-pointer border border-slate-200 dark:border-slate-700 disabled:opacity-50"
        >
          <RefreshCw :class="['w-3.5 h-3.5', loading && 'animate-spin']" />
          <span>{{ languageStore.t('refresh_profile', 'Refresh Profile') }}</span>
        </button>
      </div>

      <div class="grid grid-cols-1 lg:grid-cols-4 gap-6">
        <!-- Sidebar Navigation & Quick Info -->
        <div class="lg:col-span-1 space-y-6">
          <!-- Quick Info Card -->
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
                    {{ languageStore.t('department', 'Department') }}
                  </p>
                  <p class="font-extrabold text-slate-900 dark:text-white">
                    {{
                      profile?.administrator?.department ||
                      languageStore.t('administration', 'Administration')
                    }}
                  </p>
                </div>
              </div>

              <div class="flex items-start gap-3">
                <Calendar class="w-4 h-4 text-amber-500 mt-0.5 flex-shrink-0" />
                <div>
                  <p class="text-[10px] font-bold text-slate-400 uppercase">
                    {{ languageStore.t('hire_date', 'Hire Date') }}
                  </p>
                  <p class="font-extrabold text-slate-900 dark:text-white">
                    {{ formatDate(profile?.administrator?.hire_date) }}
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
                    {{ profile?.email }}
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
                    {{ profile?.phone || languageStore.t('not_provided', 'Not provided') }}
                  </p>
                </div>
              </div>
            </div>
          </div>

          <!-- Navigation Tabs Card -->
          <div
            class="bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-3xl p-2 shadow-xs space-y-1"
          >
            <button
              v-for="tab in localizedTabs"
              :key="tab.id"
              @click="activeTab = tab.id"
              :class="[
                'w-full flex items-center gap-3 px-4 py-3 text-xs font-black rounded-2xl transition cursor-pointer',
                activeTab === tab.id
                  ? 'bg-blue-600 text-white shadow-md shadow-blue-600/20'
                  : 'text-slate-700 dark:text-slate-300 hover:bg-slate-100 dark:hover:bg-slate-800',
              ]"
            >
              <component :is="tab.icon" class="w-4 h-4" />
              <span>{{ tab.label }}</span>
            </button>
          </div>
        </div>

        <!-- Main Form Content Column -->
        <div class="lg:col-span-3">
          <!-- Personal Info Tab -->
          <div
            v-if="activeTab === 'personal'"
            class="bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-3xl p-6 shadow-xs space-y-6"
          >
            <div>
              <h3 class="text-lg font-black text-slate-900 dark:text-white">
                {{ languageStore.t('personal_information', 'Personal Information') }}
              </h3>
              <p class="text-xs text-slate-500 dark:text-slate-400 mt-0.5">
                {{
                  languageStore.t(
                    'personal_info_desc',
                    'Update your personal account details and contact information.',
                  )
                }}
              </p>
            </div>

            <form @submit.prevent="updateProfile" class="space-y-4 text-xs font-sans">
              <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <div>
                  <label
                    class="block text-[10px] font-extrabold uppercase text-slate-500 dark:text-slate-400 mb-1"
                    >{{ languageStore.t('first_name', 'First Name') }} *</label
                  >
                  <input
                    v-model="formData.first_name"
                    type="text"
                    required
                    class="w-full px-3.5 py-2.5 rounded-xl bg-slate-50 dark:bg-slate-950 border border-slate-200 dark:border-slate-800 text-slate-900 dark:text-white font-bold focus:outline-none focus:border-amber-500 transition"
                  />
                </div>

                <div>
                  <label
                    class="block text-[10px] font-extrabold uppercase text-slate-500 dark:text-slate-400 mb-1"
                    >{{ languageStore.t('last_name', 'Last Name') }} *</label
                  >
                  <input
                    v-model="formData.last_name"
                    type="text"
                    required
                    class="w-full px-3.5 py-2.5 rounded-xl bg-slate-50 dark:bg-slate-950 border border-slate-200 dark:border-slate-800 text-slate-900 dark:text-white font-bold focus:outline-none focus:border-amber-500 transition"
                  />
                </div>
              </div>

              <div>
                <label
                  class="block text-[10px] font-extrabold uppercase text-slate-500 dark:text-slate-400 mb-1"
                  >{{ languageStore.t('email_readonly', 'Email Address (Read Only)') }}</label
                >
                <input
                  :value="profile?.email"
                  type="email"
                  disabled
                  class="w-full px-3.5 py-2.5 rounded-xl bg-slate-100 dark:bg-slate-800 border border-slate-200 dark:border-slate-700 text-slate-500 dark:text-slate-400 font-bold cursor-not-allowed"
                />
              </div>

              <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <div>
                  <label
                    class="block text-[10px] font-extrabold uppercase text-slate-500 dark:text-slate-400 mb-1"
                    >{{ languageStore.t('phone_number', 'Phone Number') }}</label
                  >
                  <input
                    v-model="formData.phone"
                    type="tel"
                    placeholder="+251 XXX XXX XXX"
                    class="w-full px-3.5 py-2.5 rounded-xl bg-slate-50 dark:bg-slate-950 border border-slate-200 dark:border-slate-800 text-slate-900 dark:text-white font-bold focus:outline-none focus:border-amber-500 transition"
                  />
                </div>

                <div>
                  <label
                    class="block text-[10px] font-extrabold uppercase text-slate-500 dark:text-slate-400 mb-1"
                    >{{ languageStore.t('department', 'Department') }}</label
                  >
                  <input
                    v-model="formData.department"
                    type="text"
                    :placeholder="languageStore.t('administration', 'Administration')"
                    class="w-full px-3.5 py-2.5 rounded-xl bg-slate-50 dark:bg-slate-950 border border-slate-200 dark:border-slate-800 text-slate-900 dark:text-white font-bold focus:outline-none focus:border-amber-500 transition"
                  />
                </div>
              </div>

              <div>
                <label
                  class="block text-[10px] font-extrabold uppercase text-slate-500 dark:text-slate-400 mb-1"
                  >{{ languageStore.t('bio_notes', 'Bio / Notes') }}</label
                >
                <textarea
                  v-model="formData.bio"
                  rows="3"
                  :placeholder="
                    languageStore.t(
                      'system_admin_notes_placeholder',
                      'System administration notes...',
                    )
                  "
                  class="w-full px-3.5 py-2.5 rounded-xl bg-slate-50 dark:bg-slate-950 border border-slate-200 dark:border-slate-800 text-slate-900 dark:text-white font-medium focus:outline-none focus:border-amber-500 transition"
                ></textarea>
              </div>

              <div
                class="flex items-center justify-end gap-2.5 pt-4 border-t border-slate-100 dark:border-slate-800"
              >
                <button
                  type="button"
                  @click="resetForm"
                  class="px-4 py-2 rounded-xl bg-slate-100 dark:bg-slate-800 hover:bg-slate-200 dark:hover:bg-slate-700 text-slate-700 dark:text-slate-300 font-bold text-xs transition cursor-pointer"
                >
                  {{ languageStore.t('cancel', 'Cancel') }}
                </button>
                <button
                  type="submit"
                  :disabled="loading"
                  class="px-5 py-2 rounded-xl bg-blue-600 hover:bg-blue-700 text-white font-black text-xs shadow-md shadow-blue-600/20 transition flex items-center gap-1.5 cursor-pointer disabled:opacity-50"
                >
                  <Save class="w-3.5 h-3.5" />
                  <span>{{
                    loading
                      ? languageStore.t('saving', 'Saving...')
                      : languageStore.t('save_changes', 'Save Changes')
                  }}</span>
                </button>
              </div>
            </form>
          </div>

          <!-- Professional Tab -->
          <div
            v-if="activeTab === 'professional'"
            class="bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-3xl p-6 shadow-xs space-y-6"
          >
            <div>
              <h3 class="text-lg font-black text-slate-900 dark:text-white">
                {{ languageStore.t('professional_details', 'Professional Details') }}
              </h3>
              <p class="text-xs text-slate-500 dark:text-slate-400 mt-0.5">
                {{
                  languageStore.t(
                    'professional_details_desc',
                    'Administrative role and system authorization status.',
                  )
                }}
              </p>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-2 gap-4 text-xs">
              <div
                class="p-4 rounded-2xl bg-slate-50 dark:bg-slate-950 border border-slate-200 dark:border-slate-800 space-y-2"
              >
                <p class="text-[10px] font-bold text-slate-400 uppercase">
                  {{ languageStore.t('employee_code', 'Employee Code') }}
                </p>
                <p class="font-mono font-black text-amber-600 dark:text-amber-400 text-sm">
                  {{ profile?.administrator?.employee_code || 'ADM971' }}
                </p>
              </div>

              <div
                class="p-4 rounded-2xl bg-slate-50 dark:bg-slate-950 border border-slate-200 dark:border-slate-800 space-y-2"
              >
                <p class="text-[10px] font-bold text-slate-400 uppercase">
                  {{ languageStore.t('department', 'Department') }}
                </p>
                <p class="font-black text-slate-900 dark:text-white text-sm">
                  {{
                    profile?.administrator?.department ||
                    languageStore.t('administration', 'Administration')
                  }}
                </p>
              </div>

              <div
                class="p-4 rounded-2xl bg-slate-50 dark:bg-slate-950 border border-slate-200 dark:border-slate-800 space-y-2"
              >
                <p class="text-[10px] font-bold text-slate-400 uppercase">
                  {{ languageStore.t('system_status', 'System Status') }}
                </p>
                <span
                  class="inline-flex items-center px-2.5 py-0.5 rounded-full text-[10px] font-black uppercase bg-emerald-500/10 text-emerald-600 dark:text-emerald-400 border border-emerald-500/20"
                >
                  {{ profile?.administrator?.status || languageStore.t('active', 'Active') }}
                </span>
              </div>

              <div
                class="p-4 rounded-2xl bg-slate-50 dark:bg-slate-950 border border-slate-200 dark:border-slate-800 space-y-2"
              >
                <p class="text-[10px] font-bold text-slate-400 uppercase">
                  {{ languageStore.t('hire_date', 'Hire Date') }}
                </p>
                <p class="font-black text-slate-900 dark:text-white text-sm">
                  {{ formatDate(profile?.administrator?.hire_date) }}
                </p>
              </div>
            </div>
          </div>

          <!-- Statistics Tab -->
          <div
            v-if="activeTab === 'statistics'"
            class="bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-3xl p-6 shadow-xs space-y-6"
          >
            <div>
              <h3 class="text-lg font-black text-slate-900 dark:text-white">
                {{ languageStore.t('system_statistics', 'System Statistics') }}
              </h3>
              <p class="text-xs text-slate-500 dark:text-slate-400 mt-0.5">
                {{
                  languageStore.t('system_statistics_desc', 'Overview of system resources managed.')
                }}
              </p>
            </div>

            <div class="grid grid-cols-2 md:grid-cols-4 gap-4 text-xs">
              <div class="p-4 rounded-2xl bg-blue-500/10 border border-blue-500/20 text-center">
                <p class="text-[10px] font-extrabold uppercase text-blue-600 dark:text-blue-400">
                  {{ languageStore.t('total_users', 'Total Users') }}
                </p>
                <p class="text-2xl font-black text-blue-900 dark:text-blue-200 mt-1">
                  {{ stats?.total_users || 0 }}
                </p>
              </div>

              <div
                class="p-4 rounded-2xl bg-emerald-500/10 border border-emerald-500/20 text-center"
              >
                <p
                  class="text-[10px] font-extrabold uppercase text-emerald-600 dark:text-emerald-400"
                >
                  {{ languageStore.t('total_rooms', 'Total Rooms') }}
                </p>
                <p class="text-2xl font-black text-emerald-900 dark:text-emerald-200 mt-1">
                  {{ stats?.total_rooms || 0 }}
                </p>
              </div>

              <div class="p-4 rounded-2xl bg-purple-500/10 border border-purple-500/20 text-center">
                <p
                  class="text-[10px] font-extrabold uppercase text-purple-600 dark:text-purple-400"
                >
                  {{ languageStore.t('total_orders', 'Total Orders') }}
                </p>
                <p class="text-2xl font-black text-purple-900 dark:text-purple-200 mt-1">
                  {{ stats?.total_orders || 0 }}
                </p>
              </div>

              <div class="p-4 rounded-2xl bg-amber-500/10 border border-amber-500/20 text-center">
                <p class="text-[10px] font-extrabold uppercase text-amber-600 dark:text-amber-400">
                  {{ languageStore.t('system_revenue', 'System Revenue') }}
                </p>
                <p class="text-xl font-black text-amber-900 dark:text-amber-200 mt-1">
                  {{ formatNumber(stats?.total_revenue) }} ETB
                </p>
              </div>
            </div>
          </div>

          <!-- Security Tab -->
          <div
            v-if="activeTab === 'security'"
            class="bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-3xl p-6 shadow-xs space-y-6"
          >
            <div>
              <h3 class="text-lg font-black text-slate-900 dark:text-white">
                {{ languageStore.t('security_and_password', 'Security & Password') }}
              </h3>
              <p class="text-xs text-slate-500 dark:text-slate-400 mt-0.5">
                {{
                  languageStore.t(
                    'security_password_desc',
                    'Change your account password securely.',
                  )
                }}
              </p>
            </div>

            <!-- Temporary Password Notice -->
            <div
              v-if="authStore.mustChangePassword"
              class="p-4 rounded-2xl bg-amber-500/10 border border-amber-500/20 text-amber-800 dark:text-amber-300 text-xs space-y-1"
            >
              <p class="font-extrabold flex items-center gap-1.5">
                <KeyRound class="w-4 h-4 text-amber-500" />
                {{ languageStore.t('temp_password_active', 'Temporary Password Active') }}
              </p>
              <p class="text-[11px] leading-relaxed">
                {{
                  languageStore.t(
                    'temp_password_notice',
                    'Your account was initialized with a system-generated password. Enter your current temporary password below to set your personal permanent password.',
                  )
                }}
              </p>
            </div>

            <form @submit.prevent="changePassword" class="space-y-4 text-xs max-w-md font-sans">
              <div>
                <label
                  class="block text-[10px] font-extrabold uppercase text-slate-500 dark:text-slate-400 mb-1"
                  >{{ languageStore.t('current_password', 'Current Password') }} *</label
                >
                <div class="relative">
                  <input
                    v-model="passwordData.current_password"
                    :type="showCurrentPassword ? 'text' : 'password'"
                    :placeholder="
                      languageStore.t('enter_current_password', 'Enter your current password')
                    "
                    required
                    class="w-full px-3.5 py-2.5 pr-10 rounded-xl bg-slate-50 dark:bg-slate-950 border border-slate-200 dark:border-slate-800 text-slate-900 dark:text-white font-bold focus:outline-none focus:border-amber-500 transition"
                  />
                  <button
                    type="button"
                    @click="showCurrentPassword = !showCurrentPassword"
                    class="absolute right-3 top-1/2 -translate-y-1/2 text-slate-400 hover:text-slate-600 dark:hover:text-slate-200 transition focus:outline-none cursor-pointer"
                    :title="
                      languageStore.t('toggle_password_visibility', 'Toggle Password Visibility')
                    "
                  >
                    <Eye v-if="!showCurrentPassword" class="w-4 h-4" />
                    <EyeOff v-else class="w-4 h-4 text-amber-500" />
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
                    v-model="passwordData.new_password"
                    :type="showNewPassword ? 'text' : 'password'"
                    :placeholder="languageStore.t('min_8_chars', 'Minimum 8 characters')"
                    required
                    class="w-full px-3.5 py-2.5 pr-10 rounded-xl bg-slate-50 dark:bg-slate-950 border border-slate-200 dark:border-slate-800 text-slate-900 dark:text-white font-bold focus:outline-none focus:border-amber-500 transition"
                  />
                  <button
                    type="button"
                    @click="showNewPassword = !showNewPassword"
                    class="absolute right-3 top-1/2 -translate-y-1/2 text-slate-400 hover:text-slate-600 dark:hover:text-slate-200 transition focus:outline-none cursor-pointer"
                    :title="
                      languageStore.t('toggle_password_visibility', 'Toggle Password Visibility')
                    "
                  >
                    <Eye v-if="!showNewPassword" class="w-4 h-4" />
                    <EyeOff v-else class="w-4 h-4 text-amber-500" />
                  </button>
                </div>
              </div>

              <div>
                <label
                  class="block text-[10px] font-extrabold uppercase text-slate-500 dark:text-slate-400 mb-1"
                  >{{ languageStore.t('confirm_new_password', 'Confirm New Password') }} *</label
                >
                <div class="relative">
                  <input
                    v-model="passwordData.new_password_confirmation"
                    :type="showConfirmPassword ? 'text' : 'password'"
                    :placeholder="languageStore.t('reenter_new_password', 'Re-enter new password')"
                    required
                    class="w-full px-3.5 py-2.5 pr-10 rounded-xl bg-slate-50 dark:bg-slate-950 border border-slate-200 dark:border-slate-800 text-slate-900 dark:text-white font-bold focus:outline-none focus:border-amber-500 transition"
                  />
                  <button
                    type="button"
                    @click="showConfirmPassword = !showConfirmPassword"
                    class="absolute right-3 top-1/2 -translate-y-1/2 text-slate-400 hover:text-slate-600 dark:hover:text-slate-200 transition focus:outline-none cursor-pointer"
                    :title="
                      languageStore.t('toggle_password_visibility', 'Toggle Password Visibility')
                    "
                  >
                    <Eye v-if="!showConfirmPassword" class="w-4 h-4" />
                    <EyeOff v-else class="w-4 h-4 text-amber-500" />
                  </button>
                </div>
              </div>

              <div class="pt-2">
                <button
                  type="submit"
                  :disabled="loading"
                  class="px-5 py-2.5 rounded-xl bg-rose-600 hover:bg-rose-700 text-white font-black text-xs shadow-md shadow-rose-600/20 transition flex items-center gap-1.5 cursor-pointer disabled:opacity-50"
                >
                  <Lock class="w-3.5 h-3.5" />
                  <span>{{
                    loading
                      ? languageStore.t('updating', 'Updating...')
                      : languageStore.t('update_password', 'Update Password')
                  }}</span>
                </button>
              </div>
            </form>
          </div>
        </div>
      </div>
    </div>
  </DashboardLayout>
</template>

<script setup lang="ts">
import { ref, computed, onMounted } from 'vue'
import { useRoute } from 'vue-router'
import DashboardLayout from '@/Layouts/DashboardLayout.vue'
import { useAuthStore } from '@/stores/auth'
import { useLanguageStore } from '@/stores/language'
import {
  adminProfileService,
  type AdminProfile,
  type AdminStats,
} from '@/services/profile/adminProfileService'
import {
  Camera,
  User,
  Lock,
  BarChart3,
  Users,
  ShoppingBag,
  DollarSign,
  Building,
  Home,
  Briefcase,
  Mail,
  Phone,
  Save,
  Shield,
  Calendar,
  RefreshCw,
  KeyRound,
  Eye,
  EyeOff,
} from 'lucide-vue-next'

const route = useRoute()
const authStore = useAuthStore()
const languageStore = useLanguageStore()

// State
const profile = ref<AdminProfile | null>(null)
const stats = ref<AdminStats | null>(null)
const loading = ref(false)
const activeTab = ref('personal')

const showCurrentPassword = ref(false)
const showNewPassword = ref(false)
const showConfirmPassword = ref(false)

// Form Data
const formData = ref({
  first_name: '',
  last_name: '',
  phone: '',
  department: '',
  bio: '',
})

const passwordData = ref({
  current_password: '',
  new_password: '',
  new_password_confirmation: '',
})

// Tabs Configuration
const localizedTabs = computed(() => [
  { id: 'personal', label: languageStore.t('personal_info', 'Personal Info'), icon: User },
  { id: 'professional', label: languageStore.t('professional', 'Professional'), icon: Briefcase },
  { id: 'statistics', label: languageStore.t('statistics', 'Statistics'), icon: BarChart3 },
  { id: 'security', label: languageStore.t('security', 'Security'), icon: Lock },
])

// Computed
const profilePhotoUrl = computed(() => {
  if (profile.value?.administrator?.profile_photo) {
    return `http://127.0.0.1:8000/storage/${profile.value.administrator.profile_photo}`
  }
  return '/images/avatar.png'
})

// Methods
async function loadProfile() {
  try {
    loading.value = true
    profile.value = await adminProfileService.getProfile()

    // Populate form
    formData.value = {
      first_name: profile.value.first_name,
      last_name: profile.value.last_name,
      phone: profile.value.phone || '',
      department: profile.value.administrator?.department || '',
      bio: profile.value.administrator?.bio || '',
    }
  } catch (error: any) {
    console.error('Failed to load admin profile:', error)
  } finally {
    loading.value = false
  }
}

async function loadStats() {
  try {
    stats.value = await adminProfileService.getStats()
  } catch (error: any) {
    console.error('Failed to load stats:', error)
  }
}

async function updateProfile() {
  try {
    loading.value = true
    await adminProfileService.updateProfile(formData.value)
    await loadProfile()
    alert(languageStore.t('profile_updated_success', 'Profile updated successfully'))
  } catch (error: any) {
    console.error('[AdminProfile] Failed to update profile:', error)
    alert(`Error: ${error.response?.data?.message || 'Failed to update profile'}`)
  } finally {
    loading.value = false
  }
}

async function handlePhotoUpload(event: Event) {
  const target = event.target as HTMLInputElement
  const file = target.files?.[0]

  if (!file) return

  if (file.size > 2 * 1024 * 1024) {
    alert(languageStore.t('photo_size_limit_error', 'Error: Photo size must be less than 2MB'))
    return
  }

  if (!file.type.startsWith('image/')) {
    alert(languageStore.t('photo_type_error', 'Error: Please upload an image file'))
    return
  }

  try {
    loading.value = true
    await adminProfileService.uploadPhoto(file)
    await loadProfile()
    alert(languageStore.t('photo_uploaded_success', 'Photo uploaded successfully'))
  } catch (error: any) {
    console.error('[AdminProfile] Failed to upload photo:', error)
    alert(`Error: ${error.response?.data?.message || 'Failed to upload photo'}`)
  } finally {
    loading.value = false
    if (target) target.value = ''
  }
}

async function changePassword() {
  if (passwordData.value.new_password !== passwordData.value.new_password_confirmation) {
    alert(languageStore.t('passwords_not_match', 'Error: Passwords do not match'))
    return
  }
  if (passwordData.value.new_password.length < 8) {
    alert(
      languageStore.t(
        'password_min_length_error',
        'Error: New password must be at least 8 characters long',
      ),
    )
    return
  }

  try {
    loading.value = true
    await adminProfileService.changePassword({
      current_password: passwordData.value.current_password,
      new_password: passwordData.value.new_password,
      new_password_confirmation: passwordData.value.new_password_confirmation,
    })
    alert(
      languageStore.t(
        'password_updated_success',
        'Password changed successfully! Your account is now updated.',
      ),
    )
    passwordData.value = {
      current_password: '',
      new_password: '',
      new_password_confirmation: '',
    }
  } catch (error: any) {
    console.error('[AdminProfile] Failed to change password:', error)
    alert(`Error: ${error.response?.data?.message || 'Failed to change password'}`)
  } finally {
    loading.value = false
  }
}

function resetForm() {
  if (profile.value) {
    formData.value = {
      first_name: profile.value.first_name,
      last_name: profile.value.last_name,
      phone: profile.value.phone || '',
      department: profile.value.administrator?.department || '',
      bio: profile.value.administrator?.bio || '',
    }
  }
}

function formatDate(date: string | null | undefined): string {
  if (!date) return 'N/A'
  return new Date(date).toLocaleDateString(
    languageStore.currentLanguage === 'am' ? 'am-ET' : 'en-US',
    {
      year: 'numeric',
      month: 'short',
      day: 'numeric',
    },
  )
}

function formatNumber(num: number | undefined): string {
  if (!num) return '0.00'
  return num.toLocaleString('en-US', { minimumFractionDigits: 2, maximumFractionDigits: 2 })
}

// Lifecycle
onMounted(() => {
  if (route.query.tab === 'security') {
    activeTab.value = 'security'
  }
  loadProfile()
  loadStats()
})
</script>
