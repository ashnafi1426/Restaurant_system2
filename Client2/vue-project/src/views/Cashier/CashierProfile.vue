<template>
  <DashboardLayout>
    <div class="py-8">
      <!-- Header -->
      <div class="mb-8">
        <h1 class="text-3xl font-bold text-gray-900">Profile Settings</h1>
        <p class="mt-2 text-sm text-gray-600">Manage your personal information and account settings</p>
      </div>

      <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
        <!-- Sidebar -->
        <div class="lg:col-span-1">
          <div class="bg-white rounded-lg shadow">
            <div class="p-6">
              <!-- Profile Photo -->
              <div class="flex flex-col items-center">
                <div class="relative">
                  <img
                    :src="profilePhotoUrl"
                    alt="Profile"
                    class="w-32 h-32 rounded-full object-cover border-4 border-gray-200"
                  />
                  <label
                    for="photo-upload"
                    class="absolute bottom-0 right-0 bg-blue-600 text-white p-2 rounded-full cursor-pointer hover:bg-blue-700 transition"
                  >
                    <Camera class="w-4 h-4" />
                  </label>
                  <input
                    id="photo-upload"
                    type="file"
                    accept="image/*"
                    class="hidden"
                    @change="handlePhotoUpload"
                  />
                </div>
                <h2 class="mt-4 text-xl font-semibold text-gray-900">{{ profile?.full_name }}</h2>
                <p class="text-sm text-gray-500">{{ profile?.cashier?.employee_code || 'No Employee Code' }}</p>
                <span
                  :class="[
                    'mt-2 inline-flex items-center px-3 py-1 rounded-full text-xs font-medium',
                    statusColor
                  ]"
                >
                  {{ profile?.cashier?.status || 'active' }}
                </span>
              </div>

              <!-- Quick Stats -->
              <div class="mt-6 space-y-3">
                <div class="flex items-center justify-between text-sm">
                  <span class="text-gray-600">Register Number</span>
                  <span class="font-medium text-gray-900">{{ profile?.cashier?.register_number || 'N/A' }}</span>
                </div>
                <div class="flex items-center justify-between text-sm">
                  <span class="text-gray-600">Shift</span>
                  <span class="font-medium text-gray-900">{{ profile?.cashier?.shift || 'N/A' }}</span>
                </div>
                <div class="flex items-center justify-between text-sm">
                  <span class="text-gray-600">Hire Date</span>
                  <span class="font-medium text-gray-900">{{ formatDate(profile?.cashier?.hire_date) }}</span>
                </div>
                <div class="flex items-center justify-between text-sm">
                  <span class="text-gray-600">Email</span>
                  <span class="font-medium text-gray-900 truncate">{{ profile?.email }}</span>
                </div>
              </div>

              <!-- Status Toggle -->
              <div class="mt-6">
                <label class="block text-sm font-medium text-gray-700 mb-2">Update Status</label>
                <select
                  v-model="selectedStatus"
                  @change="updateStatus"
                  class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                >
                  <option value="active">Active</option>
                  <option value="on_break">On Break</option>
                  <option value="off_duty">Off Duty</option>
                </select>
              </div>
            </div>

            <!-- Navigation -->
            <div class="border-t border-gray-200">
              <nav class="flex flex-col">
                <button
                  v-for="tab in tabs"
                  :key="tab.id"
                  @click="activeTab = tab.id"
                  :class="[
                    'flex items-center px-6 py-3 text-sm font-medium transition',
                    activeTab === tab.id
                      ? 'bg-blue-50 text-blue-700 border-r-4 border-blue-700'
                      : 'text-gray-700 hover:bg-gray-50'
                  ]"
                >
                  <component :is="tab.icon" class="w-5 h-5 mr-3" />
                  {{ tab.label }}
                </button>
              </nav>
            </div>
          </div>
        </div>

        <!-- Main Content -->
        <div class="lg:col-span-2">
          <div class="bg-white rounded-lg shadow">
            <!-- Personal Information Tab -->
            <div v-if="activeTab === 'personal'" class="p-6">
              <h3 class="text-lg font-semibold text-gray-900 mb-6">Personal Information</h3>
              <form @submit.prevent="updateProfile" class="space-y-6">
                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                  <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">
                      First Name *
                    </label>
                    <input
                      v-model="formData.first_name"
                      type="text"
                      required
                      class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                    />
                  </div>
                  <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">
                      Last Name *
                    </label>
                    <input
                      v-model="formData.last_name"
                      type="text"
                      required
                      class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                    />
                  </div>
                </div>

                <div>
                  <label class="block text-sm font-medium text-gray-700 mb-2">
                    Email (Read Only)
                  </label>
                  <input
                    :value="profile?.email"
                    type="email"
                    disabled
                    class="w-full px-4 py-2 border border-gray-300 rounded-lg bg-gray-50"
                  />
                </div>

                <div>
                  <label class="block text-sm font-medium text-gray-700 mb-2">
                    Phone Number
                  </label>
                  <input
                    v-model="formData.phone"
                    type="tel"
                    class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                  />
                </div>

                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                  <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">
                      Shift
                    </label>
                    <select
                      v-model="formData.shift"
                      class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                    >
                      <option value="">Select Shift</option>
                      <option value="morning">Morning</option>
                      <option value="evening">Evening</option>
                      <option value="night">Night</option>
                    </select>
                  </div>
                  <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">
                      Register Number
                    </label>
                    <input
                      v-model="formData.register_number"
                      type="text"
                      class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                    />
                  </div>
                </div>

                <div>
                  <label class="block text-sm font-medium text-gray-700 mb-2">
                    Bio
                  </label>
                  <textarea
                    v-model="formData.bio"
                    rows="4"
                    class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                    placeholder="Tell us about yourself..."
                  ></textarea>
                </div>

                <div class="flex justify-end gap-3">
                  <button
                    type="button"
                    @click="resetForm"
                    class="px-6 py-2 border border-gray-300 rounded-lg text-gray-700 hover:bg-gray-50 transition"
                  >
                    Cancel
                  </button>
                  <button
                    type="submit"
                    :disabled="loading"
                    class="px-6 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 transition disabled:opacity-50"
                  >
                    {{ loading ? 'Saving...' : 'Save Changes' }}
                  </button>
                </div>
              </form>
            </div>

            <!-- Security Tab -->
            <div v-if="activeTab === 'security'" class="p-6">
              <h3 class="text-lg font-semibold text-gray-900 mb-6">Change Password</h3>
              <form @submit.prevent="changePassword" class="space-y-6">
                <div>
                  <label class="block text-sm font-medium text-gray-700 mb-2">
                    Current Password *
                  </label>
                  <input
                    v-model="passwordData.current_password"
                    type="password"
                    required
                    class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                  />
                </div>

                <div>
                  <label class="block text-sm font-medium text-gray-700 mb-2">
                    New Password *
                  </label>
                  <input
                    v-model="passwordData.new_password"
                    type="password"
                    required
                    minlength="8"
                    class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                  />
                  <p class="mt-1 text-xs text-gray-500">Must be at least 8 characters</p>
                </div>

                <div>
                  <label class="block text-sm font-medium text-gray-700 mb-2">
                    Confirm New Password *
                  </label>
                  <input
                    v-model="passwordData.new_password_confirmation"
                    type="password"
                    required
                    class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                  />
                </div>

                <div class="flex justify-end">
                  <button
                    type="submit"
                    :disabled="loading"
                    class="px-6 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 transition disabled:opacity-50"
                  >
                    {{ loading ? 'Updating...' : 'Update Password' }}
                  </button>
                </div>
              </form>
            </div>

            <!-- Statistics Tab -->
            <div v-if="activeTab === 'statistics'" class="p-6">
              <h3 class="text-lg font-semibold text-gray-900 mb-6">Account Statistics</h3>
              <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                <div class="bg-blue-50 rounded-lg p-4">
                  <div class="flex items-center justify-between">
                    <div>
                      <p class="text-sm text-gray-600">Transactions Today</p>
                      <p class="text-2xl font-bold text-gray-900 mt-1">{{ stats?.total_transactions_today || 0 }}</p>
                    </div>
                    <CreditCard class="w-10 h-10 text-blue-600" />
                  </div>
                </div>

                <div class="bg-green-50 rounded-lg p-4">
                  <div class="flex items-center justify-between">
                    <div>
                      <p class="text-sm text-gray-600">Amount Collected Today</p>
                      <p class="text-2xl font-bold text-gray-900 mt-1">ETB {{ formatNumber(stats?.total_amount_collected_today) }}</p>
                    </div>
                    <DollarSign class="w-10 h-10 text-green-600" />
                  </div>
                </div>

                <div class="bg-purple-50 rounded-lg p-4">
                  <div class="flex items-center justify-between">
                    <div>
                      <p class="text-sm text-gray-600">Pending Payments</p>
                      <p class="text-2xl font-bold text-gray-900 mt-1">{{ stats?.pending_payments || 0 }}</p>
                    </div>
                    <Clock class="w-10 h-10 text-purple-600" />
                  </div>
                </div>

                <div class="bg-yellow-50 rounded-lg p-4">
                  <div class="flex items-center justify-between">
                    <div>
                      <p class="text-sm text-gray-600">Completed Payments</p>
                      <p class="text-2xl font-bold text-gray-900 mt-1">{{ stats?.completed_payments || 0 }}</p>
                    </div>
                    <CheckCircle class="w-10 h-10 text-yellow-600" />
                  </div>
                </div>
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>
  </DashboardLayout>
