<template>
  <DashboardLayout>
    <div class="min-h-screen bg-gradient-to-br from-slate-50 via-blue-50 to-indigo-50 p-6">
      <div class="max-w-7xl mx-auto">
        <h1 class="text-4xl font-bold text-slate-900 mb-8">My Profile</h1>
        
        <!-- Loading State -->
        <div v-if="loading" class="flex items-center justify-center py-16">
          <div class="text-center">
            <div class="relative w-12 h-12 mx-auto mb-4">
              <svg class="absolute inset-0 w-full h-full" viewBox="0 0 100 100">
                <circle cx="50" cy="50" r="45" fill="none" stroke="#0EA5E9" stroke-width="6" opacity="0.3" />
              </svg>
              <div class="absolute inset-0 animate-spin" style="animation: spin 1.5s linear infinite;">
                <svg viewBox="0 0 100 100" class="w-full h-full">
                  <circle cx="50" cy="50" r="45" fill="none" stroke="#FBBF24" stroke-width="8" stroke-linecap="round" stroke-dasharray="70 280" />
                </svg>
              </div>
            </div>
            <p class="text-slate-700 dark:text-yellow-300 font-semibold text-sm">Loading profile...</p>
          </div>
        </div>

        <div v-else class="bg-white rounded-lg shadow-sm p-8">
          <!-- Profile Header -->
          <div class="flex items-center gap-6 mb-8">
            <div class="w-16 h-16 rounded-full bg-gradient-to-br from-blue-500 to-indigo-600 flex items-center justify-center text-white font-bold text-2xl">
              {{ initials }}
            </div>
            <div>
              <p class="text-2xl font-bold text-slate-900">{{ profile.name }}</p>
              <p class="text-slate-600">{{ profile.email }}</p>
            </div>
          </div>

          <!-- Profile Info -->
          <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
            <div>
              <p class="text-sm text-slate-600 font-semibold">Phone</p>
              <p class="text-slate-900 mt-1">{{ profile.phone || 'N/A' }}</p>
            </div>
            <div>
              <p class="text-sm text-slate-600 font-semibold">Employee Number</p>
              <p class="text-slate-900 mt-1">{{ profile.employee_number || 'N/A' }}</p>
            </div>
            <div>
              <p class="text-sm text-slate-600 font-semibold">Shift</p>
              <p class="text-slate-900 mt-1">{{ profile.shift || 'N/A' }}</p>
            </div>
            <div>
              <p class="text-sm text-slate-600 font-semibold">Status</p>
              <p class="text-slate-900 mt-1">{{ profile.status || 'N/A' }}</p>
            </div>
          </div>
        </div>
      </div>
    </div>
  </DashboardLayout>
</template>

<script setup lang="ts">
import { ref, onMounted, computed } from 'vue'
import DashboardLayout from '@/Layouts/DashboardLayout.vue'
import waiterService from '@/services/waiterService'
import { useAuthStore } from '@/stores/auth'

const loading = ref(true)
const profile = ref({
  name: '',
  email: '',
  phone: '',
  employee_number: '',
  shift: '',
  status: '',
})

const auth = useAuthStore()

const initials = computed(() => {
  return profile.value.name
    .split(' ')
    .map(n => n[0])
    .join('')
    .toUpperCase()
})

onMounted(async () => {
  try {
    loading.value = true
    if (auth.user) {
      profile.value.name = `${auth.user.first_name} ${auth.user.last_name}`
      profile.value.email = auth.user.email
      profile.value.phone = auth.user.phone || 'N/A'
    }
    
    const data = await waiterService.getProfile()
    if (data) {
      profile.value = {
        ...profile.value,
        employee_number: data.employee_number || 'N/A',
        shift: data.shift || 'N/A',
        status: data.status || 'N/A',
      }
    }
  } catch (err: any) {
    console.error('[WaiterProfile] Error:', err)
  } finally {
    loading.value = false
  }
})
</script>
