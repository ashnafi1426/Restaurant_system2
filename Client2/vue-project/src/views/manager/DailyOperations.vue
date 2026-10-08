<script setup lang="ts">
import { onMounted, ref, computed, watch } from 'vue'
import DashboardLayout from '@/Layouts/DashboardLayout.vue'
import { useHotelStore } from '@/stores/hotelStore'
import { useLanguageStore } from '@/stores/language'
import axios from '@/services/axios'
import { getErrorMessage } from '@/utils/error'
import {
  ClipboardList,
  AlertCircle,
  CheckCircle,
  Clock,
  Search,
  Filter,
  X,
  RefreshCw,
  Maximize2,
  Minimize2,
  RotateCcw,
  Building2,
  Loader2,
} from 'lucide-vue-next'

interface Task {
  id: string | number
  title: string
  area: string
  priority: string
  status: string
  time: string
}

interface OperationsData {
  pending_tasks: number
  completed_tasks: number
  urgent_tasks: number
  total_staff: number
}

const hotelStore = useHotelStore()
const languageStore = useLanguageStore()

const isLoading = ref(false)
const errorMessage = ref<string | null>(null)
const isFilterOpen = ref(false)
const isFullscreen = ref(false)
const searchQuery = ref('')
const selectedPriority = ref('all')

const operationsData = ref<OperationsData>({
  pending_tasks: 0,
  completed_tasks: 0,
  urgent_tasks: 0,
  total_staff: 0,
})

const tasksList = ref<Task[]>([])

const filteredTasks = computed(() => {
  const priority = selectedPriority.value.toLowerCase()
  const q = searchQuery.value.trim().toLowerCase()

  return tasksList.value.filter((task) => {
    const matchesPriority = priority === 'all' || task.priority.toLowerCase() === priority
    const matchesSearch = !q || task.title.toLowerCase().includes(q) || task.area.toLowerCase().includes(q)
    return matchesPriority && matchesSearch
  })
})

const toggleFilter = () => {
  isFilterOpen.value = !isFilterOpen.value
}

const toggleFullscreen = () => {
  isFullscreen.value = !isFullscreen.value
}

const resetFilters = () => {
  searchQuery.value = ''
  selectedPriority.value = 'all'
}

const formatTask = (raw: any, idx: number): Task => {
  const status = raw.status ? raw.status.charAt(0).toUpperCase() + raw.status.slice(1) : 'Pending'
  const time = raw.created_at
    ? new Date(raw.created_at).toLocaleTimeString([], { hour: '2-digit', minute: '2-digit' })
    : '—'
  const area = raw.area || (raw.room ? `Room ${raw.room.room_number}` : 'Hotel Facility')
  const title = raw.title || raw.task_description || raw.task || 'Room Service Task'

  return {
    id: raw.id || idx + 1,
    title,
    area,
    priority: raw.priority || 'Normal',
    status,
    time,
  }
}

const refreshData = async () => {
  isLoading.value = true
  errorMessage.value = null
  try {
    const [statsRes, tasksRes] = await Promise.all([
      axios.get('/manager/dashboard/statistics'),
      axios.get('/manager/operations/housekeeping'),
    ])

    const stats = statsRes?.data?.data || {}
    const rawTasks = Array.isArray(tasksRes?.data?.data) ? tasksRes.data.data : []

    const tasks = rawTasks.map(formatTask)
    tasksList.value = tasks

    const pendingCount = tasks.filter((t) => t.status.toLowerCase() === 'pending').length
    const completedCount = tasks.filter((t) => t.status.toLowerCase() === 'completed').length
    const urgentCount = tasks.filter((t) => ['urgent', 'high'].includes(t.priority.toLowerCase())).length

    operationsData.value = {
      pending_tasks: pendingCount || stats.pending_orders_count || 0,
      completed_tasks: completedCount || stats.total_orders || 0,
      urgent_tasks: urgentCount,
      total_staff: stats.total_waiters || 0,
    }
  } catch (err: any) {
    console.error('[DailyOperations] Failed to load operations data:', err)
    errorMessage.value = getErrorMessage(err, 'Failed to load operations data. Please try again.')
  } finally {
    isLoading.value = false
  }
}

