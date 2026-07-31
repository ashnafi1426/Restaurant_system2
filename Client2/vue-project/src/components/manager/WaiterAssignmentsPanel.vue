<template>
  <div class="assignments-panel">
    <!-- Header -->
    <div class="panel-header">
      <div class="header-content">
        <h3 class="panel-title">Active Assignments</h3>
        <p class="panel-subtitle">{{ waiterName }}</p>
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
        <p>Loading assignments...</p>
      </div>

      <!-- Error State -->
      <div v-else-if="error" class="error-state">
        <AlertCircle :size="24" />
        <p>{{ error }}</p>
      </div>

      <!-- Empty State -->
      <div v-else-if="assignments.length === 0" class="empty-state">
        <CheckCircle :size="48" />
        <p>No active assignments</p>
        <span>This waiter is ready for new assignments</span>
      </div>

      <!-- Assignments List -->
      <div v-else class="assignments-list">
        <div
          v-for="assignment in assignments"
          :key="assignment.id"
          class="assignment-item"
          :class="{
            'status-pending': assignment.status === 'pending',
            'status-accepted': assignment.status === 'accepted',
            'status-delivery': assignment.status === 'on_delivery',
            'status-delivered': assignment.status === 'delivered',
            'status-failed': assignment.status === 'failed',
          }"
        >
          <!-- Status Badge -->
          <div class="status-badge">
            <span class="status-dot"></span>
            {{ formatStatus(assignment.status) }}
          </div>

          <!-- Assignment Details -->
          <div class="assignment-details">
            <p class="order-id">Order #{{ assignment.order_id?.substring(0, 8) || 'N/A' }}</p>
            <p class="assigned-time">{{ formatTime(assignment.assigned_at) }}</p>

            <!-- Timeline -->
            <div class="timeline">
              <div class="timeline-item" :class="{ completed: assignment.accepted_at }">
                <span class="timeline-dot">✓</span>
                <span class="timeline-label">Accepted</span>
              </div>
              <div class="timeline-item" :class="{ completed: assignment.picked_up_at }">
                <span class="timeline-dot">📦</span>
                <span class="timeline-label">Picked Up</span>
              </div>
              <div class="timeline-item" :class="{ completed: assignment.delivered_at }">
                <span class="timeline-dot">🚚</span>
                <span class="timeline-label">Delivered</span>
              </div>
            </div>
          </div>

          <!-- Remarks -->
          <div v-if="assignment.remarks" class="remarks">
            <p class="remarks-text">{{ assignment.remarks }}</p>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup lang="ts">
import { ref, onMounted } from 'vue'
import { X, AlertCircle, CheckCircle } from 'lucide-vue-next'
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
const assignments = ref<any[]>([])

const formatStatus = (status: string) => {
  const map: Record<string, string> = {
    pending: 'Pending',
    accepted: 'Accepted',
    on_delivery: 'On Delivery',
    delivered: 'Delivered',
    failed: 'Failed',
    rejected: 'Rejected',
  }
  return map[status] || status
}

const formatTime = (date: string | Date) => {
  if (!date) return 'N/A'
  const d = new Date(date)
  return d.toLocaleTimeString([], { hour: '2-digit', minute: '2-digit' })
}

onMounted(async () => {
  try {
    loading.value = true
    const data = await waiterStore.getAssignments(props.waiterId)
    // Filter only active assignments (not completed/failed/rejected)
    assignments.value = (data || []).filter((a: any) =>
      ['pending', 'accepted', 'on_delivery', 'picked_up'].includes(a.status)
    )
  } catch (err: any) {
    error.value = err.message || 'Failed to load assignments'
  } finally {
    loading.value = false
  }
})
</script>

<style scoped>
.assignments-panel {
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
  max-height: 500px;
  overflow-y: auto;
}

.loading-state,
.error-state,
.empty-state {
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
  margin-bottom: 12px;
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

.empty-state {
  color: #999;
}

.empty-state svg {
  margin-bottom: 12px;
  color: #ccc;
}

.empty-state p {
  margin: 8px 0 0 0;
  font-weight: 600;
  color: #2c3e50;
}

.empty-state span {
  font-size: 13px;
  color: #999;
  margin-top: 4px;
}

.assignments-list {
  display: flex;
  flex-direction: column;
  gap: 12px;
}

.assignment-item {
  border: 1px solid #e8eef5;
  border-left: 4px solid #999;
  border-radius: 8px;
  padding: 14px;
  background: #f8f9fa;
  transition: all 0.2s;
}

.assignment-item:hover {
  box-shadow: 0 2px 8px rgba(0, 0, 0, 0.08);
}

.status-pending {
  border-left-color: #ffc107;
  background: #fffbf0;
}

.status-accepted {
  border-left-color: #667eea;
  background: #f0f3ff;
}

.status-delivery {
  border-left-color: #17a2b8;
  background: #f0f7fa;
}

.status-delivered {
  border-left-color: #28a745;
  background: #f0f8f4;
}

.status-failed {
  border-left-color: #dc3545;
  background: #fef5f5;
}

.status-badge {
  display: inline-flex;
  align-items: center;
  gap: 6px;
  font-size: 12px;
  font-weight: 600;
  padding: 4px 8px;
  border-radius: 4px;
  margin-bottom: 8px;
}

.status-pending .status-badge {
  background: #fff3cd;
  color: #856404;
}

.status-accepted .status-badge {
  background: #cfe2ff;
  color: #084298;
}

.status-delivery .status-badge {
  background: #cff4fc;
  color: #055160;
}

.status-delivered .status-badge {
  background: #d1e7dd;
  color: #0f5132;
}

.status-failed .status-badge {
  background: #f8d7da;
  color: #842029;
}

.status-dot {
  width: 6px;
  height: 6px;
  background: currentColor;
  border-radius: 50%;
  display: inline-block;
}

.assignment-details {
  margin-bottom: 8px;
}

.order-id {
  margin: 0;
  font-size: 14px;
  font-weight: 700;
  color: #2c3e50;
}

.assigned-time {
  margin: 4px 0 0 0;
  font-size: 12px;
  color: #999;
}

.timeline {
  display: flex;
  gap: 8px;
  margin-top: 10px;
}

.timeline-item {
  display: flex;
  flex-direction: column;
  align-items: center;
  gap: 4px;
  flex: 1;
  text-align: center;
  opacity: 0.4;
  transition: opacity 0.2s;
}

.timeline-item.completed {
  opacity: 1;
}

.timeline-dot {
  font-size: 16px;
}

.timeline-label {
  font-size: 10px;
  font-weight: 600;
  color: #999;
  text-transform: uppercase;
  letter-spacing: 0.5px;
}

.remarks {
  background: white;
  border: 1px solid #e8eef5;
  border-radius: 6px;
  padding: 8px;
  margin-top: 10px;
}

.remarks-text {
  margin: 0;
  font-size: 12px;
  color: #666;
  font-style: italic;
}
</style>
