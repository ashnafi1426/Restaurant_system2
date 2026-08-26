<template>
  <DashboardLayout>
    <div class="space-y-6 bg-slate-50 dark:bg-slate-950 min-h-screen p-4 sm:p-6 max-w-full font-sans">
      <!-- Profile Header Banner -->
      <div class="bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 p-6 rounded-3xl shadow-xs flex flex-col md:flex-row items-center justify-between gap-6">
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
              title="Upload Profile Photo"
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
                {{ profile?.full_name || 'Restaurant Manager' }}
              </h1>
              <span class="px-2.5 py-0.5 rounded-full text-[10px] font-black uppercase tracking-wider bg-emerald-500/10 text-emerald-600 dark:text-emerald-400 border border-emerald-500/20">
                {{ profile?.manager?.status || 'Active' }}
              </span>
            </div>

            <div class="flex flex-wrap items-center justify-center md:justify-start gap-2 mt-1.5 text-xs text-slate-500 dark:text-slate-400 font-medium">
              <span class="font-mono font-bold text-amber-600 dark:text-amber-400">{{ profile?.manager?.employee_code || 'MGR101' }}</span>
              <span>•</span>
              <span>{{ profile?.manager?.department || 'Management' }}</span>
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
          <span>Refresh Profile</span>
        </button>
      </div>

      <div class="grid grid-cols-1 lg:grid-cols-4 gap-6">
        <!-- Sidebar Navigation & Quick Info -->
        <div class="lg:col-span-1 space-y-6">
          <!-- Quick Info Card -->
          <div class="bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-3xl p-5 shadow-xs space-y-4">
            <h3 class="text-xs font-black text-slate-400 uppercase tracking-wider">Quick Info</h3>
            <div class="space-y-3 text-xs">
              <div class="flex items-start gap-3">
                <Briefcase class="w-4 h-4 text-blue-500 mt-0.5 flex-shrink-0" />
                <div>
                  <p class="text-[10px] font-bold text-slate-400 uppercase">Department</p>
                  <p class="font-extrabold text-slate-900 dark:text-white">{{ profile?.manager?.department || 'Management' }}</p>
                </div>
              </div>

              <div class="flex items-start gap-3">
                <Calendar class="w-4 h-4 text-amber-500 mt-0.5 flex-shrink-0" />
                <div>
                  <p class="text-[10px] font-bold text-slate-400 uppercase">Hire Date</p>
                  <p class="font-extrabold text-slate-900 dark:text-white">{{ formatDate(profile?.manager?.hire_date) }}</p>
                </div>
              </div>

              <div class="flex items-start gap-3">
                <Mail class="w-4 h-4 text-emerald-500 mt-0.5 flex-shrink-0" />
                <div class="min-w-0 flex-1">
                  <p class="text-[10px] font-bold text-slate-400 uppercase">Email</p>
                  <p class="font-extrabold text-slate-900 dark:text-white truncate">{{ profile?.email }}</p>
                </div>
              </div>

              <div class="flex items-start gap-3">
                <Phone class="w-4 h-4 text-purple-500 mt-0.5 flex-shrink-0" />
                <div>
                  <p class="text-[10px] font-bold text-slate-400 uppercase">Phone</p>
                  <p class="font-extrabold text-slate-900 dark:text-white">{{ profile?.phone || 'Not provided' }}</p>
                </div>
              </div>
            </div>
          </div>

          <!-- Navigation Tabs Card -->
          <div class="bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-3xl p-2 shadow-xs space-y-1">
            <button
              v-for="tab in tabs"
              :key="tab.id"
              @click="activeTab = tab.id"
              :class="[
                'w-full flex items-center gap-3 px-4 py-3 text-xs font-black rounded-2xl transition cursor-pointer',
                activeTab === tab.id
                  ? 'bg-blue-600 text-white shadow-md shadow-blue-600/20'
                  : 'text-slate-700 dark:text-slate-300 hover:bg-slate-100 dark:hover:bg-slate-800'
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
          <div v-if="activeTab === 'personal'" class="bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-3xl p-6 shadow-xs space-y-6">
            <div>
              <h3 class="text-lg font-black text-slate-900 dark:text-white">Personal Information</h3>
              <p class="text-xs text-slate-500 dark:text-slate-400 mt-0.5">Update your manager details and contact information.</p>
            </div>

            <form @submit.prevent="updateProfile" class="space-y-4 text-xs font-sans">
              <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <div>
                  <label class="block text-[10px] font-extrabold uppercase text-slate-500 dark:text-slate-400 mb-1">First Name *</label>
                  <input
                    v-model="formData.first_name"
                    type="text"
                    required
                    class="w-full px-3.5 py-2.5 rounded-xl bg-slate-50 dark:bg-slate-950 border border-slate-200 dark:border-slate-800 text-slate-900 dark:text-white font-bold focus:outline-none focus:border-amber-500 transition"
                  />
                </div>

                <div>
                  <label class="block text-[10px] font-extrabold uppercase text-slate-500 dark:text-slate-400 mb-1">Last Name *</label>
                  <input
                    v-model="formData.last_name"
                    type="text"
                    required
                    class="w-full px-3.5 py-2.5 rounded-xl bg-slate-50 dark:bg-slate-950 border border-slate-200 dark:border-slate-800 text-slate-900 dark:text-white font-bold focus:outline-none focus:border-amber-500 transition"
                  />
                </div>
              </div>

              <div>
                <label class="block text-[10px] font-extrabold uppercase text-slate-500 dark:text-slate-400 mb-1">Email Address (Read Only)</label>
                <input
                  :value="profile?.email"
                  type="email"
                  disabled
                  class="w-full px-3.5 py-2.5 rounded-xl bg-slate-100 dark:bg-slate-800 border border-slate-200 dark:border-slate-700 text-slate-500 dark:text-slate-400 font-bold cursor-not-allowed"
                />
              </div>

              <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <div>
                  <label class="block text-[10px] font-extrabold uppercase text-slate-500 dark:text-slate-400 mb-1">Phone Number</label>
                  <input
                    v-model="formData.phone"
                    type="tel"
                    placeholder="+251 XXX XXX XXX"
                    class="w-full px-3.5 py-2.5 rounded-xl bg-slate-50 dark:bg-slate-950 border border-slate-200 dark:border-slate-800 text-slate-900 dark:text-white font-bold focus:outline-none focus:border-amber-500 transition"
                  />
                </div>

                <div>
                  <label class="block text-[10px] font-extrabold uppercase text-slate-500 dark:text-slate-400 mb-1">Department</label>
                  <input
                    v-model="formData.department"
                    type="text"
                    placeholder="Management"
                    class="w-full px-3.5 py-2.5 rounded-xl bg-slate-50 dark:bg-slate-950 border border-slate-200 dark:border-slate-800 text-slate-900 dark:text-white font-bold focus:outline-none focus:border-amber-500 transition"
                  />
                </div>
              </div>

              <div>
                <label class="block text-[10px] font-extrabold uppercase text-slate-500 dark:text-slate-400 mb-1">Bio / Notes</label>
                <textarea
                  v-model="formData.bio"
                  rows="3"
                  placeholder="Manager notes..."
                  class="w-full px-3.5 py-2.5 rounded-xl bg-slate-50 dark:bg-slate-950 border border-slate-200 dark:border-slate-800 text-slate-900 dark:text-white font-medium focus:outline-none focus:border-amber-500 transition"
                ></textarea>
              </div>

              <div class="flex items-center justify-end gap-2.5 pt-4 border-t border-slate-100 dark:border-slate-800">
                <button
                  type="button"
                  @click="resetForm"
                  class="px-4 py-2 rounded-xl bg-slate-100 dark:bg-slate-800 hover:bg-slate-200 dark:hover:bg-slate-700 text-slate-700 dark:text-slate-300 font-bold text-xs transition cursor-pointer"
                >
                  Cancel
                </button>
                <button
                  type="submit"
                  :disabled="loading"
                  class="px-5 py-2 rounded-xl bg-blue-600 hover:bg-blue-700 text-white font-black text-xs shadow-md shadow-blue-600/20 transition flex items-center gap-1.5 cursor-pointer disabled:opacity-50"
                >
                  <Save class="w-3.5 h-3.5" />
                  <span>{{ loading ? 'Saving...' : 'Save Changes' }}</span>
                </button>
              </div>
            </form>
          </div>

          <!-- Security Tab -->
          <div v-if="activeTab === 'security'" class="bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-3xl p-6 shadow-xs space-y-6">
            <div>
              <h3 class="text-lg font-black text-slate-900 dark:text-white">Security & Password</h3>
              <p class="text-xs text-slate-500 dark:text-slate-400 mt-0.5">Change your account password securely.</p>
            </div>

            <form @submit.prevent="changePassword" class="space-y-4 text-xs max-w-md font-sans">
              <div>
                <label class="block text-[10px] font-extrabold uppercase text-slate-500 dark:text-slate-400 mb-1">Current Password *</label>
                <input
                  v-model="passwordData.current_password"
                  type="password"
                  required
                  class="w-full px-3.5 py-2.5 rounded-xl bg-slate-50 dark:bg-slate-950 border border-slate-200 dark:border-slate-800 text-slate-900 dark:text-white font-bold focus:outline-none focus:border-amber-500 transition"
                />
              </div>

              <div>
                <label class="block text-[10px] font-extrabold uppercase text-slate-500 dark:text-slate-400 mb-1">New Password *</label>
                <input
                  v-model="passwordData.new_password"
                  type="password"
                  required
                  class="w-full px-3.5 py-2.5 rounded-xl bg-slate-50 dark:bg-slate-950 border border-slate-200 dark:border-slate-800 text-slate-900 dark:text-white font-bold focus:outline-none focus:border-amber-500 transition"
                />
              </div>

              <div>
                <label class="block text-[10px] font-extrabold uppercase text-slate-500 dark:text-slate-400 mb-1">Confirm New Password *</label>
                <input
                  v-model="passwordData.new_password_confirmation"
                  type="password"
                  required
                  class="w-full px-3.5 py-2.5 rounded-xl bg-slate-50 dark:bg-slate-950 border border-slate-200 dark:border-slate-800 text-slate-900 dark:text-white font-bold focus:outline-none focus:border-amber-500 transition"
                />
              </div>

              <div class="pt-2">
                <button
                  type="submit"
                  :disabled="loading"
                  class="px-5 py-2.5 rounded-xl bg-rose-600 hover:bg-rose-700 text-white font-black text-xs shadow-md shadow-rose-600/20 transition flex items-center gap-1.5 cursor-pointer disabled:opacity-50"
                >
                  <Lock class="w-3.5 h-3.5" />
                  <span>{{ loading ? 'Updating...' : 'Update Password' }}</span>
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
import DashboardLayout from '@/Layouts/DashboardLayout.vue'
import { managerProfileService, type ManagerProfile } from '@/services/profile/managerProfileService'
import {
  Camera, User, Lock, Briefcase, Mail, Phone, Save, Calendar, RefreshCw
} from 'lucide-vue-next'

