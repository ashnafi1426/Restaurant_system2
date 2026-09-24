<script setup lang="ts">
import { onMounted, ref, computed, watch } from 'vue'
import DashboardLayout from '../../Layouts/DashboardLayout.vue'
import { useHotelStore } from '@/stores/hotelStore'
import { useLanguageStore } from '@/stores/language'
import axios from '@/services/axios'
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
  Loader2,
  ChevronLeft,
  ChevronRight,
  Building2,
} from 'lucide-vue-next'

const hotelStore = useHotelStore()
const languageStore = useLanguageStore()
const isLoading = ref(false)
const isFilterOpen = ref(false)
const isFullscreen = ref(false)
const searchQuery = ref('')
const selectedPriority = ref('all')

const operationsData = ref({
  pending_tasks: 0,
  completed_tasks: 0,
  urgent_tasks: 0,
  total_staff: 0,
})

const tasksList = ref<any[]>([])

const filteredTasks = computed(() => {
  let list = tasksList.value
  if (selectedPriority.value !== 'all') {
    list = list.filter((t) => t.priority.toLowerCase() === selectedPriority.value.toLowerCase())
  }
  if (searchQuery.value.trim()) {
    const q = searchQuery.value.toLowerCase().trim()
    list = list.filter((t) => t.title.toLowerCase().includes(q) || t.area.toLowerCase().includes(q))
  }
  return list
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

const refreshData = async () => {
  isLoading.value = true
  try {
    const [statsRes, tasksRes] = await Promise.all([
      axios.get('/manager/dashboard/statistics').catch((err) => {
        console.error('[DailyOperations] Failed to load dashboard statistics:', err)
        return null
      }),
      axios.get('/manager/operations/housekeeping').catch((err) => {
        console.error('[DailyOperations] Failed to load housekeeping operations:', err)
        return null
      }),
    ])

    const stats = statsRes?.data?.data || {}
    const tasks = tasksRes?.data?.data || []

    const pendingCount = tasks.filter((t: any) => (t.status || '').toLowerCase() === 'pending').length
    const completedCount = tasks.filter((t: any) => (t.status || '').toLowerCase() === 'completed').length
    const urgentCount = tasks.filter((t: any) => (t.priority || '').toLowerCase() === 'urgent' || (t.priority || '').toLowerCase() === 'high').length

    operationsData.value = {
      pending_tasks: pendingCount || stats.pending_orders_count || 0,
      completed_tasks: completedCount || stats.total_orders || 0,
      urgent_tasks: urgentCount || 0,
      total_staff: stats.total_waiters || 0,
    }

    if (Array.isArray(tasks) && tasks.length > 0) {
      tasksList.value = tasks.map((t: any, idx: number) => ({
        id: t.id || idx + 1,
        title: t.title || t.task_description || t.task || 'Room Service Task',
        area: t.area || (t.room ? `Room ${t.room.room_number}` : 'Hotel Facility'),
        priority: t.priority || 'Normal',
        status: t.status ? t.status.charAt(0).toUpperCase() + t.status.slice(1) : 'Pending',
        time: t.created_at ? new Date(t.created_at).toLocaleTimeString([], { hour: '2-digit', minute: '2-digit' }) : '—',
      }))
    } else {
      tasksList.value = []
    }
  } catch (err) {
    console.error('Failed to load operations data:', err)
  } finally {
    isLoading.value = false
  }
}

onMounted(async () => {
  await refreshData()
})

watch(() => hotelStore.hotelId, async () => {
  await refreshData()
})
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
            <component :is="isFilterOpen ? X : Filter" class="w-4 h-4" />
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
            <component :is="isFullscreen ? Minimize2 : Maximize2" class="w-4 h-4" />
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
              <tr v-if="filteredTasks.length === 0">
                <td colspan="5" class="py-12 text-center text-slate-400 dark:text-slate-500">
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