</template>

<script setup lang="ts">
import { ref, computed, onMounted } from 'vue'
import DashboardLayout from '@/Layouts/DashboardLayout.vue'
import { cashierProfileService, type CashierProfile, type CashierStats } from '@/services/profile/cashierProfileService'
import { Camera, User, Lock, BarChart3, CreditCard, DollarSign, Clock, CheckCircle } from 'lucide-vue-next'

// State
const profile = ref<CashierProfile | null>(null)
const stats = ref<CashierStats | null>(null)
const loading = ref(false)
const activeTab = ref('personal')
const selectedStatus = ref('active')

// Form Data
const formData = ref({
  first_name: '',
  last_name: '',
  phone: '',
  shift: '',
  register_number: '',
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
  { id: 'security', label: 'Security', icon: Lock },
  { id: 'statistics', label: 'Statistics', icon: BarChart3 }
]

// Computed
const profilePhotoUrl = computed(() => {
  if (profile.value?.cashier?.profile_photo) {
    return `http://127.0.0.1:8000/storage/${profile.value.cashier.profile_photo}`
  }
  return '/images/avatar.png'
})

const statusColor = computed(() => {
  const status = profile.value?.cashier?.status
  if (status === 'active') return 'bg-green-100 text-green-800'
  if (status === 'on_break') return 'bg-yellow-100 text-yellow-800'
  if (status === 'off_duty') return 'bg-gray-100 text-gray-800'
  return 'bg-gray-100 text-gray-800'
})

// Methods
async function loadProfile() {
  try {
    loading.value = true
    profile.value = await cashierProfileService.getProfile()
    selectedStatus.value = profile.value.cashier?.status || 'active'
    
    // Populate form
    formData.value = {
      first_name: profile.value.first_name,
      last_name: profile.value.last_name,
      phone: profile.value.phone || '',
      shift: profile.value.cashier?.shift || '',
      register_number: profile.value.cashier?.register_number || '',
      bio: profile.value.cashier?.bio || ''
    }
  } catch (error: any) {
    alert(`Error: ${error.response?.data?.message || 'Failed to load profile'}`)
  } finally {
    loading.value = false
  }
}

async function loadStats() {
  try {
    stats.value = await cashierProfileService.getStats()
  } catch (error: any) {
    console.error('Failed to load stats:', error)
  }
}

async function updateProfile() {
  try {
    loading.value = true
    await cashierProfileService.updateProfile(formData.value)
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
  
  // Validate file size (2MB)
  if (file.size > 2 * 1024 * 1024) {
    alert('Error: Photo size must be less than 2MB')
    return
  }
  
  // Validate file type
  if (!file.type.startsWith('image/')) {
    alert('Error: Please upload an image file')
    return
  }
  
  try {
    loading.value = true
    const result = await cashierProfileService.uploadPhoto(file)
    console.log('Photo uploaded:', result)
    
    // Reload profile to get updated photo
    await loadProfile()
    
    alert('Photo uploaded successfully')
  } catch (error: any) {
    console.error('Photo upload error:', error)
    alert(`Error: ${error.response?.data?.message || 'Failed to upload photo'}`)
  } finally {
    loading.value = false
    // Reset file input
    if (target) {
      target.value = ''
    }
  }
}

async function changePassword() {
  if (passwordData.value.new_password !== passwordData.value.new_password_confirmation) {
    alert('Error: Passwords do not match')
    return
  }
  
  try {
    loading.value = true
    await cashierProfileService.changePassword(passwordData.value)
    alert('Password changed successfully')
    
    // Reset form
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

async function updateStatus() {
  try {
    await cashierProfileService.updateStatus(selectedStatus.value as 'active' | 'on_break' | 'off_duty')
    await loadProfile()
    alert('Status updated successfully')
  } catch (error: any) {
    alert(`Error: ${error.response?.data?.message || 'Failed to update status'}`)
  }
}

function resetForm() {
  if (profile.value) {
    formData.value = {
      first_name: profile.value.first_name,
      last_name: profile.value.last_name,
      phone: profile.value.phone || '',
      shift: profile.value.cashier?.shift || '',
      register_number: profile.value.cashier?.register_number || '',
      bio: profile.value.cashier?.bio || ''
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

function formatNumber(num: number | undefined): string {
  if (!num) return '0.00'
  return num.toLocaleString('en-US', { minimumFractionDigits: 2, maximumFractionDigits: 2 })
}

// Lifecycle
onMounted(() => {
  loadProfile()
  loadStats()
})
</script>
