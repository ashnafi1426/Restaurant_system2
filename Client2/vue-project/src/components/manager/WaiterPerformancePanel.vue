<template>
  <div class="waiter-performance-panel">
    <!-- Header -->
    <div class="panel-header">
      <div class="header-content">
        <h3 class="panel-title">Performance Metrics</h3>
        <p class="panel-subtitle">Waiter: {{ waiterName }}</p>
      </div>
      <button @click="$emit('close')" class="btn-close">
        <X :size="20" />
      </button>
    </div>

    <!-- Content -->
    <div class="panel-body">
      <!-- Loading State -->
      <div v-if="loading" class="loading-state">
        <div class="spinner"></div>
        <p>Loading performance data...</p>
      </div>

      <!-- Error State -->
      <div v-else-if="error" class="error-state">
        <AlertCircle :size="24" />
        <p>{{ error }}</p>
      </div>

      <!-- Performance Data -->
      <div v-else class="performance-data">
        <!-- Summary Cards -->
        <div class="metrics-grid">
          <div class="metric-card">
            <span class="metric-label">Acceptance Rate</span>
            <span class="metric-value">{{ latestMetric?.acceptance_rate || 0 }}%</span>
            <div class="metric-bar">
              <div class="metric-fill" :style="{ width: (latestMetric?.acceptance_rate || 0) + '%' }"></div>
            </div>
          </div>

          <div class="metric-card">
            <span class="metric-label">Completion Rate</span>
            <span class="metric-value">{{ latestMetric?.completion_rate || 0 }}%</span>
            <div class="metric-bar">
              <div class="metric-fill" :style="{ width: (latestMetric?.completion_rate || 0) + '%' }"></div>
            </div>
          </div>

          <div class="metric-card">
            <span class="metric-label">On-Time Rate</span>
            <span class="metric-value">{{ latestMetric?.on_time_rate || 0 }}%</span>
            <div class="metric-bar">
              <div class="metric-fill" :style="{ width: (latestMetric?.on_time_rate || 0) + '%' }"></div>
            </div>
          </div>

          <div class="metric-card">
            <span class="metric-label">Guest Rating</span>
            <span class="metric-value">{{ latestMetric?.guest_rating || 'N/A' }}</span>
            <div class="metric-bar">
              <div class="metric-fill" :style="{ width: (latestMetric?.guest_rating ? (latestMetric.guest_rating * 20) : 0) + '%' }"></div>
            </div>
          </div>
        </div>

        <!-- Detailed Stats -->
        <div class="detailed-stats">
          <h4>Today's Statistics</h4>
          <div class="stats-grid">
            <div class="stat-item">
              <span class="stat-label">Assignments</span>
              <span class="stat-value">{{ latestMetric?.assigned || 0 }}</span>
            </div>
            <div class="stat-item">
              <span class="stat-label">Accepted</span>
              <span class="stat-value text-green-600">{{ latestMetric?.accepted || 0 }}</span>
            </div>
            <div class="stat-item">
              <span class="stat-label">Rejected</span>
              <span class="stat-value text-red-600">{{ latestMetric?.rejected || 0 }}</span>
            </div>
            <div class="stat-item">
              <span class="stat-label">Completed</span>
              <span class="stat-value text-blue-600">{{ latestMetric?.completed || 0 }}</span>
            </div>
            <div class="stat-item">
              <span class="stat-label">Failed</span>
              <span class="stat-value text-orange-600">{{ latestMetric?.failed || 0 }}</span>
            </div>
            <div class="stat-item">
              <span class="stat-label">Avg Time</span>
              <span class="stat-value">{{ latestMetric?.avg_time_minutes || 0 }}min</span>
            </div>
          </div>
        </div>

        <!-- Performance Chart (Simple visualization) -->
        <div class="performance-trend" v-if="performanceHistory.length > 0">
          <h4>Last 7 Days Trend</h4>
          <div class="trend-chart">
            <div
              v-for="(metric, index) in performanceHistory.slice(-7)"
              :key="index"
              class="trend-bar"
              :style="{ height: (metric.performance_score * 20) + '%' }"
              :title="`${metric.date}: ${metric.performance_score.toFixed(2)} score`"
            ></div>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup lang="ts">
import { ref, computed, onMounted } from 'vue'
import { X, AlertCircle } from 'lucide-vue-next'
import { useManagerWaiterStore } from '@/stores/manager/waiterStore'

const props = defineProps<{
  waiterId: string
  waiterName: string
}>()

const emit = defineEmits<{
  (e: 'close'): void
}>()

const waiterStore = useManagerWaiterStore()
const loading = ref(false)
const error = ref('')
const performanceHistory = ref<any[]>([])

const latestMetric = computed(() => performanceHistory.value[0])

