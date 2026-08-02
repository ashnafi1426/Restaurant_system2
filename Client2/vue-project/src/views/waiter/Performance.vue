<template>
  <DashboardLayout>
    <div class="min-h-screen bg-gradient-to-br from-slate-50 via-blue-50 to-indigo-50 p-6">
      <div class="max-w-7xl mx-auto">
        <h1 class="text-4xl font-bold text-slate-900 mb-8">Performance</h1>
        
        <div v-if="loading" class="flex items-center justify-center py-16">
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
            <p class="text-slate-700 dark:text-yellow-300 font-semibold text-sm mt-4">Loading performance data...</p>
          </div>
        </div>

        <div v-else>
          <!-- Performance Stats -->
          <div class="grid grid-cols-1 md:grid-cols-3 gap-6 mb-8">
            <div class="bg-white rounded-lg shadow-sm p-6">
              <p class="text-slate-600 text-sm">Total Deliveries</p>
              <p class="text-3xl font-bold text-slate-900 mt-2">{{ stats.totalDeliveries || 0 }}</p>
            </div>
            <div class="bg-white rounded-lg shadow-sm p-6">
              <p class="text-slate-600 text-sm">Success Rate</p>
              <p class="text-3xl font-bold text-slate-900 mt-2">{{ stats.successRate || 0 }}%</p>
            </div>
            <div class="bg-white rounded-lg shadow-sm p-6">
              <p class="text-slate-600 text-sm">Avg Rating</p>
              <p class="text-3xl font-bold text-slate-900 mt-2">{{ stats.avgRating || 0 }}/5</p>
            </div>
          </div>
        </div>
      </div>
    </div>
  </DashboardLayout>
</template>

<script setup lang="ts">
import { ref, onMounted } from 'vue'
import DashboardLayout from '@/Layouts/DashboardLayout.vue'
import waiterService from '@/services/waiterService'

const loading = ref(true)
const stats = ref({
  totalDeliveries: 0,
  successRate: 0,
  avgRating: 0,
})

onMounted(async () => {
  try {
    loading.value = true
    const data = await waiterService.getPerformance()
    if (data) {
      stats.value = {
        totalDeliveries: data.total_deliveries || 0,
        successRate: data.success_rate || 0,
        avgRating: data.avg_rating || 0,
      }
    }
  } catch (err: any) {
    console.error('[Performance] Error:', err)
  } finally {
    loading.value = false
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
