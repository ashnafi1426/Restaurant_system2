<script setup lang="ts">
import { computed } from 'vue'
import { Sparkles, Home, AlertCircle, CheckCircle2 } from 'lucide-vue-next'
import { useManagerStore } from '@/stores/managerStore'

const manager = useManagerStore()

const housekeepingStats = computed(() => {
  const tasks = manager.housekeeping
  return {
    total: tasks.length,
    pending: tasks.filter((t) => t.status === 'pending').length,
    inProgress: tasks.filter((t) => t.status === 'in_progress').length,
    completed: tasks.filter((t) => t.status === 'completed').length,
  }
})

const priorityColor = (priority: string) => {
  switch (priority) {
    case 'high':
      return 'text-red-600 bg-red-50'
    case 'medium':
      return 'text-yellow-600 bg-yellow-50'
    default:
      return 'text-green-600 bg-green-50'
  }
}

const statusIcon = (status: string) => {
  if (status === 'completed') return CheckCircle2
  if (status === 'in_progress') return AlertCircle
  return Home
}
</script>

<template>
  <section class="bg-white rounded-3xl border border-slate-200 shadow-sm p-6">
    <!-- HEADER -->
    <div class="flex justify-between items-center mb-8">
      <div>
        <h2 class="text-xl font-bold">Housekeeping Tasks</h2>
        <p class="text-sm text-slate-500">Room cleaning and maintenance</p>
      </div>
      <div class="w-12 h-12 rounded-2xl bg-emerald-100 flex items-center justify-center">
        <Sparkles class="w-6 h-6 text-emerald-600" />
      </div>
    </div>

    <!-- STATISTICS -->
    <div class="grid grid-cols-1 md:grid-cols-4 gap-4 mb-8">
      <div class="bg-blue-50 rounded-2xl p-4">
        <p class="text-sm text-slate-500">Total Tasks</p>
        <h3 class="text-3xl font-bold text-blue-700 mt-2">{{ housekeepingStats.total }}</h3>
      </div>

      <div class="bg-yellow-50 rounded-2xl p-4">
        <p class="text-sm text-slate-500">Pending</p>
        <h3 class="text-3xl font-bold text-yellow-700 mt-2">{{ housekeepingStats.pending }}</h3>
      </div>

      <div class="bg-orange-50 rounded-2xl p-4">
        <p class="text-sm text-slate-500">In Progress</p>
        <h3 class="text-3xl font-bold text-orange-700 mt-2">{{ housekeepingStats.inProgress }}</h3>
      </div>

      <div class="bg-green-50 rounded-2xl p-4">
        <p class="text-sm text-slate-500">Completed</p>
        <h3 class="text-3xl font-bold text-green-700 mt-2">{{ housekeepingStats.completed }}</h3>
      </div>
    </div>

    <!-- TASKS LIST -->
    <div class="space-y-3">
      <p class="text-sm font-medium text-slate-600">Task Queue</p>

      <div
        v-for="task in manager.housekeeping.slice(0, 6)"
        :key="task.id"
        class="p-4 rounded-lg border border-slate-200 hover:border-slate-300 hover:shadow-md transition"
      >
        <div class="flex items-start justify-between mb-2">
          <div class="flex items-start gap-3 flex-1">
            <div
              :class="[
                'w-8 h-8 rounded-lg flex items-center justify-center text-white flex-shrink-0',
                task.status === 'pending' && 'bg-yellow-500',
                task.status === 'in_progress' && 'bg-orange-500',
                task.status === 'completed' && 'bg-green-500',
              ]"
            >
              <component :is="statusIcon(task.status)" class="w-4 h-4" />
            </div>
            <div>
              <p class="font-medium text-sm">Room {{ task.roomNumber }}</p>
              <p class="text-xs text-slate-500">{{ task.taskType }}</p>
            </div>
          </div>

          <span :class="['px-2 py-1 rounded text-xs font-medium', priorityColor(task.priority)]">
            {{ task.priority.toUpperCase() }}
          </span>
        </div>

        <div class="ml-11 flex items-center justify-between text-xs text-slate-500">
          <span>Assigned: {{ task.assignedTo || 'Unassigned' }}</span>
          <span v-if="task.estimatedTime">ETA: {{ task.estimatedTime }} min</span>
        </div>
      </div>

      <div v-if="manager.housekeeping.length === 0" class="py-8 text-center text-slate-500">
        No housekeeping tasks
      </div>
    </div>
  </section>
</template>
