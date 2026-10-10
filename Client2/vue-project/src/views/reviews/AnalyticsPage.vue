<template>
  <div class="analytics-page">
    <div class="max-w-7xl mx-auto">
      <!-- Header -->
      <div class="mb-8">
        <h1 class="text-3xl font-bold text-gray-900 mb-2">Review Analytics</h1>
        <p class="text-gray-600">Analyze review metrics and trends</p>
      </div>

      <!-- Permission Check -->
      <div
        v-if="!isManager"
        class="bg-red-50 border border-red-200 text-red-700 px-4 py-3 rounded-lg mb-6"
      >
        <p class="font-semibold">Access Denied</p>
        <p class="text-sm">You do not have permission to access this page.</p>
      </div>

      <!-- Analytics Dashboard -->
      <div v-else>
        <AnalyticsDashboard />
      </div>
    </div>
  </div>
</template>

<script setup lang="ts">
import { computed } from 'vue'
import { useAuthStore } from '@/stores/auth'
import AnalyticsDashboard from '@/components/reviews/AnalyticsDashboard.vue'

const authStore = useAuthStore()

const isManager = computed(
  () => authStore.can('reviews.view') || authStore.hasAnyRole(['manager', 'admin']),
)
</script>

<style scoped>
.analytics-page {
  min-height: 100vh;
  background: #f9fafb;
  padding: 24px 0;
}
</style>