// State
const profile = ref<ManagerProfile | null>(null)
const loading = ref(false)
const activeTab = ref('personal')

// Form Data
const formData = ref({
  first_name: '',
  last_name: '',
  phone: '',
  department: '',
  bio: ''
})

const passwordData = ref({
  current_password: '',
  new_password: '',
  new_password_confirmation: ''
})

// Tabs Configuration
const tabs = [
  { id: 'personal', label: 'Personal Info', icon: User },
  { id: 'security', label: 'Security', icon: Lock }
]

// Computed
const profilePhotoUrl = computed(() => {
  if (profile.value?.manager?.profile_photo) {
    return `http://127.0.0.1:8000/storage/${profile.value.manager.profile_photo}`
  }
  return '/images/avatar.png'
})

// Methods
async function loadProfile() {
  try {
    loading.value = true
    profile.value = await managerProfileService.getProfile()
    
    // Populate form
    formData.value = {
      first_name: profile.value.first_name,
      last_name: profile.value.last_name,
      phone: profile.value.phone || '',
      department: profile.value.manager?.department || '',
      bio: profile.value.manager?.bio || ''
    }
  } catch (error: any) {
    console.error('Failed to load manager profile:', error)
  } finally {
    loading.value = false
  }
}

async function updateProfile() {
  try {
    loading.value = true
    await managerProfileService.updateProfile(formData.value)
    await loadProfile()
    alert('Profile updated successfully')
  } catch (error: any) {
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
    alert('Error: Photo size must be less than 2MB')
    return
  }
  
  if (!file.type.startsWith('image/')) {
    alert('Error: Please upload an image file')
    return
  }
  
  try {
    loading.value = true
    await managerProfileService.uploadPhoto(file)
    await loadProfile()
    alert('Photo uploaded successfully')
  } catch (error: any) {
    alert(`Error: ${error.response?.data?.message || 'Failed to upload photo'}`)
  } finally {
    loading.value = false
    if (target) target.value = ''
  }
}

async function changePassword() {
  if (passwordData.value.new_password !== passwordData.value.new_password_confirmation) {
    alert('Error: Passwords do not match')
    return
  }
  
  try {
    loading.value = true
    await managerProfileService.changePassword(passwordData.value)
    alert('Password changed successfully')
    
    passwordData.value = {
      current_password: '',
      new_password: '',
      new_password_confirmation: ''
    }
  } catch (error: any) {
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
      department: profile.value.manager?.department || '',
      bio: profile.value.manager?.bio || ''
    }
  }
}

function formatDate(date: string | null | undefined): string {
  if (!date) return 'N/A'
  return new Date(date).toLocaleDateString('en-US', {
    year: 'numeric',
    month: 'short',
    day: 'numeric'
  })
}

// Lifecycle
onMounted(() => {
  loadProfile()
})
</script>
