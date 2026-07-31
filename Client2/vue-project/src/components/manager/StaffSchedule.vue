<script setup lang="ts">
import { computed } from 'vue'

import { CalendarDays, Clock, User, Plus } from 'lucide-vue-next'

import { useManagerStore } from '@/stores/managerStore'

const manager = useManagerStore()

/*
|--------------------------------------------------------------------------
| Schedules
|--------------------------------------------------------------------------
*/

const schedules = computed(() => {
  return manager.schedules ?? []
})

/*
|--------------------------------------------------------------------------
| Statistics
|--------------------------------------------------------------------------
*/

const statistics = computed(() => {
  return {
    today: schedules.value.length,

    morning: schedules.value.filter((s) => s.shift === 'morning').length,

    evening: schedules.value.filter((s) => s.shift === 'evening').length,

    night: schedules.value.filter((s) => s.shift === 'night').length,
  }
})
</script>

<template>
  <section class="bg-white rounded-3xl border border-slate-200 shadow-sm p-6">
    <!-- HEADER -->

    <div class="flex justify-between items-center mb-8">
      <div>
        <h2 class="text-xl font-bold text-slate-900">Staff Schedule</h2>

        <p class="text-sm text-slate-500">Manage employee shifts and working hours</p>
      </div>

      <button
        class="flex items-center gap-2 bg-blue-600 text-white px-5 py-3 rounded-xl hover:bg-blue-700"
      >
        <Plus class="w-5 h-5" />

        Create Schedule
      </button>
    </div>

    <!-- STATISTICS -->

    <div class="grid grid-cols-1 md:grid-cols-4 gap-5 mb-10">
      <div class="bg-slate-50 rounded-2xl p-5">
        <p class="text-sm text-slate-500">Today's Staff</p>

        <h3 class="text-3xl font-bold">
          {{ statistics.today }}
        </h3>
      </div>

      <div class="bg-yellow-50 rounded-2xl p-5">
        <p class="text-sm text-slate-500">Morning Shift</p>

        <h3 class="text-3xl font-bold">
          {{ statistics.morning }}
        </h3>
      </div>

      <div class="bg-blue-50 rounded-2xl p-5">
        <p class="text-sm text-slate-500">Evening Shift</p>

        <h3 class="text-3xl font-bold">
          {{ statistics.evening }}
        </h3>
      </div>

      <div class="bg-purple-50 rounded-2xl p-5">
        <p class="text-sm text-slate-500">Night Shift</p>

        <h3 class="text-3xl font-bold">
          {{ statistics.night }}
        </h3>
      </div>
    </div>

    <!-- Schedule List -->

    <div class="space-y-4">
      <div
        v-for="schedule in schedules"
        :key="schedule.id"
        class="border rounded-2xl p-5 flex justify-between items-center"
      >
        <div>
          <div class="flex items-center gap-3">
            <div class="w-10 h-10 rounded-xl bg-blue-50 flex items-center justify-center">
              <User class="text-blue-600 w-5" />
            </div>

            <div>
              <h3 class="font-bold">
                {{ schedule.employee }}
              </h3>

              <p class="text-sm text-slate-500">
                {{ schedule.department }}
              </p>
            </div>
          </div>
        </div>

        <div class="text-right">
          <div class="flex items-center gap-2 justify-end font-semibold">
            <Clock class="w-4" />

            {{ schedule.start }}
            -
            {{ schedule.end }}
          </div>

          <span class="text-xs text-slate-500">
            {{ schedule.shift }}
          </span>
        </div>
      </div>
    </div>
  </section>
</template>
