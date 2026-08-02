<script setup lang="ts">
import { onMounted, ref } from 'vue'
import DashboardLayout from '../../Layouts/DashboardLayout.vue'
import { BarChart3, TrendingUp } from 'lucide-vue-next'

const isLoading = ref(false)
const analyticsData = ref({
  revenue_today: 42850,
  orders_completed: 156,
  customer_satisfaction: 94,
  avg_delivery_time: 18
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

<template>
  <DashboardLayout>
    <div class="min-h-screen bg-gradient-to-br from-slate-50 via-white to-blue-50/30">
      <!-- PAGE HEADER -->
      <div class="mb-8 border-b border-slate-200/60 bg-white/80 backdrop-blur-sm rounded-lg p-6">
        <div class="flex items-center justify-between">
          <div>
            <h1 class="text-3xl font-bold text-slate-900 mb-2">Reports</h1>
            <p class="text-slate-600">View analytics and reports</p>
          </div>
          <div class="w-12 h-12 bg-gradient-to-br from-indigo-100 to-indigo-50 rounded-xl flex items-center justify-center">
            <BarChart3 class="w-6 h-6 text-indigo-600" />
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
          <p class="text-slate-700 dark:text-yellow-300 font-semibold text-sm mt-4">Loading analytics data...</p>
        </div>
      </div>

      <!-- Content -->
      <div v-if="!isLoading" class="space-y-6">
        <!-- Key Metrics -->
        <div class="grid grid-cols-1 md:grid-cols-4 gap-6">
          <div class="bg-white rounded-lg border border-slate-200/60 shadow-sm p-6 hover:shadow-md transition-shadow">
            <div class="flex items-center justify-between">
              <div>
                <p class="text-sm text-slate-600 font-medium">Revenue Today</p>
                <h3 class="mt-3 text-3xl font-bold text-emerald-600">${{ analyticsData.revenue_today.toLocaleString() }}</h3>
              </div>
              <TrendingUp class="w-8 h-8 text-emerald-500 opacity-20" />
            </div>
          </div>
          <div class="bg-white rounded-lg border border-slate-200/60 shadow-sm p-6 hover:shadow-md transition-shadow">
            <p class="text-sm text-slate-600 font-medium">Orders Completed</p>
            <h3 class="mt-3 text-3xl font-bold text-blue-600">{{ analyticsData.orders_completed }}</h3>
            <p class="text-xs text-slate-500 mt-2">Total orders</p>
          </div>
          <div class="bg-white rounded-lg border border-slate-200/60 shadow-sm p-6 hover:shadow-md transition-shadow">
            <p class="text-sm text-slate-600 font-medium">Satisfaction Rate</p>
            <h3 class="mt-3 text-3xl font-bold text-purple-600">{{ analyticsData.customer_satisfaction }}%</h3>
            <p class="text-xs text-slate-500 mt-2">Customer feedback</p>
          </div>
          <div class="bg-white rounded-lg border border-slate-200/60 shadow-sm p-6 hover:shadow-md transition-shadow">
            <p class="text-sm text-slate-600 font-medium">Avg Delivery Time</p>
            <h3 class="mt-3 text-3xl font-bold text-orange-600">{{ analyticsData.avg_delivery_time }} min</h3>
            <p class="text-xs text-slate-500 mt-2">Average</p>
          </div>
        </div>

        <!-- Performance Overview -->
        <div class="bg-white rounded-lg border border-slate-200/60 shadow-sm p-6">
          <h2 class="text-xl font-bold text-slate-900 mb-6">Performance Metrics</h2>
          <div class="space-y-4">
            <div>
              <div class="flex justify-between mb-2">
                <span class="text-sm font-medium text-slate-700">Staff Efficiency</span>
                <span class="text-sm font-semibold text-slate-900">88%</span>
              </div>
              <div class="h-2 bg-slate-200 rounded-full overflow-hidden">
                <div class="h-full bg-gradient-to-r from-blue-500 to-blue-600" style="width: 88%"></div>
              </div>
            </div>
            <div>
              <div class="flex justify-between mb-2">
                <span class="text-sm font-medium text-slate-700">Order Accuracy</span>
                <span class="text-sm font-semibold text-slate-900">95%</span>
              </div>
              <div class="h-2 bg-slate-200 rounded-full overflow-hidden">
                <div class="h-full bg-gradient-to-r from-emerald-500 to-emerald-600" style="width: 95%"></div>
              </div>
            </div>
            <div>
              <div class="flex justify-between mb-2">
                <span class="text-sm font-medium text-slate-700">Delivery On-Time</span>
                <span class="text-sm font-semibold text-slate-900">92%</span>
              </div>
              <div class="h-2 bg-slate-200 rounded-full overflow-hidden">
                <div class="h-full bg-gradient-to-r from-purple-500 to-purple-600" style="width: 92%"></div>
              </div>
            </div>
          </div>
        </div>

        <!-- Recent Activity -->
        <div class="bg-white rounded-lg border border-slate-200/60 shadow-sm p-6">
          <h2 class="text-xl font-bold text-slate-900 mb-4">Recent Activity</h2>
          <div class="space-y-3">
            <div class="flex items-center justify-between p-3 bg-slate-50 rounded-lg">
              <span class="text-sm text-slate-700">Peak hours: 12:00 PM - 1:30 PM</span>
              <span class="text-xs font-semibold text-slate-500">2 hours ago</span>
            </div>
            <div class="flex items-center justify-between p-3 bg-slate-50 rounded-lg">
              <span class="text-sm text-slate-700">New waiter assigned to Floor 2</span>
              <span class="text-xs font-semibold text-slate-500">30 mins ago</span>
            </div>
            <div class="flex items-center justify-between p-3 bg-slate-50 rounded-lg">
              <span class="text-sm text-slate-700">Daily revenue target exceeded</span>
              <span class="text-xs font-semibold text-emerald-600">15 mins ago</span>
            </div>
          </div>
        </div>
      </div>
    </div>
  </DashboardLayout>
</template>

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
