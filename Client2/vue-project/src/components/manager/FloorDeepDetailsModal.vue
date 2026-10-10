<script setup lang="ts">
import { ref, computed, watch } from 'vue'
import {
  X,
  ChevronLeft,
  ChevronRight,
  Hotel,
  Users,
  UserPlus,
  BedDouble,
  CheckCircle2,
  Clock,
  Phone,
  Mail,
  Trash2,
  Shield,
  Layers,
  Sparkles,
  RefreshCw,
  Edit2,
  ToggleLeft,
  ToggleRight,
  AlertCircle,
  Building2,
  DollarSign,
} from 'lucide-vue-next'
import floorManagementService, {
  type Floor,
  type FloorRoom,
  type FloorWaiterAssignment,
  type FloorStats,
} from '@/services/manager/floorManagementService'
import floorAssignmentService from '@/services/manager/floorAssignmentService'
import { useLanguageStore } from '@/stores/language'

interface Props {
  isOpen: boolean
  floor: Floor | null
  floorsList?: Floor[]
}

interface Emits {
  (e: 'close'): void
  (e: 'assign-staff', floor: Floor): void
  (e: 'edit-floor', floor: Floor): void
  (e: 'refresh'): void
}

const props = withDefaults(defineProps<Props>(), {
  isOpen: false,
  floor: null,
  floorsList: () => [],
})

const emit = defineEmits<Emits>()
const languageStore = useLanguageStore()

const activeTab = ref<'waiters' | 'rooms' | 'stats'>('waiters')
const isLoadingDetails = ref(false)
const detailedFloor = ref<Floor | null>(null)
const floorStats = ref<FloorStats | null>(null)
const isUnassigning = ref<string | null>(null)

// Current index in floors list for stepper navigation
const currentIndex = computed(() => {
  if (!props.floorsList || props.floorsList.length === 0 || !props.floor) return -1
  return props.floorsList.findIndex((f) => String(f.id) === String(props.floor?.id))
})

const hasPrev = computed(() => currentIndex.value > 0)
const hasNext = computed(
  () => currentIndex.value !== -1 && currentIndex.value < props.floorsList.length - 1,
)

const currentDisplayFloor = computed(() => detailedFloor.value || props.floor)

// Fetch deep floor details (rooms, waiter assignments, stats)
const loadDeepFloorDetails = async (floorId: string) => {
  if (!floorId) return
  isLoadingDetails.value = true
  try {
    const [floorRes, statsRes] = await Promise.all([
      floorManagementService.getFloor(floorId),
      floorManagementService.getFloorStats(floorId).catch(() => null),
    ])
    detailedFloor.value = floorRes || null
    floorStats.value = statsRes || null
  } catch (err) {
    console.warn('[FloorDeepDetails] Error fetching details:', err)
  } finally {
    isLoadingDetails.value = false
  }
}

watch(
  () => props.floor?.id,
  (newId) => {
    if (newId && props.isOpen) {
      detailedFloor.value = null
      loadDeepFloorDetails(newId)
    }
  },
  { immediate: true },
)

const handlePrevFloor = () => {
  if (!hasPrev.value) return
  const prev = props.floorsList[currentIndex.value - 1]
  if (prev) {
    detailedFloor.value = null
    loadDeepFloorDetails(prev.id)
  }
}

const handleNextFloor = () => {
  if (!hasNext.value) return
  const next = props.floorsList[currentIndex.value + 1]
  if (next) {
    detailedFloor.value = null
    loadDeepFloorDetails(next.id)
  }
}

const assignedWaiters = computed<FloorWaiterAssignment[]>(() => {
  const f = currentDisplayFloor.value
  if (!f) return []
  return f.waiter_assignments || []
})

const roomsList = computed<FloorRoom[]>(() => {
  const f = currentDisplayFloor.value
  if (!f) return []
  return f.rooms || []
})

// Metrics
const totalRoomsCount = computed(() => {
  if (roomsList.value.length > 0) return roomsList.value.length
  return (
    floorStats.value?.total_rooms ||
    currentDisplayFloor.value?.room_count ||
    currentDisplayFloor.value?.total_rooms ||
    0
  )
})