onMounted(refreshData)
watch(() => hotelStore.hotelId, refreshData)
</script>

<template>
  <DashboardLayout>
    <div
      class="space-y-6 bg-slate-50 dark:bg-slate-950 min-h-screen p-4 sm:p-6 max-w-full overflow-hidden font-sans"
      :class="{ 'fixed inset-0 z-50 p-6 overflow-y-auto bg-white dark:bg-slate-950': isFullscreen }"
    >
      <!-- Header -->
      <div class="bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 p-5 sm:p-6 rounded-2xl shadow-xs flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
        <div class="flex items-center gap-3">
          <div class="w-10 h-10 rounded-xl bg-gradient-to-br from-purple-600 to-purple-700 flex items-center justify-center shadow-md flex-shrink-0 text-white">
            <ClipboardList class="w-5 h-5 stroke-[2.2]" />
          </div>
          <div>
            <div class="flex items-center gap-2">
              <h1 class="text-xl sm:text-2xl font-black text-slate-900 dark:text-white">
                {{ languageStore.t('daily_operations', 'Daily Operations') }}
              </h1>
              <span v-if="hotelStore.hotelName" class="inline-flex items-center gap-1.5 px-2.5 py-0.5 rounded-full text-xs font-bold bg-blue-100 dark:bg-blue-900/30 text-blue-800 dark:text-blue-300 border border-blue-200 dark:border-blue-700/50">
                <Building2 class="w-3 h-3" />
                {{ hotelStore.hotelName }}
              </span>
            </div>
            <p class="text-xs text-slate-500 dark:text-slate-400 mt-0.5">
              {{ languageStore.t('daily_operations_desc', 'Manage daily floor tasks, checklists, and operations status.') }}
            </p>
          </div>
        </div>
      </div>

      <!-- Stats Grid -->
      <div class="grid grid-cols-2 md:grid-cols-4 gap-4">
        <div class="bg-white dark:bg-slate-900 rounded-2xl p-5 shadow-xs border border-slate-200 dark:border-slate-800 flex items-center justify-between">
          <div>
            <p class="text-[10px] font-bold text-slate-500 dark:text-slate-400 uppercase tracking-wider">
              {{ languageStore.t('pending_tasks', 'Pending Tasks') }}
            </p>
            <h3 class="text-2xl font-black text-amber-600 dark:text-amber-400 mt-1">{{ operationsData.pending_tasks }}</h3>
          </div>
          <div class="p-3 rounded-2xl bg-amber-500/10 text-amber-600 dark:text-amber-400">
            <Clock class="w-5 h-5" />
          </div>
        </div>

        <div class="bg-white dark:bg-slate-900 rounded-2xl p-5 shadow-xs border border-slate-200 dark:border-slate-800 flex items-center justify-between">
          <div>
            <p class="text-[10px] font-bold text-slate-500 dark:text-slate-400 uppercase tracking-wider">
              {{ languageStore.t('completed', 'Completed') }}
            </p>
            <h3 class="text-2xl font-black text-emerald-600 dark:text-emerald-400 mt-1">{{ operationsData.completed_tasks }}</h3>
          </div>
          <div class="p-3 rounded-2xl bg-emerald-500/10 text-emerald-600 dark:text-emerald-400">
            <CheckCircle class="w-5 h-5" />
          </div>
        </div>

        <div class="bg-white dark:bg-slate-900 rounded-2xl p-5 shadow-xs border border-slate-200 dark:border-slate-800 flex items-center justify-between">
          <div>
            <p class="text-[10px] font-bold text-slate-500 dark:text-slate-400 uppercase tracking-wider">
              {{ languageStore.t('urgent_attention', 'Urgent Attention') }}
            </p>
            <h3 class="text-2xl font-black text-rose-600 dark:text-rose-400 mt-1">{{ operationsData.urgent_tasks }}</h3>
          </div>
          <div class="p-3 rounded-2xl bg-rose-500/10 text-rose-600 dark:text-rose-400">
            <AlertCircle class="w-5 h-5" />
          </div>
        </div>

        <div class="bg-white dark:bg-slate-900 rounded-2xl p-5 shadow-xs border border-slate-200 dark:border-slate-800 flex items-center justify-between">
          <div>
            <p class="text-[10px] font-bold text-slate-500 dark:text-slate-400 uppercase tracking-wider">
              {{ languageStore.t('Staff On Duty', 'Staff On Duty') }}
            </p>
            <h3 class="text-2xl font-black text-blue-600 dark:text-blue-400 mt-1">{{ operationsData.total_staff }}</h3>
          </div>
          <div class="p-3 rounded-2xl bg-blue-500/10 text-blue-600 dark:text-blue-400">
            <ClipboardList class="w-5 h-5" />
          </div>
        </div>
      </div>

      <!-- Top Bar Toolbar -->
      <div
        class="flex flex-wrap items-center justify-between gap-3 rounded-2xl border border-slate-200 dark:border-slate-800/80 bg-white dark:bg-[#0b1527] p-3 sm:p-4 shadow-xs transition-all"
      >
        <!-- Left: Search & Filter Toggle -->
        <div class="flex flex-1 items-center gap-2.5 min-w-[280px] max-w-2xl">
          <!-- Search Input -->
          <div class="relative flex-1">
            <Search class="w-4 h-4 absolute left-3.5 top-1/2 -translate-y-1/2 text-slate-400 dark:text-slate-500" />
            <input
              v-model="searchQuery"
              type="text"
              :placeholder="languageStore.t('search_daily_tasks', 'Search daily tasks or areas...')"
              class="w-full rounded-xl border border-slate-200 dark:border-[#1e3455] bg-slate-50/80 dark:bg-[#13233c] text-slate-900 dark:text-white placeholder-slate-400 dark:placeholder-slate-500 pl-10 pr-4 py-2.5 text-xs sm:text-sm focus:ring-2 focus:ring-blue-500/40 focus:border-blue-500 dark:focus:border-blue-400 transition outline-none"
            />
          </div>

          <!-- Filter Toggle Button -->
          <button
            type="button"
            @click="toggleFilter"
            class="inline-flex items-center gap-2 rounded-xl px-4 py-2.5 text-xs sm:text-sm font-semibold transition cursor-pointer flex-shrink-0"
            :class="[
              isFilterOpen
                ? 'bg-blue-600/10 dark:bg-blue-900/30 text-blue-600 dark:text-blue-400 border border-blue-500/40'
                : 'border border-slate-200 dark:border-[#1e3455] bg-white dark:bg-[#13233c]/80 text-slate-700 dark:text-slate-300 hover:bg-slate-100 dark:hover:bg-[#1c3356]'
            ]"
          >
            <X v-if="isFilterOpen" class="w-4 h-4" />
            <Filter v-else class="w-4 h-4" />
            <span>{{ isFilterOpen ? languageStore.t('hide_filters', 'Hide Filter') : languageStore.t('filter', 'Filter') }}</span>
          </button>
        </div>

        <!-- Right: Action Buttons -->
        <div class="flex items-center gap-2 sm:gap-2.5">
          <!-- Refresh Button -->
          <button
            type="button"
            @click="refreshData"
            :disabled="isLoading"
            :title="languageStore.t('refresh', 'Refresh')"
            class="inline-flex items-center justify-center h-9 w-9 sm:h-10 sm:w-10 rounded-xl border border-slate-200 dark:border-[#1e3455] bg-white dark:bg-[#13233c]/80 text-slate-600 dark:text-slate-300 hover:bg-slate-100 dark:hover:bg-[#1c3356] hover:text-slate-900 dark:hover:text-white transition disabled:opacity-50 cursor-pointer"
          >
            <RefreshCw class="w-4 h-4" :class="{ 'animate-spin': isLoading }" />
          </button>

          <!-- Fullscreen Toggle -->
          <button
            type="button"
            @click="toggleFullscreen"
            :title="languageStore.t('toggle_fullscreen', 'Toggle Fullscreen')"
            class="inline-flex items-center justify-center h-9 w-9 sm:h-10 sm:w-10 rounded-xl border border-slate-200 dark:border-[#1e3455] bg-white dark:bg-[#13233c]/80 text-slate-600 dark:text-slate-300 hover:bg-slate-100 dark:hover:bg-[#1c3356] hover:text-slate-900 dark:hover:text-white transition cursor-pointer"
          >
            <Minimize2 v-if="isFullscreen" class="w-4 h-4" />
            <Maximize2 v-else class="w-4 h-4" />
          </button>
        </div>
      </div>

      <!-- Expandable Filter Panel -->
      <Transition
        enter-active-class="transition duration-200 ease-out"
        enter-from-class="transform -translate-y-2 opacity-0 scale-98"
        enter-to-class="transform translate-y-0 opacity-100 scale-100"
        leave-active-class="transition duration-150 ease-in"
        leave-from-class="transform translate-y-0 opacity-100 scale-100"
        leave-to-class="transform -translate-y-2 opacity-0 scale-98"
      >
        <div
          v-if="isFilterOpen"
          class="rounded-2xl border border-slate-200 dark:border-slate-800/80 bg-white dark:bg-[#0b1527] p-4 sm:p-5 shadow-sm space-y-4"
        >
          <div class="grid grid-cols-1 sm:grid-cols-2 gap-3.5 sm:gap-4">
            <!-- Priority Filter -->
            <div>
              <label class="mb-1.5 block text-xs font-semibold text-slate-600 dark:text-slate-300">
                {{ languageStore.t('task_priority', 'Task Priority') }}
              </label>
              <select
                v-model="selectedPriority"
                class="w-full rounded-xl border border-slate-200 dark:border-[#1e3455] bg-slate-50/80 dark:bg-[#13233c] text-slate-900 dark:text-white px-3.5 py-2 text-xs sm:text-sm focus:ring-2 focus:ring-blue-500/40 focus:border-blue-500 dark:focus:border-blue-400 transition cursor-pointer font-medium outline-none"
              >
                <option value="all">{{ languageStore.t('all_priorities', 'All Priorities') }}</option>
                <option value="high">{{ languageStore.t('high_priority', 'High Priority') }}</option>
                <option value="normal">{{ languageStore.t('normal', 'Normal') }}</option>
              </select>
            </div>

            <!-- Reset Filters -->
            <div class="flex items-end">
              <button
                type="button"
                @click="resetFilters"
                class="w-full inline-flex items-center justify-center gap-1.5 rounded-xl border border-slate-200 dark:border-[#1e3455] bg-slate-100/70 dark:bg-[#13233c] px-3.5 py-2 text-xs font-semibold text-slate-700 dark:text-slate-300 hover:bg-slate-200 dark:hover:bg-[#1c3356] transition cursor-pointer"
              >
                <RotateCcw class="w-3.5 h-3.5" />
                <span>{{ languageStore.t('reset_filters', 'Reset Filters') }}</span>
              </button>
            </div>
          </div>
        </div>
      </Transition>

      <!-- Error State Banner -->
      <div
        v-if="errorMessage"
        class="rounded-2xl border border-rose-200 dark:border-rose-900/50 bg-rose-50 dark:bg-rose-950/30 p-4 flex items-center justify-between gap-3 text-rose-700 dark:text-rose-300 text-xs sm:text-sm font-semibold"
      >
        <div class="flex items-center gap-2.5">
          <AlertCircle class="w-5 h-5 text-rose-500 shrink-0" />
          <span>{{ errorMessage }}</span>
        </div>
        <button
          type="button"
          @click="refreshData"
          class="px-3 py-1.5 rounded-lg bg-rose-600 hover:bg-rose-700 text-white text-xs font-bold transition cursor-pointer shrink-0"
        >
          {{ languageStore.t('retry', 'Retry') }}
        </button>
      </div>

      <!-- Tasks Table -->
      <div class="rounded-2xl border border-slate-200 dark:border-slate-800 bg-white dark:bg-slate-900 shadow-xs overflow-hidden font-sans w-full">
        <div class="overflow-x-auto w-full">
          <table class="w-full text-left border-collapse">
            <thead class="bg-slate-50/90 dark:bg-[#0c182c] border-b border-slate-200 dark:border-[#1e3455]">
              <tr class="text-[11px] font-bold text-slate-500 dark:text-slate-400 select-none">
                <th class="py-3 px-4 pl-5 whitespace-nowrap">{{ languageStore.t('task_description', 'Task Description') }}</th>
                <th class="py-3 px-4 whitespace-nowrap">{{ languageStore.t('area_floor', 'Area / Floor') }}</th>
                <th class="py-3 px-4 text-center whitespace-nowrap">{{ languageStore.t('priority', 'Priority') }}</th>
                <th class="py-3 px-4 text-center whitespace-nowrap">{{ languageStore.t('status', 'Status') }}</th>
                <th class="py-3 px-4 text-right pr-5 whitespace-nowrap">{{ languageStore.t('scheduled_time', 'Scheduled Time') }}</th>
              </tr>
            </thead>
            <tbody class="divide-y divide-slate-100 dark:divide-[#1e3455]/60 text-xs">
              <!-- Loading Row -->
              <tr v-if="isLoading">
                <td colspan="5" class="py-16 text-center">
                  <div class="flex flex-col items-center justify-center gap-3">
                    <Loader2 class="w-7 h-7 text-blue-600 dark:text-blue-400 animate-spin" />
                    <span class="text-xs font-bold text-slate-500">{{ languageStore.t('loading_tasks', 'Loading tasks...') }}</span>
                  </div>
                </td>
              </tr>
              <!-- Empty State Row -->
              <tr v-else-if="filteredTasks.length === 0">
                <td colspan="5" class="py-12 text-center text-slate-400 dark:text-slate-500 font-medium">
                  {{ languageStore.t('no_tasks_found', 'No operations tasks match your criteria.') }}
                </td>
              </tr>
              <tr
                v-for="task in filteredTasks"
                :key="task.id"
                class="hover:bg-slate-50/80 dark:hover:bg-[#13233c]/60 transition-colors duration-150 group"
              >
                <td class="py-3 px-4 pl-5 whitespace-nowrap font-bold text-slate-900 dark:text-white">
                  {{ task.title }}
                </td>
                <td class="py-3 px-4 whitespace-nowrap text-slate-600 dark:text-slate-400 font-medium">
                  {{ task.area }}
                </td>
                <td class="py-3 px-4 text-center whitespace-nowrap">
                  <span
                    class="inline-flex items-center px-2.5 py-0.5 rounded-full text-[10px] font-extrabold border uppercase tracking-wider"
                    :class="task.priority === 'High' ? 'bg-rose-500/10 text-rose-600 border-rose-500/20' : 'bg-blue-500/10 text-blue-600 border-blue-500/20'"
                  >
                    {{ task.priority }}
                  </span>
                </td>
                <td class="py-3 px-4 text-center whitespace-nowrap">
                  <span
                    class="inline-flex items-center px-2.5 py-0.5 rounded-full text-[10px] font-bold"
                    :class="task.status === 'Completed' ? 'bg-emerald-500/10 text-emerald-600' : 'bg-amber-500/10 text-amber-600'"
                  >
                    {{ task.status }}
                  </span>
                </td>
                <td class="py-3 px-4 text-right pr-5 whitespace-nowrap text-slate-600 dark:text-slate-400 font-mono font-medium">
                  {{ task.time }}
                </td>
              </tr>
            </tbody>
          </table>
        </div>
      </div>
    </div>
  </DashboardLayout>
</template>