onMounted(async () => {
  try {
    loading.value = true
    performanceHistory.value = await waiterStore.getPerformance(props.waiterId)
  } catch (err: any) {
    error.value = err.message || 'Failed to load performance data'
  } finally {
    loading.value = false
  }
})
</script>

<style scoped>
.waiter-performance-panel {
  background: white;
  border-radius: 12px;
  border: 1px solid #e8eef5;
  overflow: hidden;
  box-shadow: 0 4px 12px rgba(0, 0, 0, 0.08);
}

.panel-header {
  background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
  color: white;
  padding: 20px;
  display: flex;
  justify-content: space-between;
  align-items: center;
}

.header-content {
  flex: 1;
}

.panel-title {
  margin: 0;
  font-size: 18px;
  font-weight: 700;
}

.panel-subtitle {
  margin: 4px 0 0 0;
  font-size: 13px;
  opacity: 0.9;
}

.btn-close {
  background: rgba(255, 255, 255, 0.2);
  border: none;
  color: white;
  cursor: pointer;
  padding: 8px;
  border-radius: 6px;
  display: flex;
  align-items: center;
  justify-content: center;
  transition: all 0.2s;
}

.btn-close:hover {
  background: rgba(255, 255, 255, 0.3);
}

.panel-body {
  padding: 24px;
}

.loading-state,
.error-state {
  display: flex;
  flex-direction: column;
  align-items: center;
  justify-content: center;
  padding: 40px 20px;
  text-align: center;
}

.spinner {
  width: 40px;
  height: 40px;
  border: 3px solid #e8eef5;
  border-top-color: #667eea;
  border-radius: 50%;
  animation: spin 1s linear infinite;
}

@keyframes spin {
  to { transform: rotate(360deg); }
}

.error-state {
  color: #e74c3c;
}

.error-state svg {
  margin-bottom: 12px;
}

.performance-data {
  display: flex;
  flex-direction: column;
  gap: 24px;
}

.metrics-grid {
  display: grid;
  grid-template-columns: repeat(auto-fit, minmax(150px, 1fr));
  gap: 16px;
}

.metric-card {
  background: #f8f9fa;
  border: 1px solid #e8eef5;
  border-radius: 10px;
  padding: 14px;
  display: flex;
  flex-direction: column;
  gap: 8px;
}

.metric-label {
  font-size: 12px;
  font-weight: 600;
  color: #666;
  text-transform: uppercase;
  letter-spacing: 0.5px;
}

.metric-value {
  font-size: 24px;
  font-weight: 700;
  color: #667eea;
}

.metric-bar {
  width: 100%;
  height: 4px;
  background: #e8eef5;
  border-radius: 2px;
  overflow: hidden;
}

.metric-fill {
  height: 100%;
  background: linear-gradient(90deg, #667eea 0%, #764ba2 100%);
  border-radius: 2px;
  transition: width 0.3s ease;
}

.detailed-stats {
  background: #f8f9fa;
  border-radius: 10px;
  padding: 16px;
}

.detailed-stats h4 {
  margin: 0 0 12px 0;
  font-size: 13px;
  font-weight: 700;
  color: #2c3e50;
  text-transform: uppercase;
  letter-spacing: 0.5px;
}

.stats-grid {
  display: grid;
  grid-template-columns: repeat(auto-fit, minmax(100px, 1fr));
  gap: 12px;
}

.stat-item {
  display: flex;
  flex-direction: column;
  gap: 4px;
}

.stat-label {
  font-size: 11px;
  font-weight: 600;
  color: #999;
  text-transform: uppercase;
}

.stat-value {
  font-size: 16px;
  font-weight: 700;
  color: #2c3e50;
}

.performance-trend {
  background: #f8f9fa;
  border-radius: 10px;
  padding: 16px;
}

.performance-trend h4 {
  margin: 0 0 16px 0;
  font-size: 13px;
  font-weight: 700;
  color: #2c3e50;
  text-transform: uppercase;
  letter-spacing: 0.5px;
}

.trend-chart {
  display: flex;
  align-items: flex-end;
  justify-content: space-around;
  height: 120px;
  gap: 4px;
}

.trend-bar {
  flex: 1;
  background: linear-gradient(180deg, #667eea 0%, #764ba2 100%);
  border-radius: 4px 4px 0 0;
  min-height: 4px;
  opacity: 0.8;
  transition: opacity 0.2s;
  cursor: pointer;
}

.trend-bar:hover {
  opacity: 1;
}

@media (max-width: 640px) {
  .metrics-grid {
    grid-template-columns: repeat(2, 1fr);
  }

  .stats-grid {
    grid-template-columns: repeat(3, 1fr);
  }
}
</style>
