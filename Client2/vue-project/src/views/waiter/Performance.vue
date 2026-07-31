<template>
  <DashboardLayout>
    <div class="min-h-screen bg-gradient-to-br from-slate-50 via-blue-50 to-indigo-50 p-6">
      <div class="max-w-7xl mx-auto">
        <h1 class="text-4xl font-bold text-slate-900 mb-8">Performance</h1>
        
        <div v-if="loading" class="flex items-center justify-center py-16">
          <div class="text-center">
            <div class="animate-spin rounded-full h-12 w-12 border-4 border-slate-200 border-t-blue-600 mx-auto mb-4"></div>
            <p class="text-slate-600 font-medium">Loading performance data...</p>
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
