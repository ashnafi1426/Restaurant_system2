<script setup lang="ts">
import { onMounted, ref } from 'vue'
import DashboardLayout from '../../Layouts/DashboardLayout.vue'
import { Truck, Download } from 'lucide-vue-next'

const isLoading = ref(false)
const deliveryData = ref({
  total_deliveries: 24,
  completed: 18,
  in_progress: 4,
  failed: 2
})

onMounted(async () => {
  isLoading.value = true
  try {
    await new Promise(resolve => setTimeout(resolve, 500))
  } finally {
    isLoading.value = false
  }
})

const generateReport = () => {
  // Generate report logic
  alert('Report generated successfully!')
}
</script>

<template>
  <DashboardLayout>
    <div class="min-h-screen bg-gradient-to-br from-slate-50 via-white to-blue-50/30">
      <!-- PAGE HEADER -->
      <div class="mb-8 border-b border-slate-200/60 bg-white/80 backdrop-blur-sm rounded-lg p-6">
        <div class="flex items-center justify-between">
          <div>
            <h1 class="text-3xl font-bold text-slate-900 mb-2">Room Service</h1>
            <p class="text-slate-600">Track and manage all room service deliveries</p>
          </div>
          <div class="w-12 h-12 bg-gradient-to-br from-teal-100 to-teal-50 rounded-xl flex items-center justify-center">
            <Truck class="w-6 h-6 text-teal-600" />
          </div>
        </div>
      </div>

      <!-- Loading State -->
      <div v-if="isLoading" class="flex justify-center items-center py-32">
        <div class="text-center">
          <div class="animate-spin rounded-full h-12 w-12 border-4 border-slate-200 border-t-blue-600 mx-auto mb-4"></div>
          <p class="text-slate-600">Loading delivery data...</p>
        </div>
      </div>

      <!-- Content -->
      <div v-if="!isLoading" class="space-y-6">
        <!-- Delivery Summary -->
        <div class="grid grid-cols-1 md:grid-cols-4 gap-6">
          <div class="bg-white rounded-lg border border-slate-200/60 shadow-sm p-6 hover:shadow-md transition-shadow">
            <p class="text-sm text-slate-600 font-medium">Total Deliveries</p>
            <h3 class="mt-3 text-3xl font-bold text-slate-900">{{ deliveryData.total_deliveries }}</h3>
            <p class="text-xs text-slate-500 mt-2">Today</p>
          </div>
          <div class="bg-white rounded-lg border border-slate-200/60 shadow-sm p-6 hover:shadow-md transition-shadow">
            <p class="text-sm text-slate-600 font-medium">Completed</p>
            <h3 class="mt-3 text-3xl font-bold text-emerald-600">{{ deliveryData.completed }}</h3>
            <p class="text-xs text-slate-500 mt-2">Successfully delivered</p>
          </div>
          <div class="bg-white rounded-lg border border-slate-200/60 shadow-sm p-6 hover:shadow-md transition-shadow">
            <p class="text-sm text-slate-600 font-medium">In Progress</p>
            <h3 class="mt-3 text-3xl font-bold text-blue-600">{{ deliveryData.in_progress }}</h3>
            <p class="text-xs text-slate-500 mt-2">Being delivered</p>
          </div>
          <div class="bg-white rounded-lg border border-slate-200/60 shadow-sm p-6 hover:shadow-md transition-shadow">
            <p class="text-sm text-slate-600 font-medium">Failed/Cancelled</p>
            <h3 class="mt-3 text-3xl font-bold text-red-600">{{ deliveryData.failed }}</h3>
            <p class="text-xs text-slate-500 mt-2">Issues reported</p>
          </div>
        </div>

        <!-- Action Button -->
        <div class="flex justify-end">
          <button @click="generateReport" class="flex items-center gap-2 px-6 py-3 bg-slate-100 hover:bg-slate-200 text-slate-700 font-semibold rounded-lg transition-all duration-300">
            <Download class="w-4 h-4" />
            Generate Report
          </button>
        </div>

        <!-- Deliveries List -->
        <div class="bg-white rounded-lg border border-slate-200/60 shadow-sm p-6">
          <h2 class="text-xl font-bold text-slate-900 mb-4">Recent Deliveries</h2>
          <div class="space-y-3">
            <div class="flex items-center justify-between p-4 border-b border-slate-200 hover:bg-slate-50 transition">
              <div class="flex-1">
                <p class="font-medium text-slate-900">Room 302 - Coffee Service</p>
                <p class="text-sm text-slate-500">Delivered 15 mins ago</p>
              </div>
              <span class="px-3 py-1 bg-emerald-100 text-emerald-700 text-xs font-semibold rounded-full">Completed</span>
            </div>
            <div class="flex items-center justify-between p-4 border-b border-slate-200 hover:bg-slate-50 transition">
              <div class="flex-1">
                <p class="font-medium text-slate-900">Room 105 - Breakfast Order</p>
                <p class="text-sm text-slate-500">En route - 5 mins away</p>
              </div>
              <span class="px-3 py-1 bg-blue-100 text-blue-700 text-xs font-semibold rounded-full">In Progress</span>
            </div>
            <div class="flex items-center justify-between p-4 hover:bg-slate-50 transition">
              <div class="flex-1">
                <p class="font-medium text-slate-900">Room 415 - Room Service</p>
                <p class="text-sm text-slate-500">Pending assignment</p>
              </div>
              <span class="px-3 py-1 bg-amber-100 text-amber-700 text-xs font-semibold rounded-full">Pending</span>
            </div>
          </div>
        </div>
      </div>
    </div>
  </DashboardLayout>
</template>