const occupiedRoomsCount = computed(() => {
  if (roomsList.value.length > 0) {
    return roomsList.value.filter((r) => r.status === 'occupied').length
  }
  return floorStats.value?.occupied_rooms || 0
})

const availableRoomsCount = computed(() => {
  if (roomsList.value.length > 0) {
    return roomsList.value.filter((r) => r.status === 'available').length
  }
  return (
    floorStats.value?.available_rooms ||
    Math.max(0, totalRoomsCount.value - occupiedRoomsCount.value)
  )
})

const unassignWaiter = async (assignmentId: string) => {
  if (
    !confirm(
      languageStore.t(
        'confirm_unassign_waiter',
        'Are you sure you want to remove this waiter from this floor?',
      ),
    )
  ) {
    return
  }
  isUnassigning.value = assignmentId
  try {
    await floorAssignmentService.deleteAssignment(assignmentId)
    if (currentDisplayFloor.value?.id) {
      await loadDeepFloorDetails(currentDisplayFloor.value.id)
    }
    emit('refresh')
  } catch (err: any) {
    alert(err?.response?.data?.message || err?.message || 'Failed to remove assignment')
  } finally {
    isUnassigning.value = null
  }
}

const toggleFloorStatus = async () => {
  if (!currentDisplayFloor.value) return
  const target = currentDisplayFloor.value
  try {
    if (target.is_active) {
      await floorManagementService.deactivateFloor(target.id)
    } else {
      await floorManagementService.activateFloor(target.id)
    }
    await loadDeepFloorDetails(target.id)
    emit('refresh')
  } catch (err: any) {
    alert(err?.response?.data?.message || 'Failed to toggle status')
  }
}

const getPriorityClass = (priority?: string) => {
  const p = (priority || '').toLowerCase()
  if (p === 'primary') return 'bg-blue-500/10 text-blue-600 dark:text-blue-400 border-blue-500/30'
  if (p === 'secondary')
    return 'bg-amber-500/10 text-amber-600 dark:text-amber-400 border-amber-500/30'
  return 'bg-slate-500/10 text-slate-600 dark:text-slate-400 border-slate-500/30'
}

const getRoomStatusClass = (status?: string) => {
  const s = (status || '').toLowerCase()
  if (s === 'occupied') return 'bg-blue-500/10 text-blue-600 dark:text-blue-400 border-blue-500/30'
  if (s === 'maintenance')
    return 'bg-rose-500/10 text-rose-600 dark:text-rose-400 border-rose-500/30'
  if (s === 'cleaning')
    return 'bg-amber-500/10 text-amber-600 dark:text-amber-400 border-amber-500/30'
  return 'bg-emerald-500/10 text-emerald-600 dark:text-emerald-400 border-emerald-500/30'
}
</script>

