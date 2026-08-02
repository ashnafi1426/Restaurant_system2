<script setup lang="ts">
import { onMounted, ref } from 'vue'
import DashboardLayout from '../../Layouts/DashboardLayout.vue'
import { ClipboardList, AlertCircle, CheckCircle } from 'lucide-vue-next'

const isLoading = ref(false)
const operationsData = ref({
  pending_tasks: 8,
  completed_tasks: 24,
  urgent_tasks: 2,
  total_staff: 12
})

onMounted(async () => {
  isLoading.value = true
  try {
    await new Promise(resolve => setTimeout(resolve, 500))
  } finally {
    isLoading.value = false
  }
})
</script>

<style scoped>
@keyframes spin {
  from {
    transform: rotate(0deg);
  }
  to {
    transform: rotate(360deg);
  }
}

.animate-spin {
  animation: spin 1.5s linear infinite;
}
</style>

<template>
  <DashboardLayout>
    <div class="min-h-screen bg-gradient-to-br from-slate-50 via-white to-blue-50/30">
      <!-- PAGE HEADER -->
      <div class="mb-8 border-b border-slate-200/60 bg-white/80 backdrop-blur-sm rounded-lg p-6">
        <div class="flex items-center justify-between">
          <div>
            <h1 class="text-3xl font-bold text-slate-900 mb-2">Daily Operations</h1>
            <p class="text-slate-600">Manage daily operations and tasks</p>
          </div>
          <div class="w-12 h-12 bg-gradient-to-br from-purple-100 to-purple-50 rounded-xl flex items-center justify-center">
            <ClipboardList class="w-6 h-6 text-purple-600" />
          </div>
        </div>
      </div>

      <!-- Loading State -->
      <div v-if="isLoading" class="flex justify-center items-center py-32">
        <div class="text-center">
          <div class="relative w-12 h-12">
            <svg class="absolute inset-0 w-full h-full" viewBox="0 0 100 100">
              <circle cx="50" cy="50" r="45" fill="none" stroke="#0EA5E9" stroke-width="6" opacity="0.3" />
            </svg>
            <div class="absolute inset-0 animate-spin" style="animation: spin 1.5s linear infinite;">
              <svg viewBox="0 0 100 100" class="w-full h-full">
                <circle cx="50" cy="50" r="45" fill="none" stroke="#FBBF24" stroke-width="8" stroke-linecap="round" stroke-dasharray="70 280" />
              </svg>
            </div>
          </div>
          <p class="text-slate-700 dark:text-yellow-300 font-semibold text-sm mt-4">Loading operations data...</p>
        </div>
      </div>

      <!-- Content -->
      <div v-if="!isLoading" class="space-y-6">
        <!-- Operations Stats -->
        <div class="grid grid-cols-1 md:grid-cols-4 gap-6">
          <div class="bg-white rounded-lg border border-slate-200/60 shadow-sm p-6 hover:shadow-md transition-shadow">
            <p class="text-sm text-slate-600 font-medium">Pending Tasks</p>
            <h3 class="mt-3 text-3xl font-bold text-amber-600">{{ operationsData.pending_tasks }}</h3>
            <p class="text-xs text-slate-500 mt-2">Awaiting attention</p>
          </div>
          <div class="bg-white rounded-lg border border-slate-200/60 shadow-sm p-6 hover:shadow-md transition-shadow">
            <p class="text-sm text-slate-600 font-medium">Completed</p>
            <h3 class="mt-3 text-3xl font-bold text-emerald-600">{{ operationsData.completed_tasks }}</h3>
            <p class="text-xs text-slate-500 mt-2">Today</p>
          </div>
          <div class="bg-white rounded-lg border border-slate-200/60 shadow-sm p-6 hover:shadow-md transition-shadow">
            <p class="text-sm text-slate-600 font-medium">Urgent</p>
            <h3 class="mt-3 text-3xl font-bold text-red-600">{{ operationsData.urgent_tasks }}</h3>
            <p class="text-xs text-slate-500 mt-2">Require immediate action</p>
          </div>
          <div class="bg-white rounded-lg border border-slate-200/60 shadow-sm p-6 hover:shadow-md transition-shadow">
            <p class="text-sm text-slate-600 font-medium">Staff On Duty</p>
            <h3 class="mt-3 text-3xl font-bold text-blue-600">{{ operationsData.total_staff }}</h3>
            <p class="text-xs text-slate-500 mt-2">Active staff</p>
          </div>
        </div>

        <!-- Operations Overview -->
        <div class="bg-white rounded-lg border border-slate-200/60 shadow-sm p-6">
          <h2 class="text-xl font-bold text-slate-900 mb-4">Operations Overview</h2>
          <div class="space-y-4">
            <div class="flex items-center justify-between p-4 bg-slate-50 rounded-lg">
              <div class="flex items-center gap-3">
                <AlertCircle class="w-5 h-5 text-amber-600" />
                <div>
                  <p class="font-medium text-slate-900">Morning Briefing</p>
                  <p class="text-sm text-slate-500">Scheduled for 08:00 AM</p>
                </div>
              </div>
              <span class="px-3 py-1 bg-amber-100 text-amber-700 text-xs font-semibold rounded-full">Pending</span>
            </div>
            <div class="flex items-center justify-between p-4 bg-slate-50 rounded-lg">
              <div class="flex items-center gap-3">
                <CheckCircle class="w-5 h-5 text-emerald-600" />
                <div>
                  <p class="font-medium text-slate-900">Staff Shift Assignment</p>
                  <p class="text-sm text-slate-500">Completed at 07:30 AM</p>
                </div>
              </div>
              <span class="px-3 py-1 bg-emerald-100 text-emerald-700 text-xs font-semibold rounded-full">Completed</span>
            </div>
            <div class="flex items-center justify-between p-4 bg-slate-50 rounded-lg">
              <div class="flex items-center gap-3">
                <AlertCircle class="w-5 h-5 text-red-600" />
                <div>
                  <p class="font-medium text-slate-900">Equipment Maintenance Check</p>
                  <p class="text-sm text-slate-500">Overdue - requires immediate action</p>
                </div>
              </div>
              <span class="px-3 py-1 bg-red-100 text-red-700 text-xs font-semibold rounded-full">Urgent</span>
            </div>
          </div>
        </div>
      </div>
    </div>
  </DashboardLayout>
</template>
