<script setup lang="ts">
import { computed } from 'vue'
import { Activity, User, FileText, AlertCircle, CheckCircle2, Clock, Edit3 } from 'lucide-vue-next'
import { useManagerStore } from '@/stores/managerStore'

const manager = useManagerStore()

const getActivityIcon = (type: string) => {
  switch (type) {
    case 'check_in':
      return CheckCircle2
    case 'check_out':
      return AlertCircle
    case 'order':
      return FileText
    case 'staff':
      return User
    case 'reservation':
      return Clock
    case 'update':
      return Edit3
    default:
      return Activity
  }
}

const getActivityColor = (type: string) => {
  switch (type) {
    case 'check_in':
      return 'bg-green-100 text-green-700'
    case 'check_out':
      return 'bg-red-100 text-red-700'
    case 'order':
      return 'bg-blue-100 text-blue-700'
    case 'staff':
      return 'bg-purple-100 text-purple-700'
    case 'reservation':
      return 'bg-yellow-100 text-yellow-700'
    case 'update':
      return 'bg-indigo-100 text-indigo-700'
    default:
      return 'bg-slate-100 text-slate-700'
  }
}

const formatTime = (timestamp: string) => {
  const date = new Date(timestamp)
  const now = new Date()
  const diff = now.getTime() - date.getTime()
  const minutes = Math.floor(diff / 60000)
  const hours = Math.floor(diff / 3600000)
  const days = Math.floor(diff / 86400000)

  if (minutes < 60) return `${minutes}m ago`
  if (hours < 24) return `${hours}h ago`
  return `${days}d ago`
}
</script>

<template>
  <section class="bg-white rounded-3xl border border-slate-200 shadow-sm p-6">
    <!-- HEADER -->
    <div class="flex justify-between items-center mb-8">
      <div>
        <h2 class="text-xl font-bold">Recent Activity</h2>
        <p class="text-sm text-slate-500">System activity and events</p>
      </div>
      <div class="w-12 h-12 rounded-2xl bg-indigo-100 flex items-center justify-center">
        <Activity class="w-6 h-6 text-indigo-600" />
      </div>
    </div>

    <!-- ACTIVITY TIMELINE -->
    <div class="space-y-0">
      <div
        v-for="(activity, index) in manager.activities.slice(0, 10)"
        :key="activity.id"
        class="flex gap-4 py-4"
        :class="{ 'border-b': index !== manager.activities.slice(0, 10).length - 1 }"
      >
        <!-- TIMELINE DOT -->
        <div class="flex flex-col items-center flex-shrink-0">
          <div
            :class="[
              'w-10 h-10 rounded-full flex items-center justify-center',
              getActivityColor(activity.type),
            ]"
          >
            <component :is="getActivityIcon(activity.type)" class="w-5 h-5" />
          </div>
          <div
            v-if="index !== manager.activities.slice(0, 10).length - 1"
            class="w-0.5 h-12 bg-slate-200 mt-2"
          ></div>
        </div>

        <!-- ACTIVITY CONTENT -->
        <div class="pt-1 flex-1 min-w-0">
          <div class="flex items-start justify-between gap-4">
            <div class="flex-1">
              <p class="font-medium text-sm text-slate-900">{{ activity.description }}</p>
              <p class="text-xs text-slate-600 mt-1">{{ activity.details }}</p>
            </div>
            <span class="text-xs text-slate-500 whitespace-nowrap">
              {{ formatTime(activity.timestamp) }}
            </span>
          </div>
        </div>
      </div>

      <div v-if="manager.activities.length === 0" class="py-8 text-center text-slate-500">
        No recent activity
      </div>
    </div>

    <!-- VIEW ALL LINK -->
    <div v-if="manager.activities.length > 10" class="mt-4 pt-4 border-t text-center">
      <button class="text-blue-600 hover:text-blue-700 font-medium text-sm">
        View All Activities →
      </button>
    </div>
  </section>
</template>