<template>
  <div
    v-if="isOpen && currentDisplayFloor"
    class="fixed inset-0 z-50 overflow-y-auto bg-slate-900/60 backdrop-blur-xs flex items-center justify-center p-3 sm:p-5 animate-in fade-in duration-200"
  >
    <div
      class="bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-3xl w-full max-w-4xl shadow-2xl overflow-hidden flex flex-col max-h-[92vh]"
      @click.stop
    >
      <!-- Modal Header -->
      <div
        class="p-5 sm:p-6 border-b border-slate-100 dark:border-slate-800 bg-slate-50/70 dark:bg-slate-950/40 flex items-center justify-between gap-4"
      >
        <div class="flex items-center gap-3.5">
          <div
            class="w-12 h-12 rounded-2xl bg-gradient-to-br from-blue-600 to-indigo-600 flex items-center justify-center text-white shadow-md shadow-blue-500/20 font-black text-sm"
          >
            L{{ currentDisplayFloor.floor_number }}
          </div>
          <div>
            <div class="flex items-center gap-2.5 flex-wrap">
              <h2 class="text-lg sm:text-xl font-black text-slate-900 dark:text-white capitalize">
                {{ currentDisplayFloor.name }}
              </h2>
              <span
                class="px-2.5 py-0.5 rounded-full text-[11px] font-bold border"
                :class="
                  currentDisplayFloor.is_active
                    ? 'bg-emerald-500/10 text-emerald-600 dark:text-emerald-400 border-emerald-500/30'
                    : 'bg-slate-500/10 text-slate-600 dark:text-slate-400 border-slate-500/30'
                "
              >
                {{
                  currentDisplayFloor.is_active
                    ? languageStore.t('active', 'Active')
                    : languageStore.t('inactive', 'Inactive')
                }}
              </span>
            </div>
            <p class="text-xs text-slate-500 dark:text-slate-400 mt-0.5">
              {{
                currentDisplayFloor.description ||
                languageStore.t(
                  'floor_deep_desc',
                  'Complete breakdown of rooms, assigned waiters, and floor status.',
                )
              }}
            </p>
          </div>
        </div>

        <!-- Stepper & Close Actions -->
        <div class="flex items-center gap-2">
          <!-- Previous / Next Stepper -->
          <div
            v-if="props.floorsList && props.floorsList.length > 1"
            class="flex items-center border border-slate-200 dark:border-slate-700 rounded-xl overflow-hidden bg-white dark:bg-slate-800 shadow-xs"
          >
            <button
              @click="handlePrevFloor"
              :disabled="!hasPrev"
              :title="languageStore.t('prev_floor', 'Previous Floor')"
              class="p-2 hover:bg-slate-100 dark:hover:bg-slate-700 text-slate-600 dark:text-slate-300 disabled:opacity-30 disabled:cursor-not-allowed cursor-pointer transition"
            >
              <ChevronLeft class="w-4 h-4" />
            </button>
            <span class="text-[11px] font-bold px-2 text-slate-500 dark:text-slate-400 select-none">
              {{ currentIndex + 1 }} / {{ props.floorsList.length }}
            </span>
            <button
              @click="handleNextFloor"
              :disabled="!hasNext"
              :title="languageStore.t('next_floor', 'Next Floor')"
              class="p-2 hover:bg-slate-100 dark:hover:bg-slate-700 text-slate-600 dark:text-slate-300 disabled:opacity-30 disabled:cursor-not-allowed cursor-pointer transition"
            >
              <ChevronRight class="w-4 h-4" />
            </button>
          </div>

          <!-- Close -->
          <button
            @click="emit('close')"
            class="w-9 h-9 rounded-xl flex items-center justify-center text-slate-400 hover:text-slate-700 dark:hover:text-white hover:bg-slate-100 dark:hover:bg-slate-800 transition cursor-pointer"
          >
            <X class="w-5 h-5" />
          </button>
        </div>
      </div>

      <!-- Quick Metrics Strip -->
      <div
        class="grid grid-cols-2 sm:grid-cols-4 gap-3 p-4 sm:px-6 bg-white dark:bg-slate-900 border-b border-slate-100 dark:border-slate-800/80"
      >
        <div
          class="bg-slate-50 dark:bg-slate-800/50 p-3 rounded-2xl border border-slate-100 dark:border-slate-800"
        >
          <p
            class="text-[10px] font-bold uppercase tracking-wider text-slate-500 dark:text-slate-400"
          >
            {{ languageStore.t('total_rooms', 'Total Rooms') }}
          </p>
          <div class="flex items-center justify-between mt-1">
            <span class="text-xl font-black text-slate-900 dark:text-white">{{
              totalRoomsCount
            }}</span>
            <BedDouble class="w-4 h-4 text-slate-400" />
          </div>
        </div>

        <div
          class="bg-emerald-500/5 dark:bg-emerald-950/20 p-3 rounded-2xl border border-emerald-500/20"
        >
          <p
            class="text-[10px] font-bold uppercase tracking-wider text-emerald-600 dark:text-emerald-400"
          >
            {{ languageStore.t('available_rooms', 'Available Rooms') }}
          </p>
          <div class="flex items-center justify-between mt-1">
            <span class="text-xl font-black text-emerald-600 dark:text-emerald-400">{{
              availableRoomsCount
            }}</span>
            <CheckCircle2 class="w-4 h-4 text-emerald-500" />
          </div>
        </div>

        <div class="bg-blue-500/5 dark:bg-blue-950/20 p-3 rounded-2xl border border-blue-500/20">
          <p
            class="text-[10px] font-bold uppercase tracking-wider text-blue-600 dark:text-blue-400"
          >
            {{ languageStore.t('occupied_rooms', 'Occupied Rooms') }}
          </p>
          <div class="flex items-center justify-between mt-1">
            <span class="text-xl font-black text-blue-600 dark:text-blue-400">{{
              occupiedRoomsCount
            }}</span>
            <Hotel class="w-4 h-4 text-blue-500" />
          </div>
        </div>

        <div
          class="bg-purple-500/5 dark:bg-purple-950/20 p-3 rounded-2xl border border-purple-500/20"
        >
          <p
            class="text-[10px] font-bold uppercase tracking-wider text-purple-600 dark:text-purple-400"
          >
            {{ languageStore.t('assigned_waiters', 'Assigned Waiters') }}
          </p>
          <div class="flex items-center justify-between mt-1">
            <span class="text-xl font-black text-purple-600 dark:text-purple-400">{{
              assignedWaiters.length
            }}</span>
            <Users class="w-4 h-4 text-purple-500" />
          </div>
        </div>
      </div>

      <!-- Navigation Tabs -->
      <div
        class="flex items-center gap-2 px-6 pt-3 border-b border-slate-100 dark:border-slate-800 bg-white dark:bg-slate-900"
      >
        <button
          @click="activeTab = 'waiters'"
          class="pb-3 px-3 text-xs sm:text-sm font-bold border-b-2 transition flex items-center gap-2 cursor-pointer"
          :class="
            activeTab === 'waiters'
              ? 'border-blue-600 text-blue-600 dark:text-blue-400'
              : 'border-transparent text-slate-500 hover:text-slate-800 dark:hover:text-slate-200'
          "
        >
          <Users class="w-4 h-4" />
          <span
            >{{ languageStore.t('assigned_waiters', 'Assigned Waiters') }} ({{
              assignedWaiters.length
            }})</span
          >
        </button>

        <button
          @click="activeTab = 'rooms'"
          class="pb-3 px-3 text-xs sm:text-sm font-bold border-b-2 transition flex items-center gap-2 cursor-pointer"
          :class="
            activeTab === 'rooms'
              ? 'border-blue-600 text-blue-600 dark:text-blue-400'
              : 'border-transparent text-slate-500 hover:text-slate-800 dark:hover:text-slate-200'
          "
        >
          <BedDouble class="w-4 h-4" />
          <span
            >{{ languageStore.t('rooms_on_floor', 'Rooms on Floor') }} ({{
              roomsList.length
            }})</span
          >
        </button>

        <button
          @click="activeTab = 'stats'"
          class="pb-3 px-3 text-xs sm:text-sm font-bold border-b-2 transition flex items-center gap-2 cursor-pointer"
          :class="
            activeTab === 'stats'
              ? 'border-blue-600 text-blue-600 dark:text-blue-400'
              : 'border-transparent text-slate-500 hover:text-slate-800 dark:hover:text-slate-200'
          "
        >
          <Layers class="w-4 h-4" />
          <span>{{ languageStore.t('floor_stats', 'Floor Stats & Health') }}</span>
        </button>
      </div>

      <!-- Tab Content Area (Scrollable) -->
      <div class="p-6 overflow-y-auto flex-1 space-y-6">
        <!-- TAB 1: Assigned Waiters One by One -->
        <div v-if="activeTab === 'waiters'" class="space-y-4">
          <div class="flex items-center justify-between gap-3">
            <div>
              <h3 class="text-sm font-black text-slate-900 dark:text-white">
                {{
                  languageStore.t('waiters_assigned_this_floor', 'Waiters Assigned to this Floor')
                }}
              </h3>
              <p class="text-xs text-slate-500 dark:text-slate-400">
                {{
                  languageStore.t(
                    'waiters_assigned_desc',
                    'Staff members currently on active duty and coverage on this floor.',
                  )
                }}
              </p>
            </div>
            <button
              @click="emit('assign-staff', currentDisplayFloor)"
              class="px-3.5 py-2 rounded-xl bg-blue-600 hover:bg-blue-700 text-white font-bold text-xs shadow-xs transition flex items-center gap-1.5 cursor-pointer"
            >
              <UserPlus class="w-3.5 h-3.5 stroke-[2.5]" />
              <span>{{ languageStore.t('assign_waiter', 'Assign Waiter') }}</span>
            </button>
          </div>

          <!-- Empty State -->
          <div
            v-if="assignedWaiters.length === 0"
            class="text-center py-12 border-2 border-dashed border-slate-200 dark:border-slate-800 rounded-2xl"
          >
            <Users class="w-10 h-10 text-slate-400 mx-auto mb-2 opacity-50" />
            <h4 class="text-sm font-bold text-slate-700 dark:text-slate-300">
              {{ languageStore.t('no_waiters_assigned', 'No Waiters Assigned to this Floor') }}
            </h4>
            <p class="text-xs text-slate-500 dark:text-slate-400 max-w-sm mx-auto mt-1 mb-4">
              {{
                languageStore.t(
                  'no_waiters_assigned_hint',
                  'Assign servers or service staff to ensure guest room service coverage.',
                )
              }}
            </p>
            <button
              @click="emit('assign-staff', currentDisplayFloor)"
              class="px-4 py-2 rounded-xl bg-blue-50 dark:bg-blue-950/40 text-blue-600 dark:text-blue-400 hover:bg-blue-100 font-bold text-xs transition cursor-pointer border border-blue-200 dark:border-blue-800"
            >
              {{ languageStore.t('assign_now', 'Assign Staff Now') }}
            </button>
          </div>

          <!-- Waiter Cards List -->
          <div v-else class="grid grid-cols-1 md:grid-cols-2 gap-3.5">
            <div
              v-for="wa in assignedWaiters"
              :key="wa.id"
              class="p-4 rounded-2xl border border-slate-200 dark:border-slate-800 bg-white dark:bg-slate-900 shadow-xs hover:border-blue-500/40 transition-all flex flex-col justify-between gap-3 group"
            >
              <div class="flex items-start justify-between gap-3">
                <div class="flex items-center gap-3">
                  <div
                    class="w-10 h-10 rounded-xl bg-gradient-to-br from-indigo-500 to-purple-600 text-white font-black text-sm flex items-center justify-center flex-shrink-0 shadow-xs"
                  >
                    {{ (wa.waiter_name?.[0] || 'W').toUpperCase() }}
                  </div>
                  <div>
                    <h4 class="text-sm font-black text-slate-900 dark:text-white capitalize">
                      {{ wa.waiter_name }}
                    </h4>
                    <span
                      class="inline-block mt-0.5 px-2 py-0.5 rounded-full text-[10px] font-black uppercase tracking-wider border"
                      :class="getPriorityClass(wa.priority)"
                    >
                      {{ wa.priority }} Coverage
                    </span>
                  </div>
                </div>

                <!-- Unassign button -->
                <button
                  @click="unassignWaiter(wa.id)"
                  :disabled="isUnassigning === wa.id"
                  class="p-2 rounded-xl text-slate-400 hover:text-rose-600 hover:bg-rose-50 dark:hover:bg-rose-950/30 transition cursor-pointer"
                  :title="languageStore.t('unassign_staff', 'Remove Staff from Floor')"
                >
                  <Trash2 class="w-4 h-4" :class="{ 'animate-spin': isUnassigning === wa.id }" />
                </button>
              </div>

              <!-- Details Grid -->
              <div
                class="grid grid-cols-2 gap-2 text-xs text-slate-600 dark:text-slate-400 pt-2 border-t border-slate-100 dark:border-slate-800/80"
              >
                <div v-if="wa.shift" class="flex items-center gap-1.5">
                  <Clock class="w-3.5 h-3.5 text-blue-500" />
                  <span class="truncate"
                    >{{ wa.shift.name }}: {{ wa.shift.start_time?.slice(0, 5) }} -
                    {{ wa.shift.end_time?.slice(0, 5) }}</span
                  >
                </div>
                <div v-else class="flex items-center gap-1.5">
                  <Clock class="w-3.5 h-3.5 text-slate-400" />
                  <span>General Shift</span>
                </div>

                <div v-if="wa.phone" class="flex items-center gap-1.5">
                  <Phone class="w-3.5 h-3.5 text-emerald-500" />
                  <span class="truncate">{{ wa.phone }}</span>
                </div>
                <div v-else-if="wa.email" class="flex items-center gap-1.5">
                  <Mail class="w-3.5 h-3.5 text-indigo-500" />
                  <span class="truncate">{{ wa.email }}</span>
                </div>
              </div>
            </div>
          </div>
        </div>

        <!-- TAB 2: Rooms on this Floor -->
        <div v-if="activeTab === 'rooms'" class="space-y-4">
          <div class="flex items-center justify-between gap-3">
            <div>
              <h3 class="text-sm font-black text-slate-900 dark:text-white">
                {{ languageStore.t('guest_rooms_on_floor', 'Guest Rooms on this Floor') }}
              </h3>
              <p class="text-xs text-slate-500 dark:text-slate-400">
                {{
                  languageStore.t(
                    'guest_rooms_desc',
                    'List of physical hotel rooms and current occupancy status.',
                  )
                }}
              </p>
            </div>
            <router-link
              to="/admin/rooms/create"
              class="px-3.5 py-2 rounded-xl bg-slate-100 dark:bg-slate-800 hover:bg-slate-200 dark:hover:bg-slate-700 text-slate-700 dark:text-slate-200 font-bold text-xs transition flex items-center gap-1.5 cursor-pointer border border-slate-200 dark:border-slate-700"
            >
              <BedDouble class="w-3.5 h-3.5" />
              <span>{{ languageStore.t('add_room', 'Add Room') }}</span>
            </router-link>
          </div>

          <!-- Empty State -->
          <div
            v-if="roomsList.length === 0"
            class="text-center py-12 border-2 border-dashed border-slate-200 dark:border-slate-800 rounded-2xl"
          >
            <BedDouble class="w-10 h-10 text-slate-400 mx-auto mb-2 opacity-50" />
            <h4 class="text-sm font-bold text-slate-700 dark:text-slate-300">
              {{ languageStore.t('no_rooms_found', 'No Rooms Assigned to this Floor') }}
            </h4>
            <p class="text-xs text-slate-500 dark:text-slate-400 max-w-sm mx-auto mt-1 mb-4">
              {{
                languageStore.t(
                  'no_rooms_hint',
                  'Create or reassign hotel rooms to Floor #' +
                    currentDisplayFloor.floor_number +
                    ' from Room Management.',
                )
              }}
            </p>
            <router-link
              to="/admin/rooms"
              class="inline-block px-4 py-2 rounded-xl bg-blue-50 dark:bg-blue-950/40 text-blue-600 dark:text-blue-400 font-bold text-xs transition border border-blue-200 dark:border-blue-800"
            >
              {{ languageStore.t('manage_rooms', 'Go to Room Management') }}
            </router-link>
          </div>

          <!-- Rooms Grid -->
          <div v-else class="grid grid-cols-2 sm:grid-cols-3 md:grid-cols-4 gap-3">
            <div
              v-for="room in roomsList"
              :key="room.id"
              class="p-3.5 rounded-2xl border border-slate-200 dark:border-slate-800 bg-white dark:bg-slate-900 shadow-xs hover:shadow-md transition-all flex flex-col justify-between"
            >
              <div class="flex items-center justify-between gap-2 mb-2">
                <span class="font-black text-sm text-slate-900 dark:text-white">
                  Room {{ room.room_number }}
                </span>
                <span
                  class="px-2 py-0.5 rounded-full text-[10px] font-bold border capitalize"
                  :class="getRoomStatusClass(room.status)"
                >
                  {{ room.status }}
                </span>
              </div>

              <div class="text-xs text-slate-500 dark:text-slate-400">
                <p class="font-medium truncate">{{ room.room_type }}</p>
                <p
                  v-if="room.price_per_night"
                  class="font-bold text-slate-700 dark:text-slate-300 mt-1"
                >
                  ${{ room.price_per_night }}
                  <span class="text-[10px] font-normal text-slate-400">/ night</span>
                </p>
              </div>
            </div>
          </div>
        </div>

        <!-- TAB 3: Floor Stats & Health -->
        <div v-if="activeTab === 'stats'" class="space-y-4">
          <div
            class="bg-slate-50 dark:bg-slate-800/40 p-5 rounded-2xl border border-slate-200 dark:border-slate-800 space-y-4"
          >
            <h4
              class="text-xs font-black uppercase tracking-wider text-slate-500 dark:text-slate-400"
            >
              {{ languageStore.t('occupancy_breakdown', 'Occupancy & Delivery Performance') }}
            </h4>

            <div class="grid grid-cols-2 sm:grid-cols-3 gap-4">
              <div>
                <p class="text-xs text-slate-500 dark:text-slate-400">
                  {{ languageStore.t('occupied_rate', 'Occupancy Rate') }}
                </p>
                <p class="text-2xl font-black text-blue-600 dark:text-blue-400 mt-1">
                  {{
                    totalRoomsCount > 0
                      ? Math.round((occupiedRoomsCount / totalRoomsCount) * 100)
                      : 0
                  }}%
                </p>
              </div>

              <div>
                <p class="text-xs text-slate-500 dark:text-slate-400">
                  {{ languageStore.t('deliveries_today', 'Deliveries Today') }}
                </p>
                <p class="text-2xl font-black text-emerald-600 dark:text-emerald-400 mt-1">
                  {{ floorStats?.total_deliveries || 0 }}
                </p>
              </div>

              <div>
                <p class="text-xs text-slate-500 dark:text-slate-400">
                  {{ languageStore.t('avg_delivery_time', 'Avg Delivery Time') }}
                </p>
                <p class="text-2xl font-black text-purple-600 dark:text-purple-400 mt-1">
                  {{ floorStats?.average_delivery_time || 0 }}
                  <span class="text-xs font-bold text-slate-400">min</span>
                </p>
              </div>
            </div>
          </div>

          <!-- Actions -->
          <div
            class="flex items-center justify-between p-4 rounded-2xl bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800"
          >
            <div>
              <h5 class="text-xs font-bold text-slate-800 dark:text-slate-200">
                {{ languageStore.t('floor_status_toggle', 'Floor Operational Status') }}
              </h5>
              <p class="text-[11px] text-slate-500 dark:text-slate-400">
                {{
                  languageStore.t(
                    'floor_status_toggle_desc',
                    'Deactivating a floor suspends incoming room service and waiter assignments.',
                  )
                }}
              </p>
            </div>
            <button
              @click="toggleFloorStatus"
              class="px-4 py-2 rounded-xl text-xs font-bold transition flex items-center gap-2 cursor-pointer border"
              :class="
                currentDisplayFloor.is_active
                  ? 'bg-rose-50 dark:bg-rose-950/30 text-rose-600 dark:text-rose-400 border-rose-200 dark:border-rose-800'
                  : 'bg-emerald-50 dark:bg-emerald-950/30 text-emerald-600 dark:text-emerald-400 border-emerald-200 dark:border-emerald-800'
              "
            >
              <span>{{
                currentDisplayFloor.is_active
                  ? languageStore.t('deactivate_floor', 'Deactivate Floor')
                  : languageStore.t('activate_floor', 'Activate Floor')
              }}</span>
            </button>
          </div>
        </div>
      </div>

      <!-- Modal Footer -->
      <div
        class="p-4 sm:p-5 border-t border-slate-100 dark:border-slate-800 bg-slate-50/50 dark:bg-slate-950/30 flex items-center justify-between gap-3"
      >
        <div class="flex items-center gap-2">
          <button
            @click="emit('edit-floor', currentDisplayFloor)"
            class="px-3.5 py-2 rounded-xl bg-white dark:bg-slate-800 text-slate-700 dark:text-slate-200 hover:bg-slate-100 font-bold text-xs border border-slate-200 dark:border-slate-700 transition flex items-center gap-1.5 cursor-pointer shadow-xs"
          >
            <Edit2 class="w-3.5 h-3.5 text-blue-500" />
            <span>{{ languageStore.t('edit_floor', 'Edit Floor Info') }}</span>
          </button>
        </div>

        <button
          @click="emit('close')"
          class="px-5 py-2 rounded-xl bg-slate-900 dark:bg-white text-white dark:text-slate-900 hover:bg-slate-800 dark:hover:bg-slate-100 font-bold text-xs transition cursor-pointer"
        >
          {{ languageStore.t('close', 'Close') }}
        </button>
      </div>
    </div>
  </div>
</template>
